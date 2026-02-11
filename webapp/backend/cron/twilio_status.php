<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/integrations/twilio.php'; // for helper functions (optional)
require_once __DIR__ . '/../api/twilio_helpers.php';
require_once __DIR__ . '/../api/audit_log.php';

// Poll Twilio for message status updates and schedule retries
$accountSid = getIntegrationSetting($pdo, 'twilio_account_sid');
$authToken = getIntegrationSetting($pdo, 'twilio_auth_token');

if (!$accountSid || !$authToken) {
    echo "Twilio not configured\n";
    exit;
}

// Acquire advisory lock to prevent concurrent runs (wait up to 10 seconds)
$lockName = 'twilio_status_lock';
try {
    $lockRow = $pdo->query("SELECT GET_LOCK('{$lockName}', 10) as got")->fetch(PDO::FETCH_ASSOC);
    $gotLock = !empty($lockRow['got']);
} catch (Exception $e) {
    echo "Lock acquisition error: " . $e->getMessage() . "\n";
    exit;
}

if (!$gotLock) {
    echo "Could not acquire lock ({$lockName}), another instance may be running. Exiting.\n";
    exit;
}

try {
    // 1) Find messages with provider_message_sid and non-final statuses
    $inStmt = $pdo->prepare("SELECT * FROM integration_messages WHERE provider = 'twilio' AND provider_message_sid IS NOT NULL AND status NOT IN ('delivered','undelivered','failed') ORDER BY created_at DESC LIMIT 200");
    $inStmt->execute();
    $rows = $inStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $sid = $r['provider_message_sid'];
        if (!$sid) continue;
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages/{$sid}.json";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            $payload = json_decode($resp, true);
            $status = $payload['status'] ?? null;
            $sidResp = $payload['sid'] ?? null;

            if ($status && $status !== $r['status']) {
                $u = $pdo->prepare("UPDATE integration_messages SET status = ?, provider_payload = ?, updated_at = NOW() WHERE id = ?");
                $u->execute([$status, $resp, $r['id']]);
                AuditLog::log('TWILIO_MESSAGE_STATUS_UPDATED', ['id'=>$r['id'],'sid'=>$sidResp,'status'=>$status]);
            }

            // Handle failed/delivered states
            if (in_array($status, ['failed','undelivered'])) {
                // schedule retry if allowed
                $retryCount = intval($r['retry_count'] ?? 0);
                $max = intval($r['max_retries'] ?? 3);
                if ($retryCount < $max) {
                    $next = new DateTime();
                    // exponential backoff minutes: 2^retry * 5
                    $delayMin = (pow(2, $retryCount) * 5);
                    $next->modify("+{$delayMin} minutes");
                    $nxt = $next->format('Y-m-d H:i:s');
                    $s = $pdo->prepare("UPDATE integration_messages SET status = 'retry_scheduled', next_retry_at = ?, retry_count = retry_count + 1 WHERE id = ?");
                    $s->execute([$nxt, $r['id']]);
                    AuditLog::log('TWILIO_MESSAGE_RETRY_SCHEDULED', ['id'=>$r['id'],'next_retry_at'=>$nxt]);
                }
            }
        } else {
            AuditLog::logApiError('twilio_poll', "HTTP $http", $http);
        }
    }

    // 2) Find retry_scheduled rows ready to resend
    $now = date('Y-m-d H:i:s');
    $rs = $pdo->prepare("SELECT * FROM integration_messages WHERE provider = 'twilio' AND status = 'retry_scheduled' AND next_retry_at <= ? LIMIT 50");
    $rs->execute([$now]);
    $ready = $rs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ready as $m) {
        // Resend using the stored body and from/to
        $to = $m['to_number'];
        $from = $m['from_number'];
        $body = $m['body'];
        $accountSid = getIntegrationSetting($pdo, 'twilio_account_sid');
        $authToken = getIntegrationSetting($pdo, 'twilio_auth_token');

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $post = http_build_query(['To' => $to, 'From' => $from, 'Body' => $body]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            $payload = json_decode($resp, true);
            $sid = $payload['sid'] ?? null;
            $status = $payload['status'] ?? 'sent';
            $u = $pdo->prepare("UPDATE integration_messages SET status = ?, provider_message_sid = ?, provider_payload = ?, last_attempt_at = NOW(), next_retry_at = NULL, updated_at = NOW() WHERE id = ?");
            $u->execute([$status, $sid, $resp, $m['id']]);
            AuditLog::log('TWILIO_MESSAGE_RESENT', ['id'=>$m['id'],'sid'=>$sid,'status'=>$status]);
        } else {
            $u = $pdo->prepare("UPDATE integration_messages SET status = 'failed', error_message = ?, last_attempt_at = NOW(), updated_at = NOW() WHERE id = ?");
            $u->execute([$err, $m['id']]);
            AuditLog::logApiError('twilio_resend', $err, $http);
        }
    }
} catch (Exception $e) {
    AuditLog::log('TWILIO_STATUS_ERROR', ['message'=>$e->getMessage()]);
    echo "Error during twilio status worker: " . $e->getMessage() . "\n";
} finally {
    // Write health summary into integration_settings for operator visibility
    try {
        $failedCountStmt = $pdo->prepare("SELECT COUNT(*) as c FROM integration_messages WHERE provider = 'twilio' AND status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $failedCountStmt->execute();
        $failedCount = intval($failedCountStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $retryCountStmt = $pdo->prepare("SELECT COUNT(*) as c FROM integration_messages WHERE provider = 'twilio' AND status = 'retry_scheduled'");
        $retryCountStmt->execute();
        $retryScheduled = intval($retryCountStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $summary = json_encode(['polled' => (isset($rows) ? count($rows) : 0), 'retried' => (isset($ready) ? count($ready) : 0), 'failed_last_hour' => $failedCount, 'retry_scheduled' => $retryScheduled]);

        $up = $pdo->prepare("INSERT INTO integration_settings (`key`,`value`,`description`) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $now = date('Y-m-d H:i:s');
        $up->execute(['twilio_status_last_run', $now]);
        $up->execute(['twilio_status_last_summary', $summary]);

        // Alert if thresholds exceeded
        $alertThresholdFailed = intval(getenv('TWILIO_ALERT_FAILED_THRESHOLD') ?: 5);
        $alertThresholdRetry = intval(getenv('TWILIO_ALERT_RETRY_THRESHOLD') ?: 100);

        if ($failedCount >= $alertThresholdFailed || $retryScheduled >= $alertThresholdRetry) {
            AuditLog::logSecurityAlert('TWILIO_WORKER_ALERT', ['failed_last_hour' => $failedCount, 'retry_scheduled' => $retryScheduled]);
        }
    } catch (Exception $ex) {
        // ignore health write errors but log
        AuditLog::log('TWILIO_STATUS_HEALTH_WRITE_ERROR', ['message'=>$ex->getMessage()], null, 'warning');
    }

    // Release advisory lock
    try {
        $pdo->query("SELECT RELEASE_LOCK('{$lockName}')");
    } catch (Exception $ex) {
        // ignore
    }
}

echo "Twilio status check done. Polled: " . (isset($rows) ? count($rows) : 0) . ", retried: " . (isset($ready) ? count($ready) : 0) . "\n";
