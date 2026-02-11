<?php
/**
 * Test Token Verification dengan JWT::verify()
 * 
 * Paste token dari browser localStorage dan test langsung
 */

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, '"\'');
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

require_once __DIR__ . '/api/jwt.php';

echo "===========================================\n";
echo "🔐 JWT TOKEN VERIFIER\n";
echo "===========================================\n\n";

// Check JWT_SECRET
$jwtSecret = getenv('JWT_SECRET');
echo "1️⃣ JWT_SECRET Loaded: " . ($jwtSecret ? 'YES ✅' : 'NO ❌') . "\n";
echo "   Value: " . substr($jwtSecret, 0, 20) . "...\n\n";

// Test token from user's request
$testToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGdtYWlsLmNvbSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc3MDMyND k0NSwiZXhwIjoxNzcwNDExMzQ1fQ.MmBzgOVyHEP77zNvngyKvpBTlWmemmeyWb_PZMM02mc';

echo "2️⃣ Testing Token:\n";
echo "   Token: " . substr($testToken, 0, 50) . "...\n\n";

// Verify with JWT::verify()
echo "3️⃣ JWT::verify() Result:\n";
$user = JWT::verify($testToken);

if ($user) {
    echo "   ✅ SUCCESS! Token verified!\n";
    echo "   User ID: " . $user['user_id'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Role: " . $user['role'] . "\n";
    echo "   Issued At: " . date('Y-m-d H:i:s', $user['iat']) . "\n";
    echo "   Expires At: " . date('Y-m-d H:i:s', $user['exp']) . "\n";
    echo "   Is Admin: " . ($user['role'] === 'admin' ? 'YES ✅' : 'NO ❌') . "\n";
    
    // Check expiry
    if ($user['exp'] < time()) {
        echo "   ⚠️ WARNING: Token is EXPIRED!\n";
        echo "   Expired: " . round((time() - $user['exp']) / 3600, 1) . " hours ago\n";
    } else {
        echo "   ⏰ Valid for: " . round(($user['exp'] - time()) / 3600, 1) . " more hours\n";
    }
} else {
    echo "   ❌ FAILED! Token verification failed!\n";
    echo "   Possible reasons:\n";
    echo "   - Wrong JWT_SECRET\n";
    echo "   - Token expired\n";
    echo "   - Token format invalid\n";
    echo "   - Token signature invalid\n";
}

echo "\n===========================================\n";
echo "📝 INSTRUCTIONS:\n";
echo "===========================================\n";
echo "1. Get your current token from browser:\n";
echo "   - Open DevTools (F12)\n";
echo "   - Console tab\n";
echo "   - Run: localStorage.getItem('dailycup-auth')\n";
echo "   - Copy the token value\n\n";
echo "2. Replace \$testToken in this file\n";
echo "3. Run: php " . basename(__FILE__) . "\n";
echo "===========================================\n";
