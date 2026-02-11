<?php
echo "=== JWT SECRET DEBUG ===\n\n";

echo "1. Loading webapp/backend/config/database.php\n";
require_once __DIR__ . '/webapp/backend/config/database.php';
echo "   JWT_SECRET defined: " . (defined('JWT_SECRET') ? 'YES' : 'NO') . "\n";
if (defined('JWT_SECRET')) {
    echo "   JWT_SECRET value: " . substr(JWT_SECRET, 0, 20) . "...\n";
}

echo "\n2. Testing JWT class\n";
require_once __DIR__ . '/webapp/backend/api/jwt.php';

// Generate a test token
$testPayload = ['user_id' => 1, 'role' => 'admin', 'email' => 'test@test.com'];
$testToken = JWT::generate($testPayload);
echo "   Test token generated: " . substr($testToken, 0, 30) . "...\n";

$decoded = JWT::verify($testToken);
echo "   Test token verified: " . ($decoded ? 'YES' : 'NO') . "\n";
if ($decoded) {
    echo "   Role: " . ($decoded['role'] ?? 'NOT SET') . "\n";
}

echo "\n3. Testing your actual token\n";
$yourToken = trim(file_get_contents(__DIR__ . '/token.txt'));
echo "   Token length: " . strlen($yourToken) . "\n";
$decoded2 = JWT::verify($yourToken);
echo "   Verified: " . ($decoded2 ? 'YES' : 'NO') . "\n";
if ($decoded2) {
    echo "   Payload: " . json_encode($decoded2, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   FAILED - Different JWT_SECRET!\n";
    
    // Decode without verifying to see payload
    $parts = explode('.', $yourToken);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
        echo "   Raw payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n4. Root config JWT_SECRET\n";
require_once __DIR__ . '/config/database.php';
echo "   JWT_SECRET: " . substr(JWT_SECRET, 0, 20) . "...\n";
