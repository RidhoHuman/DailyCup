<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json');

// Test token from error logs
$testToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJuYW1lIjoiYWRtaW4gdXNlciAgIiwiZW1haWwiOiJhZG1pbkBnbWFpbC5jb20iLCJyb2xlIjoiYWRtaW4iLCJpYXQiOjE3NzA5NzI2NTYsImV4cCI6MTc3MTA1OTA1Nn0.pm8pufe2ov7JC-ShoM7rJQiOAxGhbg46mlY-ON6NkD0';

echo "=== JWT Debug ===\n";
$jwtSecret = defined('JWT_SECRET') ? JWT_SECRET : (getenv('JWT_SECRET') ?: 'default-secret-key');
echo "JWT_SECRET used: " . $jwtSecret . "\n";

$verified = JWT::verify($testToken);
echo "Verification result: " . ($verified ? json_encode($verified) : 'NULL') . "\n";

$user = JWT::getUser();
echo "getUser() result: " . ($user ? json_encode($user) : 'NULL') . "\n";

// Generate a fresh token
$newPayload = ['user_id' => 1, 'name' => 'admin user', 'email' => 'admin@gmail.com', 'role' => 'admin'];
$newToken = JWT::generate($newPayload);
echo "\n=== New Token Generated ===\n";
echo "Token: $newToken\n";

// Test new token
$verified2 = JWT::verify($newToken);
echo "Verification of new token: " . ($verified2 ? json_encode($verified2) : 'NULL') . "\n";
