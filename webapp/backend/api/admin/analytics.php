<?php
// Global error handler: always return JSON on error, with CORS
function send_cors_headers() {
    // CORS handled centrally (cors.php / .htaccess) — avoid duplicate Access-Control-Allow-Origin
    $apacheCors = getenv('CORS_ORIGIN') ?: ($_SERVER['CORS_ORIGIN'] ?? $_SERVER['REDIRECT_CORS_ORIGIN'] ?? '');
    if (empty($apacheCors)) {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
        header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS,PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma, ngrok-skip-browser-warning');
    }
}
set_exception_handler(function($e){
    send_cors_headers();
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Internal server error','error'=>$e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    send_cors_headers();
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

send_cors_headers();
header('Content-Type: application/json');

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? 'summary';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'summary') {
    // Recent overall summary (by provider)
    // Include previous 24h for percent change calculation
    // Query summary data, handle SQL errors gracefully
    $rows = [];
    $trend = [];
    $orders = null;
    try {
        $stmt = $db->prepare(
            "SELECT r.provider, r.sent_last_24h, r.failed_last_24h, r.retry_scheduled_total, r.avg_retry_count, COALESCE(p.prev_sent,0) AS prev_sent_last_24h
             FROM analytics_integration_messages_recent r
             LEFT JOIN (
                 SELECT provider, SUM(CASE WHEN created_at >= (NOW() - INTERVAL 48 HOUR) AND created_at < (NOW() - INTERVAL 24 HOUR) AND direction='outbound' THEN 1 ELSE 0 END) as prev_sent
                 FROM integration_messages GROUP BY provider
             ) p ON p.provider = r.provider"
        );
        if ($stmt && $stmt->execute()) {
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $sent = intval($r['sent_last_24h']);
                $prev = intval($r['prev_sent_last_24h']);
                $delta = null;
                if ($prev === 0 && $sent > 0) { $delta = 100.0; }
                elseif ($prev === 0 && $sent === 0) { $delta = 0.0; }
                else { $delta = $prev === 0 ? 0.0 : (($sent - $prev) / max(1,$prev)) * 100.0; }
                $r['sent_delta_pct'] = round($delta,2);
                $rows[] = $r;
            }
        }
    } catch (Throwable $e) {
        // Log error if needed
        $rows = [];
    }
    try {
        $trendStmt = $db->prepare("SELECT day, total_messages, delivered_count, failed_count, retry_scheduled FROM analytics_integration_messages_daily WHERE provider = 'twilio' ORDER BY day DESC LIMIT 14");
        if ($trendStmt && $trendStmt->execute()) {
            $tres = $trendStmt->get_result();
            while ($r = $tres->fetch_assoc()) { $trend[] = $r; }
        }
    } catch (Throwable $e) {
        $trend = [];
    }
    try {
        $ordersStmt = $db->prepare("SELECT * FROM analytics_orders_summary LIMIT 1");
        if ($ordersStmt && $ordersStmt->execute()) {
            $orders = $ordersStmt->get_result()->fetch_assoc();
        }
    } catch (Throwable $e) {
        $orders = null;
    }
    echo json_encode(['success'=>true,'summary'=>$rows,'trend'=>$trend,'orders'=>$orders]);
    exit;
}

if ($action === 'provider') {
    // Provider breakdown: support multiple providers (comma-separated) with optional date range and channel
    $providerParam = $_GET['provider'] ?? null;
    if (!$providerParam) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'provider required']); exit; }

    $providers = array_map('trim', explode(',', $providerParam));
    if (empty($providers)) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'invalid provider list']); exit; }

    $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $to = $_GET['to'] ?? date('Y-m-d');
    $channel = $_GET['channel'] ?? null;

    // Build SQL with IN clause for providers
    $placeholders = implode(',', array_fill(0, count($providers), '?'));
    // Prefer materialized table if exists
    $useMat = $db->query("SHOW TABLES LIKE 'analytics_integration_messages_daily_mat'")->num_rows > 0;
    $tableName = $useMat ? 'analytics_integration_messages_daily_mat' : 'analytics_integration_messages_daily';
    $sql = "SELECT provider, day, channel, total_messages, delivered_count, failed_count, retry_scheduled FROM `" . $tableName . "` WHERE provider IN ($placeholders) AND day BETWEEN ? AND ?";
    if ($channel) { $sql .= " AND channel = ?"; }
    $sql .= " ORDER BY provider ASC, day ASC";

    $stmt = $db->prepare($sql);

    // Build bind types and params
    $types = str_repeat('s', count($providers)) . 'ss' . ($channel ? 's' : '');
    $params = array_merge($providers, [$from, $to]);
    if ($channel) $params[] = $channel;

    // mysqli bind_param with references
    $bindNames = [];
    $bindNames[] = &$types;
    foreach ($params as $k => $v) { $bindNames[] = &$params[$k]; }
    call_user_func_array([$stmt, 'bind_param'], $bindNames);

    $stmt->execute();
    $rows = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }

    // Totals grouped by provider
    $totSql = "SELECT provider, SUM(total_messages) as total_sent, SUM(delivered_count) as total_delivered, SUM(failed_count) as total_failed FROM " . $tableName . " WHERE provider IN ($placeholders) AND day BETWEEN ? AND ?";
    if ($channel) { $totSql .= " AND channel = ?"; }
    $totSql .= " GROUP BY provider";
    $totStmt = $db->prepare($totSql);

    $typesTot = str_repeat('s', count($providers)) . 'ss' . ($channel ? 's' : '');
    $paramsTot = array_merge($providers, [$from, $to]);
    if ($channel) $paramsTot[] = $channel;
    $bindNames = [];
    $bindNames[] = &$typesTot;
    foreach ($paramsTot as $k => $v) { $bindNames[] = &$paramsTot[$k]; }
    call_user_func_array([$totStmt, 'bind_param'], $bindNames);
    $totStmt->execute();
    $totRows = [];
    $tres = $totStmt->get_result();
    while ($r = $tres->fetch_assoc()) { $totRows[] = $r; }

    echo json_encode(['success'=>true,'providers'=>$providers,'from'=>$from,'to'=>$to,'series'=>$rows,'totals'=>$totRows]);
    exit;
}

if ($action === 'test_alert') {
    // Trigger a test alert for analytics monitoring
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); exit; }
    $note = $_POST['note'] ?? 'Triggered via Admin UI';
    AuditLog::logSecurityAlert('ANALYTICS_REFRESH_TEST', ['note'=>$note, 'by'=>$user['id']]);
    echo json_encode(['success'=>true,'message'=>'Test alert sent']);
    exit;
}
// Admin: refresh materialized analytics table
if ($action === 'refresh_materialized') {
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); exit; }

    $days = isset($_POST['days']) ? intval($_POST['days']) : null;

    try {
        if ($days && $days > 0) {
            // Incremental refresh using DB (transaction)
            $from = date('Y-m-d', strtotime("-{$days} days"));
            $pdo->beginTransaction();
            $delStmt = $pdo->prepare('DELETE FROM analytics_integration_messages_daily_mat WHERE day >= :from');
            $delStmt->execute([':from' => $from]);

            $insSql = "INSERT INTO analytics_integration_messages_daily_mat (provider, day, channel, total_messages, delivered_count, failed_count, retry_scheduled, avg_retry_count)
                SELECT provider, DATE(created_at) as day, channel, SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as total_messages, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count, SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) as retry_scheduled, AVG(COALESCE(retry_count,0)) as avg_retry_count
                FROM integration_messages
                WHERE DATE(created_at) >= :from
                GROUP BY provider, DATE(created_at), channel";
            $ins = $pdo->prepare($insSql);
            $ins->execute([':from' => $from]);
            $pdo->commit();

            AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH_INCREMENTAL', ['days'=>$days,'from'=>$from,'updated_at'=>date('Y-m-d H:i:s')]);
            echo json_encode(['success'=>true,'message'=>'Incremental refresh complete','days'=>$days]);
            exit;
        }

        // Full refresh performed in-process (no external exec)
        $pdo->exec('TRUNCATE TABLE analytics_integration_messages_daily_mat');
        $insSql = "INSERT INTO analytics_integration_messages_daily_mat (provider, day, channel, total_messages, delivered_count, failed_count, retry_scheduled, avg_retry_count)
                SELECT provider, DATE(created_at) as day, channel, SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as total_messages, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count, SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) as retry_scheduled, AVG(COALESCE(retry_count,0)) as avg_retry_count
                FROM integration_messages
                GROUP BY provider, DATE(created_at), channel";
        $pdo->exec($insSql);
        AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH', ['updated_at'=>date('Y-m-d H:i:s')]);
        echo json_encode(['success'=>true,'message'=>'Refreshed materialized analytics table']);
        exit;
    } catch (Exception $e) {
        try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) {}
        AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH_FAILED', ['message'=>$e->getMessage(),'days'=>$days], $user['id'] ?? null, 'error');
        http_response_code(500);
        echo json_encode(['success'=>false,'message'=>'Failed refreshing materialized analytics','error'=>$e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action']);
exit;