<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../audit_log.php';
require_once __DIR__ . '/../../lib/sms_providers.php';

header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess) — removed duplicate Access-Control-Allow-Origin

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? null;
if ($action !== 'send') { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$provider = $data['provider'] ?? 'twilio';
$to = $data['to'] ?? null;
$body = $data['body'] ?? '';
$from = $data['from'] ?? null;

if (!$to || !$body) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'`to` and `body` required']); exit; }

// Determine provider-specific defaults
if ($provider === 'twilio' && !$from) {
    $from = getIntegrationSetting($db, 'twilio_whatsapp_from');
}

// Insert into integration_messages
$stmt = $db->prepare("INSERT INTO integration_messages (provider, channel, direction, to_number, from_number, body, status) VALUES (?, ?, 'outbound', ?, ?, ?, 'queued')");
$channel = ($provider === 'twilio') ? 'whatsapp' : 'sms';
$stmt->bind_param('sssss', $provider, $channel, $to, $from, $body);
$stmt->execute();
$messageId = $stmt->insert_id;

// Send via adapter
$accountSid = getIntegrationSetting($db, 'twilio_account_sid');
$authToken = getIntegrationSetting($db, 'twilio_auth_token');
$params = ['to'=>$to,'from'=>$from,'body'=>$body,'account_sid'=>$accountSid,'auth_token'=>$authToken];
$providerResp = send_sms_via_provider($provider, $params);

if ($providerResp['success']) {
    $sid = $providerResp['sid'] ?? null;
    $status = $providerResp['status'] ?? 'sent';
    $pp = is_string($providerResp['raw'] ?? '') ? ($providerResp['raw']) : json_encode($providerResp['payload'] ?? []);
    $u = $db->prepare("UPDATE integration_messages SET status = ?, provider_message_sid = ?, provider_payload = ?, last_attempt_at = NOW(), updated_at = NOW() WHERE id = ?");
    $u->bind_param('sssi', $status, $sid, $pp, $messageId);
    $u->execute();

    AuditLog::log('INTEGRATION_MESSAGE_SENT', ['id'=>$messageId,'provider'=>$provider,'sid'=>$sid,'to'=>$to], $user['id']);
    echo json_encode(['success'=>true,'sid'=>$sid,'provider_payload'=>$providerResp['payload'] ?? null]);
    exit;
} else {
    $err = $providerResp['error'] ?? 'Unknown provider error';
    $u = $db->prepare("UPDATE integration_messages SET status = 'failed', error_message = ? WHERE id = ?");
    $u->bind_param('si', $err, $messageId);
    $u->execute();

    AuditLog::logApiError('/integrations/send.php?action=send', $err, $providerResp['http'] ?? 500);
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$err,'raw'=>$providerResp]);
    exit;
}

// Helper to read integration settings (mysqli style)
function getIntegrationSetting($db, $key) {
    $stmt = $db->prepare("SELECT `value` FROM integration_settings WHERE `key` = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return $r ? $r['value'] : null;
}
