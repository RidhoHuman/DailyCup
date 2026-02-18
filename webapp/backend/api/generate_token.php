<?php
// Force load .env first
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        $val = preg_replace('/^"(.*)"$/', '$1', $val);
        $val = preg_replace("/^'(.*)'$/", '$1', $val);
        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json');

// Generate fresh token
$payload = [
    'user_id' => 1,
    'name' => 'admin user',
    'email' => 'admin@gmail.com',
    'role' => 'admin'
];
$token = JWT::generate($payload);

// Verify it works
$verified = JWT::verify($token);

echo json_encode([
    'success' => true,
    'token' => $token,
    'verified' => $verified
], JSON_PRETTY_PRINT);
