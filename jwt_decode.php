<?php
require_once __DIR__ . '/webapp/backend/api/jwt.php';

$token = $argv[1] ?? '';

if (!$token) {
    echo "Usage: php jwt_decode.php <token>\n";
    exit(1);
}

try {
    $decoded = JWT::verify($token);
    echo "=== JWT DECODED ===\n\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT);
    echo "\n\nRole: " . ($decoded['role'] ?? 'NOT SET') . "\n";
    echo "Role check (admin): " . (($decoded['role'] ?? '') === 'admin' ? 'PASS' : 'FAIL') . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
