<?php
// Global error handler: always return JSON on error, with CORS
function twilio_send_cors_headers() {
    // CORS handled centrally (cors.php / .htaccess) — avoid duplicate Access-Control-Allow-Origin
    $apacheCors = getenv('CORS_ORIGIN') ?: ($_SERVER['CORS_ORIGIN'] ?? $_SERVER['REDIRECT_CORS_ORIGIN'] ?? '');
    if (empty($apacheCors)) {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS,PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma, ngrok-skip-browser-warning, X-Twilio-Signature');
    }
}
set_exception_handler(function($e){
    twilio_send_cors_headers();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Internal server error','error'=>$e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    twilio_send_cors_headers();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Internal server error','error'=>"$errstr in $errfile:$errline"]);
    exit;
});

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../audit_log.php';

// Get MySQLi connection (database.php provides PDO as $conn/$pdo, MySQLi via Database class)
$db = Database::getConnection();

twilio_send_cors_headers();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$user = validateToken(); // may be null for public webhook

// Helper: fetch integration setting (PDO)
function getIntegrationSetting($db, $key) {
    $stmt = $db->prepare("SELECT `value` FROM integration_settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['value'] : null;
}

// Admin endpoints: save/get settings
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'settings') {
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $keys = ['twilio_account_sid','twilio_auth_token','twilio_whatsapp_from','twilio_webhook_secret'];
    $settings = [];
    foreach ($keys as $k) {
        $val = getIntegrationSetting($db, $k);
        $settings[$k] = $val;
    }

    echo json_encode(['success'=>true,'settings'=>$settings]);
    exit;
}

// Admin: provider-specific settings (generic)
if (isset($_GET['action']) && $_GET['action'] === 'provider_settings') {
    if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

    if ($method === 'GET') {
        $provider = $_GET['provider'] ?? null;
        if (!$provider) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'provider required']); exit; }

        $pfx = 'provider_' . $provider . '_%';
        $stmt = $db->prepare("SELECT `key`,`value` FROM integration_settings WHERE `key` LIKE ?");
        $stmt->execute([$pfx]);
        $out = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $k = substr($r['key'], strlen('provider_' . $provider . '_'));
            $out[$k] = $r['value'];
        }
        echo json_encode(['success'=>true,'provider'=>$provider,'settings'=>$out]);
        exit;
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $provider = $data['provider'] ?? null;
        $settings = $data['settings'] ?? [];
        if (!$provider || !is_array($settings)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid body']); exit; }

        foreach ($settings as $k => $v) {
            $key = 'provider_' . $provider . '_' . $k;
            $stmt = $db->prepare("INSERT INTO integration_settings (`key`,`value`,`description`) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$key, $v]);
        }

        AuditLog::log('PROVIDER_SETTINGS_UPDATED', ['provider'=>$provider,'keys'=>array_keys($settings)], $user['id']);
        echo json_encode(['success'=>true]);
        exit;
    }
}

// Admin: send a test security alert (Slack/email)
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'alerts' && isset($_GET['test'])) {
    if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }
    AuditLog::logSecurityAlert('TWILIO_WORKER_ALERT_TEST', ['initiated_by'=>$user['id']]);
    echo json_encode(['success'=>true,'message'=>'Test alert triggered']);
    exit;
}

// Admin: run worker now (executes the cron worker script)
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'run_worker') {
    if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

    $workerPath = realpath(__DIR__ . '/../../cron/twilio_status.php');
    if (!$workerPath) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Worker not found']); exit; }

    $cmd = escapeshellcmd(PHP_BINARY ?? 'php') . ' ' . escapeshellarg($workerPath) . ' 2>&1';
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);

    AuditLog::log('TWILIO_WORKER_RUN_MANUAL', ['by'=>$user['id'],'exit_code'=>$code,'output'=>array_slice($out,0,20)], $user['id']);
    echo json_encode(['success'=>true,'exit_code'=>$code,'output'=>implode("\n", array_slice($out,0,200))]);
    exit;
}

// Admin logs viewer
if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'logs') {
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    // Worker status endpoint
    if (isset($_GET['action2']) && $_GET['action2'] === 'worker_status') {
        // Read values from integration_settings
        $keys = ['twilio_status_last_run','twilio_status_last_summary'];
        $res = [];
        $stmt = $db->prepare("SELECT `key`,`value` FROM integration_settings WHERE `key` IN (?, ?)");
        $stmt->execute([$keys[0], $keys[1]]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) { $res[$r['key']] = $r['value']; }

        $lastRun = $res['twilio_status_last_run'] ?? null;
        $summary = $res['twilio_status_last_summary'] ?? null;
        if ($summary && is_string($summary)) { $summary = json_decode($summary, true); }

        // Additional live checks
        $pendingStmt = $db->prepare("SELECT COUNT(*) as c FROM integration_messages WHERE provider='twilio' AND status='retry_scheduled'");
        $pendingStmt->execute();
        $pending = intval($pendingStmt->fetchColumn() ?? 0);

        echo json_encode(['success'=>true,'last_run'=>$lastRun,'summary'=>$summary,'pending_retry'=>$pending]);
        exit;
    }

    // Single log fetch by id
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $db->prepare("SELECT * FROM integration_messages WHERE id = ? AND provider = 'twilio' LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'log' => $row]);
        exit;
    }

    $limit = min(200, intval($_GET['limit'] ?? 50));
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $status = $_GET['status'] ?? null;
    $direction = $_GET['direction'] ?? null;
    $fromDate = $_GET['from'] ?? null; // YYYY-MM-DD
    $toDate = $_GET['to'] ?? null; // YYYY-MM-DD

    $whereParts = ["provider = 'twilio'"];
    $params = [];

    if ($status) { $whereParts[] = 'status = ?'; $params[] = $status; }
    if ($direction) { $whereParts[] = 'direction = ?'; $params[] = $direction; }
    if ($fromDate) { $whereParts[] = 'created_at >= ?'; $params[] = $fromDate . ' 00:00:00'; }
    if ($toDate) { $whereParts[] = 'created_at <= ?'; $params[] = $toDate . ' 23:59:59'; }

    $whereSql = implode(' AND ', $whereParts);

    // count total (PDO)
    $countSql = "SELECT COUNT(*) FROM integration_messages WHERE $whereSql";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn() ?? 0;

    // fetch rows (PDO)
    $sql = "SELECT * FROM integration_messages WHERE $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'logs' => $rows, 'total' => intval($total), 'page' => $page, 'limit' => $limit]);
    exit;
}

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'settings') {
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Missing body']); exit; }

    foreach (['twilio_account_sid','twilio_auth_token','twilio_whatsapp_from','twilio_webhook_secret'] as $k) {
        if (isset($data[$k])) {
            $val = $data[$k];
            // Upsert (PDO)
            $stmt = $db->prepare("INSERT INTO integration_settings (`key`,`value`,`description`) VALUES (?, ?, NULL) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$k, $val]);
        }
    }

    AuditLog::log('TWILIO_SETTINGS_UPDATED', ['by' => $user['id'], 'payload_keys' => array_keys($data)], $user['id']);

    echo json_encode(['success'=>true]);
    exit;
}

// Send message endpoint (admin only)
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'send') {
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $to = $data['to'] ?? null; // in E.164 or whatsapp:+62...
    $body = $data['body'] ?? '';
    if (!$to || !$body) {
        http_response_code(400);
        echo json_encode(['success'=>false,'message'=>'`to` and `body` required']);
        exit;
    }

    $accountSid = getIntegrationSetting($db, 'twilio_account_sid');
    $authToken = getIntegrationSetting($db, 'twilio_auth_token');
    $from = getIntegrationSetting($db, 'twilio_whatsapp_from');

    if (!$accountSid || !$authToken || !$from) {
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Twilio not configured']);
        exit;
    }

    // Log message in DB (PDO)
    $stmt = $db->prepare("INSERT INTO integration_messages (provider, channel, direction, to_number, from_number, body, status) VALUES ('twilio','whatsapp','outbound', ?, ?, ?, 'queued')");
    $stmt->execute([$to, $from, $body]);
    $messageId = $db->lastInsertId();

    // Delegate sending via provider adapter
    require_once __DIR__ . '/../../lib/sms_providers.php';

    $providerResp = send_sms_via_provider('twilio', [
        'to' => $to,
        'from' => $from,
        'body' => $body,
        'account_sid' => $accountSid,
        'auth_token' => $authToken
    ]);

    if ($providerResp['success']) {
        $sid = $providerResp['sid'] ?? null;
        $status = $providerResp['status'] ?? 'sent';
        $pp = is_string($providerResp['raw'] ?? '') ? ($providerResp['raw']) : json_encode($providerResp['payload'] ?? []);
        $stmt = $db->prepare("UPDATE integration_messages SET status = ?, provider_payload = ? WHERE id = ?");
        $stmt->execute([$status, $pp, $messageId]);

        AuditLog::log('TWILIO_MESSAGE_SENT', ['message_id' => $messageId, 'sid' => $sid, 'to' => $to], $user['id']);

        echo json_encode(['success'=>true, 'sid'=>$sid, 'provider_payload'=>$providerResp['payload'] ?? null]);
        exit;
    } else {
        $err = $providerResp['error'] ?? 'Unknown provider error';
        $stmt = $db->prepare("UPDATE integration_messages SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute([$err, $messageId]);

        AuditLog::logApiError('/integrations/twilio.php?action=send', $err, $providerResp['http'] ?? 500);

        http_response_code(500);
        echo json_encode(['success'=>false, 'error'=>$err, 'raw'=>$providerResp]);
        exit;
    }
}

// Helper: Validate Twilio signature using official algorithm
function validate_twilio_signature($authToken, $fullUrl, $params, $signature) {
    // Build concatenated string: url + sorted params (alphabetical)
    ksort($params);
    $concatenated = $fullUrl;
    foreach ($params as $k => $v) {
        // Twilio uses string values concatenated in param order
        $concatenated .= $k . $v;
    }

    $expected = base64_encode(hash_hmac('sha1', $concatenated, $authToken, true));
    return hash_equals($expected, $signature);
}

// Webhook listener for incoming messages (public). Use Twilio signature verification
if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'webhook') {
    // Read raw POST body and headers
    $raw = file_get_contents('php://input');
    $headers = getallheaders();
    $twilioSig = $headers['X-Twilio-Signature'] ?? $headers['x-twilio-signature'] ?? null;

    // Get auth token from settings
    $authToken = getIntegrationSetting($db, 'twilio_auth_token');

    // Build full URL that Twilio called
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    $requestUri = $_SERVER['REQUEST_URI'];
    $fullUrl = $scheme . '://' . $host . $requestUri;

    // Parse params
    parse_str($raw, $params);

    // If auth token present, validate signature using Twilio algorithm
    if ($authToken) {
        if (!$twilioSig || !validate_twilio_signature($authToken, $fullUrl, $params, $twilioSig)) {
            http_response_code(401);
            AuditLog::log('TWILIO_WEBHOOK_VERIFY_FAILED', ['ip'=>$_SERVER['REMOTE_ADDR']], null, 'warning');
            echo json_encode(['success'=>false,'message'=>'Invalid Twilio signature']);
            exit;
        }
    }

    // For WhatsApp inbound, Body, From, To, MessageSid
    $from = $params['From'] ?? null;
    $to = $params['To'] ?? null;
    $body = $params['Body'] ?? null;
    $numMedia = intval($params['NumMedia'] ?? 0);

    // Log inbound message
    $payloadJson = json_encode($params);
    $stmt = $db->prepare("INSERT INTO integration_messages (provider, channel, direction, to_number, from_number, body, status, provider_payload, metadata) VALUES ('twilio','whatsapp','inbound', ?, ?, ?, 'received', ?, NULL)");
    $stmt->execute([$to, $from, $body, $payloadJson]);
    $msgId = $db->lastInsertId();

    // Handle media attachments (download and store under /uploads/integrations/twilio/{msgId}/)
    $attachments = [];
    if ($numMedia > 0) {
        $uploadBase = __DIR__ . '/../../uploads/integrations/twilio/' . $msgId;
        if (!is_dir($uploadBase)) {
            @mkdir($uploadBase, 0755, true);
        }

        for ($i = 0; $i < $numMedia; $i++) {
            $mUrl = $params["MediaUrl{$i}"] ?? null;
            $mType = $params["MediaContentType{$i}"] ?? null;
            if (!$mUrl) continue;

            // Download media from Twilio (use account credentials if available)
            $ch = curl_init($mUrl);
            $accountSid = getIntegrationSetting($db, 'twilio_account_sid');
            $authToken = getIntegrationSetting($db, 'twilio_auth_token');
            if ($accountSid && $authToken) {
                curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $bin = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($bin !== false && $http >= 200 && $http < 300) {
                $fn = basename(parse_url($mUrl, PHP_URL_PATH));
                if (!$fn) { $fn = 'media_' . $i; }
                $safe = preg_replace('/[^a-zA-Z0-9\.\-\_]/', '_', $fn);
                $localPath = $uploadBase . '/' . $safe;
                file_put_contents($localPath, $bin);

                // store relative web path
                $webPath = "uploads/integrations/twilio/{$msgId}/{$safe}";
                $attachments[] = ['url'=>$mUrl, 'path'=>$webPath, 'content_type'=>$mType, 'filename'=>$safe, 'http_code'=>$http];
            } else {
                $attachments[] = ['url'=>$mUrl, 'error'=> $err, 'http_code'=>$http];
            }
        }

        if (!empty($attachments)) {
            $metaJson = json_encode(['attachments'=>$attachments]);
            $u = $db->prepare("UPDATE integration_messages SET metadata = ? WHERE id = ?");
            $u->execute([$metaJson, $msgId]);
        }
    }

    AuditLog::log('TWILIO_INBOUND', ['id'=>$msgId,'from'=>$from,'to'=>$to,'body'=>mb_substr($body,0,200)]);

    // Example: if message contains "HELP" create a ticket for customer if number matches a user
    if ($body && stripos($body, 'help') !== false) {
        // try to find user by phone
        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$from]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $u ? $u['id'] : null;

        $ticketSubject = 'WhatsApp Support: ' . ($userId ? "User #$userId" : $from);
        $ticketBody = "WhatsApp inbound: " . $body;
        if ($userId) {
            $stmt = $db->prepare("INSERT INTO tickets (ticket_number, user_id, order_id, subject, category, priority, status) VALUES (?, ?, NULL, ?, 'whatsapp', 'normal','open')");
            $ticketNumber = 'TKT-' . strtoupper(uniqid());
            $stmt->execute([$ticketNumber, $userId, $ticketSubject]);
            $ticketId = $db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_staff) VALUES (?, ?, ?, 0)");
            $stmt->execute([$ticketId, $userId, $ticketBody]);

            AuditLog::log('TWILIO_WEBHOOK_CREATED_TICKET', ['ticket_id'=>$ticketId,'from'=>$from]);
        }
    }

    // Respond with 200 OK to Twilio
    http_response_code(200);
    echo "OK";
    exit;
}

// Fallback
http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid request']);
exit;
