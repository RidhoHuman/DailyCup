<?php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../../../includes/functions.php'; // optional helpers
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/audit_log.php';
require_once __DIR__ . '/../lib/sms_providers.php';

$segment = $argv[1] ?? 'new';
$template = $argv[2] ?? 'reminder';

$templates = [
    'gajian' => "Halo, promo gajian dari DailyCup! Nikmati diskon 20% untuk semua item. Buruan buruan!",
    'reminder' => "Hai! Keranjangmu menunggu. Lengkapi checkout dan dapatkan voucher Rp10.000. Klik sekarang!",
];

if (!isset($templates[$template])) {
    echo "Unknown template: $template\n"; exit(2);
}
$bodyTemplate = $templates[$template];

$db = Database::getConnection(); // mysqli

// Resolve recipients by segment
$recipients = [];
if ($segment === 'new') {
    $q = $db->prepare("SELECT id, name, phone, email FROM users WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
    $q->execute(); $res = $q->get_result(); while ($r = $res->fetch_assoc()) $recipients[] = $r;
} elseif ($segment === 'vip') {
    $q = $db->prepare("SELECT u.id, u.name, u.phone, u.email, COALESCE(SUM(o.total),0) as total_spend FROM users u LEFT JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled' GROUP BY u.id HAVING total_spend > 500000");
    $q->execute(); $res = $q->get_result(); while ($r = $res->fetch_assoc()) $recipients[] = $r;
} else {
    $q = $db->prepare("SELECT id, name, phone, email FROM users LIMIT 100");
    $q->execute(); $res = $q->get_result(); while ($r = $res->fetch_assoc()) $recipients[] = $r;
}

if (empty($recipients)) { echo "No recipients for segment $segment\n"; exit(0); }

$sent = 0; $failed = 0;
foreach ($recipients as $r) {
    $to = $r['phone'] ?? null;
    if (!$to) { $failed++; continue; }
    $body = str_replace('{name}', $r['name'] ?? '', $bodyTemplate);
    $params = ['to'=>'whatsapp:+1000000000','from'=>'whatsapp:+1000000000','body'=>$body,'account_sid'=>'MOCK','auth_token'=>'MOCK'];
    $resp = send_sms_via_provider('mock', $params);
    if ($resp['success']) { $sent++; } else { $failed++; }
}

echo "Sent: $sent, Failed: $failed\n";
exit(0);
