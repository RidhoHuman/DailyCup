<?php
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../audit_log.php';
require_once __DIR__ . '/../../lib/sms_providers.php';

header('Content-Type: application/json');

$user = validateToken();
// Diagnostic header logging (dev only)
$method = $_SERVER['REQUEST_METHOD'];
if (strtolower(getenv('DEV_VERBOSE') ?: '') === 'true') {
    $authRaw = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    $tokenParam = $_GET['token'] ?? $_POST['token'] ?? null;
    $mask = function($t){ if (!$t) return null; $len=strlen($t); if ($len<=10) return substr($t,0,2).'...'; return substr($t,0,4).'...'.substr($t,-4); };
    AuditLog::log('DEBUG_HEADERS', ['auth_header'=>$mask($authRaw),'token_param'=>$mask($tokenParam)], $user['id'] ?? null, 'warning');
}

// Strict mode: tidak ada fallback dev, hanya JWT valid yang diterima

if (!$user || ($user['role'] ?? '') !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? null;

if ($action !== 'send') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit; }
if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$template = $data['template'] ?? null; // 'gajian' | 'reminder'
$userIds = $data['user_ids'] ?? [];
$segment = $data['segment'] ?? null; // optional: 'new'|'vip'|'passive'
$provider = $data['provider'] ?? 'twilio';

if (!$template) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'template required']); exit; }

// templates
$templates = [
    'gajian' => "Halo, promo gajian dari DailyCup! Nikmati diskon 20% untuk semua item. Buruan buruan!",
    'reminder' => "Hai! Keranjangmu menunggu. Lengkapi checkout dan dapatkan voucher Rp10.000. Klik sekarang!",
];

if (!isset($templates[$template])) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'unknown template']); exit; }

$bodyTemplate = $templates[$template];

# Resolve recipients
$recipients = [];
if (!empty($userIds) && is_array($userIds)) {
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $db->prepare("SELECT id, name, phone, email FROM users WHERE id IN ($placeholders)");
    // bind
    $types = str_repeat('i', count($userIds));
    $a = [$types];
    foreach ($userIds as $k => $v) $a[] = &$userIds[$k];
    call_user_func_array([$stmt, 'bind_param'], $a);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $recipients[] = $r;
} elseif ($segment) {
    // simple segment queries
    if ($segment === 'new') {
        $q = $db->prepare("SELECT id, name, phone, email FROM users WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
        $q->execute();
        $res = $q->get_result(); while ($r = $res->fetch_assoc()) $recipients[] = $r;
    } elseif ($segment === 'vip') {
        $q = $db->prepare("SELECT u.id, u.name, u.phone, u.email, COALESCE(SUM(o.total),0) as total_spend FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled' GROUP BY u.id HAVING total_spend > 500000");
        $q->execute(); $res = $q->get_result(); while ($r = $res->fetch_assoc()) $recipients[] = $r;
    } elseif ($segment === 'passive') {
        $q = $db->prepare("SELECT u.id, u.name, u.phone, u.email FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled' GROUP BY u.id HAVING COALESCE(MAX(o.created_at), u.created_at) < (NOW() - INTERVAL 60 DAY)");
        $q->execute(); $res = $q->get_result(); while ($r = $res->fetch_assoc()) $recipients[] = $r;
    }
} else {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'recipients required (user_ids or segment)']); exit;
}

if (empty($recipients)) { echo json_encode(['success'=>true,'sent'=>0,'message'=>'No recipients']); exit; }

// Decide provider (prefer env or integration settings)
$integrationProvider = $provider;
$accountSid = getenv('TWILIO_ACCOUNT_SID') ?: getIntegrationSetting($db, 'twilio_account_sid');
$authToken = getenv('TWILIO_AUTH_TOKEN') ?: getIntegrationSetting($db, 'twilio_auth_token');
$twilioFrom = getenv('TWILIO_WHATSAPP_FROM') ?: getIntegrationSetting($db, 'twilio_whatsapp_from');
if ($integrationProvider === 'twilio' && (!$accountSid || !$authToken || !$twilioFrom)) {
    // fallback to mock
    $integrationProvider = 'mock';
}

$sent = 0; $failed = 0; $results = [];
foreach ($recipients as $r) {
    $to = $r['phone'] ?? null;
    if (!$to) { $failed++; $results[] = ['id'=>$r['id'],'error'=>'no phone']; continue; }
    // normalize number
    if (preg_match('/^\+?[0-9]+$/', $to)) {
        $to = 'whatsapp:' . preg_replace('/^\+/', '', $to);
        $to = 'whatsapp:' . preg_replace('/[^0-9]/', '', $to);
        $to = 'whatsapp:+'.ltrim($to,'whatsapp:+');
    }

    $from = ($integrationProvider === 'twilio') ? $twilioFrom : null;
    $body = str_replace('{name}', $r['name'] ?? '', $bodyTemplate);

    // insert into integration_messages as queued
    $stmt = $db->prepare("INSERT INTO integration_messages (provider, channel, direction, to_number, from_number, body, status) VALUES (?, ?, 'outbound', ?, ?, ?, 'queued')");
    $channel = ($integrationProvider === 'twilio') ? 'whatsapp' : 'sms';
    $stmt->bind_param('sssss', $integrationProvider, $channel, $to, $from, $body);
    $stmt->execute(); $messageId = $stmt->insert_id;

    // call provider
    $params = ['to'=>$to,'from'=>$from,'body'=>$body,'account_sid'=>$accountSid,'auth_token'=>$authToken];
    $resp = send_sms_via_provider($integrationProvider, $params);
    if ($resp['success']) {
        $sid = $resp['sid'] ?? null;
        $status = $resp['status'] ?? 'sent';
        $pp = is_string($resp['raw'] ?? '') ? ($resp['raw']) : json_encode($resp['payload'] ?? []);
        $u = $db->prepare("UPDATE integration_messages SET status = ?, provider_message_sid = ?, provider_payload = ?, last_attempt_at = NOW(), updated_at = NOW() WHERE id = ?");
        $u->bind_param('sssi', $status, $sid, $pp, $messageId);
        $u->execute();
        $sent++; $results[] = ['id'=>$r['id'],'message_id'=>$messageId,'sid'=>$sid];
    } else {
        $err = $resp['error'] ?? 'send failed';
        $u = $db->prepare("UPDATE integration_messages SET status = 'failed', error_message = ? WHERE id = ?");
        $u->bind_param('si', $err, $messageId);
        $u->execute();
        $failed++; $results[] = ['id'=>$r['id'],'error'=>$err];
    }
}

AuditLog::log('BROADCAST_SENT', ['template'=>$template,'provider'=>$integrationProvider,'sent'=>$sent,'failed'=>$failed,'segment'=>$segment,'user_count'=>count($recipients)], $user['id']);

echo json_encode(['success'=>true,'sent'=>$sent,'failed'=>$failed,'results'=>$results]);
exit;

function getIntegrationSetting($db, $key) {
    $stmt = $db->prepare("SELECT `value` FROM integration_settings WHERE `key` = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ? $r['value'] : null;
}

# backend/scripts/test_broadcast.php
$path = 'webapp/backend/scripts/test_broadcast.php';
$content = <<<'PHP'
<?php
// Simple test script: broadcast 'reminder' to segment 'new' (mock provider expected if no Twilio creds)
$backend = getenv('BACKEND_URL') ?: 'http://127.0.0.1:8000';
$token = getenv('BACKEND_AUTH_TOKEN') ?: null;
$devBypass = getenv('DEV_AUTH_BYPASS') ?: null;
// include token also as query param for local dev servers that drop Authorization header
$url = rtrim($backend, '/') . '/api/admin/broadcast.php?action=send' . ($token ? '&token=' . urlencode($token) : '') . ($token ? '' : ($devBypass ? '&dev_bypass=1' : '&dev_bypass=1'));

// payload
$data = ['template'=>'reminder','segment'=>'new','provider'=>'mock'];

// Use PHP cURL for cross-platform reliability
$ch = curl_init($url);
$payload = json_encode($data);
$headers = [ 'Content-Type: application/json' ];
if ($token) $headers[] = 'Authorization: Bearer ' . $token;

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP: {$http}\n";
echo "Response:\n" . ($resp ?? '') . "\n";
if ($err) { echo "cURL error: {$err}\n"; exit(2); }
if ($http < 200 || $http >= 300) { exit(3); }
exit(0);
PHP;

@file_put_contents(__DIR__ . '/../../' . $path, $content);
