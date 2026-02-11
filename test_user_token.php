<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== Test User's Token ===" . PHP_EOL;

$userToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGdtYWlsLmNvbSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc3MDMxNTc5NywiZXhwIjoxNzcwNDAyMTk3fQ.wYSMkKlUlYy9cE7A02iJ2PSJWCP901azKeaTeNQgcGE";

echo "Token: " . substr($userToken, 0, 80) . "..." . PHP_EOL;
echo "JWT_SECRET being used: " . JWT_SECRET . PHP_EOL;
echo "JWT_SECRET length: " . strlen(JWT_SECRET) . PHP_EOL;

echo "\n=== Verification ===" . PHP_EOL;
$verified = verifyJWT($userToken);

if ($verified) {
    echo "✅ Verification: SUCCESS" . PHP_EOL;
    echo "User Data: " . json_encode($verified, JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "❌ Verification: FAILED" . PHP_EOL;
    echo "Check error.log for details" . PHP_EOL;
}

// Also test with direct JWT::decode
echo "\n=== Direct JWT Decode Test ===" . PHP_EOL;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

try {
    $decoded = JWT::decode($userToken, new Key(JWT_SECRET, 'HS256'));
    echo "✅ Direct decode: SUCCESS" . PHP_EOL;
    echo "Decoded: " . json_encode($decoded, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Direct decode: FAILED" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
