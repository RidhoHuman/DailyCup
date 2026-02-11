<?php
require_once __DIR__ . '/../api/jwt.php';

// Generate a short-lived admin token for CI tests
$payload = [
    'id' => 'ci',
    'role' => 'admin',
    'email' => 'ci@example.com'
];
// Optionally accept expiry override in seconds
$ttl = isset($argv[1]) ? intval($argv[1]) : 600; // 10 min default
// override JWT expiry temporarily
$reflection = new ReflectionClass('JWT');
$prop = $reflection->getProperty('expiry');
$prop->setAccessible(true);
$prop->setValue(null, $ttl);

$token = JWT::generate($payload);
echo $token;
