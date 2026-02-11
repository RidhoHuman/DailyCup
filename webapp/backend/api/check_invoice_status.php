<?php
// Quick check invoice status
$key = 'xnd_development_SePxbPbTD5zpN2id2bcysjhdhUJbuqbU6HrxV28suzKKlrywvoKIpQwGGQr8j';
$orderId = $_GET['order_id'] ?? 'ORD-1770743962-8035';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/v2/invoices?external_id=' . urlencode($orderId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($key . ':')]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
echo json_encode([
    'http_code' => $code,
    'order_id' => $orderId,
    'invoices' => json_decode($res, true)
], JSON_PRETTY_PRINT);
