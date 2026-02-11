<?php
// Simple test script: broadcast 'reminder' to segment 'new' (mock provider expected if no Twilio creds)
$backend = getenv('BACKEND_URL') ?: 'http://127.0.0.1:8000';
// include token also as query param for local dev servers that drop Authorization header
$token = getenv('BACKEND_AUTH_TOKEN') ?: null;
$devBypass = getenv('DEV_AUTH_BYPASS') ?: null;
$url = rtrim($backend, '/') . '/api/admin/broadcast.php?action=send' . ($token ? '&token=' . urlencode($token) : '') . ($token ? '' : ($devBypass ? '&dev_bypass=1' : '&dev_bypass=1')); // default to dev bypass when no token
$data = ['template'=>'reminder','segment'=>'new','provider'=>'mock'];
$token = getenv('BACKEND_AUTH_TOKEN') ?: null;

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
