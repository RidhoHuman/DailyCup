<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== Test NEW Token (after re-login) ===" . PHP_EOL;

$newToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGdtYWlsLmNvbSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc3MDMyMTU5NCwiZXhwIjoxNzcwNDA3OTk0fQ.5rcXumL-Z7KC1Rm6b_kd_FbAhwFA7B0Y_nKK8h46R_w";

echo "Token (first 80 chars): " . substr($newToken, 0, 80) . "..." . PHP_EOL;
echo "JWT_SECRET: " . JWT_SECRET . PHP_EOL;
echo "JWT_SECRET Length: " . strlen(JWT_SECRET) . PHP_EOL;

echo "\n=== DECODING TOKEN ===" . PHP_EOL;
$parts = explode('.', $newToken);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
echo "Decoded Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . PHP_EOL;

echo "\n=== VERIFICATION WITH verifyJWT() ===" . PHP_EOL;
$verified = verifyJWT($newToken);

if ($verified) {
    echo "✅ Verification: SUCCESS" . PHP_EOL;
    echo "User Data: " . json_encode($verified, JSON_PRETTY_PRINT) . PHP_EOL;
    echo "\n=== ROLE CHECK ===" . PHP_EOL;
    echo "Has 'role' key: " . (isset($verified['role']) ? 'YES' : 'NO') . PHP_EOL;
    echo "Role value: " . ($verified['role'] ?? 'NULL') . PHP_EOL;
    echo "Role === 'admin': " . (($verified['role'] ?? '') === 'admin' ? 'YES ✅' : 'NO ❌') . PHP_EOL;
} else {
    echo "❌ Verification: FAILED" . PHP_EOL;
}

echo "\n=== TEST WITH webapp/backend/api/jwt.php JWT class ===" . PHP_EOL;
require_once __DIR__ . '/webapp/backend/api/jwt.php';

$verified2 = JWT::verify($newToken);
if ($verified2) {
    echo "✅ JWT::verify(): SUCCESS" . PHP_EOL;
    echo "User Data: " . json_encode($verified2, JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "❌ JWT::verify(): FAILED" . PHP_EOL;
}
