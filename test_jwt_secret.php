<?php
require_once __DIR__ . '/config/database.php';

echo "=== JWT_SECRET Check ===" . PHP_EOL;
echo "JWT_SECRET: " . JWT_SECRET . PHP_EOL;
echo "Length: " .  strlen(JWT_SECRET) . PHP_EOL;
echo "From .env: " . ($_ENV['JWT_SECRET'] ?? 'NOT SET') . PHP_EOL;

// Test JWT generation and verification
require_once __DIR__ . '/includes/functions.php';

echo "\n=== Test JWT Generation & Verification ===" . PHP_EOL;
$testUserId = 1;
$testPayload = [
    'user_id' => 1,
    'email' => 'admin@gmail.com',
    'role' => 'admin'
];

try {
    $token = generateJWT($testUserId, $testPayload);
    echo "Generated Token: " . substr($token, 0, 80) . "...\n";
    
    $verified = verifyJWT($token);
    echo "Verification Result: " . ($verified ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    if ($verified) {
        echo "User Data: " . json_encode($verified) . PHP_EOL;
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
