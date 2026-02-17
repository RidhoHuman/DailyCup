<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

echo "=== Debug Auth ===\n";
echo "JWT_SECRET: " . (defined('JWT_SECRET') ? JWT_SECRET : 'NOT DEFINED') . "\n";
echo "getenv JWT_SECRET: " . getenv('JWT_SECRET') . "\n";

// Check Authorization header
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
echo "Authorization header: " . ($authHeader ? substr($authHeader, 0, 50) . '...' : 'NOT FOUND') . "\n";

$user = validateToken();
echo "validateToken result: " . ($user ? json_encode($user) : 'NULL') . "\n";
