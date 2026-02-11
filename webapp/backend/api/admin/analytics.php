<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../audit_log.php';

header('Content-Type: application/json');

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? 'summary';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'summary') {
    // Recent overall summary (by provider)
    // Include previous 24h for percent change calculation
    $stmt = $db->prepare(
        "SELECT r.provider, r.sent_last_24h, r.failed_last_24h, r.retry_scheduled_total, r.avg_retry_count, COALESCE(p.prev_sent,0) AS prev_sent_last_24h
         FROM analytics_integration_messages_recent r
         LEFT JOIN (
             SELECT provider, SUM(CASE WHEN created_at >= (NOW() - INTERVAL 48 HOUR) AND created_at < (NOW() - INTERVAL 24 HOUR) AND direction='outbound' THEN 1 ELSE 0 END) as prev_sent
             FROM integration_messages GROUP BY provider
         ) p ON p.provider = r.provider"
    );
    $stmt->execute();
    $rows = [];
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { 
        // compute percent change safely
        $sent = intval($r['sent_last_24h']);
        $prev = intval($r['prev_sent_last_24h']);
        $delta = null;
        if ($prev === 0 && $sent > 0) { $delta = 100.0; }
        elseif ($prev === 0 && $sent === 0) { $delta = 0.0; }
        else { $delta = $prev === 0 ? 0.0 : (($sent - $prev) / max(1,$prev)) * 100.0; }
        $r['sent_delta_pct'] = round($delta,2);
        $rows[] = $r; 
    }

    // Daily trends (last 14 days) for twilio
    $trendStmt = $db->prepare("SELECT day, total_messages, delivered_count, failed_count, retry_scheduled FROM analytics_integration_messages_daily WHERE provider = 'twilio' ORDER BY day DESC LIMIT 14");
    $trendStmt->execute();
    $trend = [];
    $tres = $trendStmt->get_result();
    while ($r = $tres->fetch_assoc()) { $trend[] = $r; }

    // Orders summary (30 days)
    $ordersStmt = $db->prepare("SELECT * FROM analytics_orders_summary LIMIT 1");
    $ordersStmt->execute();
    $orders = $ordersStmt->get_result()->fetch_assoc();

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
    $workerPath = realpath(__DIR__ . '/../../scripts/refresh_analytics_materialized.php');
    if (!$workerPath) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Worker not found']); exit; }
    $days = isset($_POST['days']) ? intval($_POST['days']) : null;
    $cmd = escapeshellcmd(PHP_BINARY ?? 'php') . ' ' . escapeshellarg($workerPath);
    if ($days && $days > 0) {
        $cmd .= ' ' . escapeshellarg((string)$days);
    }
    $cmd .= ' 2>&1';
    $out = [];
    $code = 0;
    exec($cmd, $out, $code);
    AuditLog::log('ANALYTICS_MATERIALIZED_RUN_MANUAL', ['by'=>$user['id'],'exit_code'=>$code,'output'=>array_slice($out,0,20),'days'=>$days], $user['id']);
    echo json_encode(['success'=>true,'exit_code'=>$code,'output'=>implode("\n", array_slice($out,0,200))]);
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action']);
exit;