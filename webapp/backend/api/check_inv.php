<?php 
require_once __DIR__ . '/cors.php';
$key = 'xnd_development_SePxbPbTD5zpN2id2bcysjhdhUJbuqbU6HrxV28suzKKlrywvoKIpQwGGQr8j';
$orderId = 'ORD-1770743962-8035';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/v2/invoices?external_id=' . urlencode($orderId));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($key . ':')]);
$res = curl_exec($ch);
curl_close($ch);
print_r(json_decode($res, true));
