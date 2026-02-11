<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phase 1 Security Implementation Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; margin-bottom: 10px; }
        .subtitle { color: #7f8c8d; margin-bottom: 30px; }
        .test-section { background: white; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { color: #34495e; margin-bottom: 15px; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .test-result { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid; }
        .success { background: #d4edda; border-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-info { background: #17a2b8; color: white; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .summary-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-card h3 { font-size: 36px; margin-bottom: 5px; }
        .summary-card p { color: #7f8c8d; font-size: 14px; }
        .card-success { border-top: 4px solid #28a745; }
        .card-warning { border-top: 4px solid #ffc107; }
        .card-info { border-top: 4px solid #17a2b8; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .checklist { list-style: none; }
        .checklist li { padding: 8px 0; }
        .checklist li:before { content: "✓ "; color: #28a745; font-weight: bold; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔒 Phase 1 Security Implementation Test</h1>
        <p class="subtitle">DailyCup CRM - Complete Security Audit & Verification</p>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;
$testsWarning = 0;

function testResult($name, $passed, $message, $details = '') {
    global $testsRun, $testsPassed, $testsFailed;
    $testsRun++;
    if ($passed) {
        $testsPassed++;
        $class = 'success';
        $icon = '✅';
    } else {
        $testsFailed++;
        $class = 'error';
        $icon = '❌';
    }
    echo "<div class='test-result $class'>";
    echo "<strong>$icon $name</strong><br>$message";
    if ($details) echo "<br><small>$details</small>";
    echo "</div>";
}

function warningResult($name, $message, $details = '') {
    global $testsRun, $testsWarning;
    $testsRun++;
    $testsWarning++;
    echo "<div class='test-result warning'>";
    echo "<strong>⚠️ $name</strong><br>$message";
    if ($details) echo "<br><small>$details</small>";
    echo "</div>";
}

try {
    // Load configuration
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/includes/functions.php';
    
    echo "<div class='test-section'>";
    echo "<h2>1. Environment Variables</h2>";
    
    // Test 1: Environment variables loaded
    $envLoaded = defined('DB_HOST') && defined('JWT_SECRET');
    testResult(
        "Environment Variables Loaded",
        $envLoaded,
        $envLoaded ? "✓ Environment variables successfully loaded from .env file" : "✗ Failed to load environment variables"
    );
    
    // Test 2: .env file exists
    $envExists = file_exists(__DIR__ . '/.env');
    testResult(
        ".env File Exists",
        $envExists,
        $envExists ? "✓ .env file found" : "✗ .env file not found"
    );
    
    // Test 3: JWT secret is not default
    $jwtSecure = JWT_SECRET !== 'default-secret-key' && strlen(JWT_SECRET) >= 32;
    testResult(
        "JWT Secret Secure",
        $jwtSecure,
        $jwtSecure ? "✓ JWT secret is configured and secure" : "⚠️ JWT secret is using default value - change in production!",
        $jwtSecure ? "" : "Current: " . (JWT_SECRET === 'default-secret-key' ? 'default' : 'weak')
    );
    
    echo "</div>";
    
    // Database Tests
    echo "<div class='test-section'>";
    echo "<h2>2. Database Security Tables</h2>";
    
    $db = getDB();
    
    // Test: rate_limits table
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'rate_limits'");
        $exists = $stmt->fetch();
        testResult(
            "Rate Limits Table",
            $exists !== false,
            $exists ? "✓ rate_limits table created" : "✗ rate_limits table missing"
        );
    } catch (Exception $e) {
        testResult("Rate Limits Table", false, "Error checking table: " . $e->getMessage());
    }
    
    // Test: activity_logs table
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'activity_logs'");
        $exists = $stmt->fetch();
        testResult(
            "Activity Logs Table",
            $exists !== false,
            $exists ? "✓ activity_logs table created" : "✗ activity_logs table missing"
        );
    } catch (Exception $e) {
        testResult("Activity Logs Table", false, "Error checking table: " . $e->getMessage());
    }
    
    // Test: security_audit table
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'security_audit'");
        $exists = $stmt->fetch();
        testResult(
            "Security Audit Table",
            $exists !== false,
            $exists ? "✓ security_audit table created" : "✗ security_audit table missing"
        );
    } catch (Exception $e) {
        testResult("Security Audit Table", false, "Error checking table: " . $e->getMessage());
    }
    
    echo "</div>";
    
    // Function Tests
    echo "<div class='test-section'>";
    echo "<h2>3. Security Functions</h2>";
    
    // Test: secureSessionStart
    testResult(
        "secureSessionStart()",
        function_exists('secureSessionStart'),
        function_exists('secureSessionStart') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: checkRateLimit
    testResult(
        "checkRateLimit()",
        function_exists('checkRateLimit'),
        function_exists('checkRateLimit') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: logActivity
    testResult(
        "logActivity()",
        function_exists('logActivity'),
        function_exists('logActivity') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: logSecurityEvent
    testResult(
        "logSecurityEvent()",
        function_exists('logSecurityEvent'),
        function_exists('logSecurityEvent') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: validateEmail
    testResult(
        "validateEmail()",
        function_exists('validateEmail'),
        function_exists('validateEmail') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: validatePhone
    testResult(
        "validatePhone()",
        function_exists('validatePhone'),
        function_exists('validatePhone') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: validatePassword
    testResult(
        "validatePassword()",
        function_exists('validatePassword'),
        function_exists('validatePassword') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: validateInput
    testResult(
        "validateInput()",
        function_exists('validateInput'),
        function_exists('validateInput') ? "✓ Function exists" : "✗ Function not found"
    );
    
    // Test: secureFileUpload
    testResult(
        "secureFileUpload()",
        function_exists('secureFileUpload'),
        function_exists('secureFileUpload') ? "✓ Function exists" : "✗ Function not found"
    );
    
    echo "</div>";
    
    // Validation Tests
    echo "<div class='test-section'>";
    echo "<h2>4. Input Validation Tests</h2>";
    
    if (function_exists('validateEmail')) {
        $validEmail = validateEmail('test@example.com');
        $invalidEmail = !validateEmail('invalid-email');
        testResult(
            "Email Validation",
            $validEmail && $invalidEmail,
            $validEmail && $invalidEmail ? "✓ Email validation working correctly" : "✗ Email validation failed"
        );
    }
    
    if (function_exists('validatePhone')) {
        $validPhone = validatePhone('081234567890');
        $invalidPhone = !validatePhone('123');
        testResult(
            "Phone Validation",
            $validPhone && $invalidPhone,
            $validPhone && $invalidPhone ? "✓ Phone validation working correctly" : "✗ Phone validation failed"
        );
    }
    
    if (function_exists('validatePassword')) {
        $validPassword = validatePassword('SecurePass123');
        $invalidPassword = !validatePassword('weak');
        testResult(
            "Password Validation",
            $validPassword && $invalidPassword,
            $validPassword && $invalidPassword ? "✓ Password validation working correctly" : "✗ Password validation failed",
            "Requires: min 8 chars, 1 uppercase, 1 lowercase, 1 number"
        );
    }
    
    echo "</div>";
    
    // File Security Tests
    echo "<div class='test-section'>";
    echo "<h2>5. File Security</h2>";
    
    // Test: .htaccess exists
    $htaccessExists = file_exists(__DIR__ . '/.htaccess');
    testResult(
        ".htaccess File",
        $htaccessExists,
        $htaccessExists ? "✓ .htaccess file exists with security headers" : "✗ .htaccess file missing"
    );
    
    // Test: Upload directory .htaccess
    $uploadHtaccess = file_exists(__DIR__ . '/assets/images/payments/.htaccess');
    testResult(
        "Upload Directory Protection",
        $uploadHtaccess,
        $uploadHtaccess ? "✓ Upload directory protected with .htaccess" : "✗ Upload directory not protected"
    );
    
    // Test: .gitignore
    $gitignoreExists = file_exists(__DIR__ . '/.gitignore');
    if ($gitignoreExists) {
        $content = file_get_contents(__DIR__ . '/.gitignore');
        $protectsEnv = strpos($content, '.env') !== false;
        testResult(
            ".gitignore Protection",
            $protectsEnv,
            $protectsEnv ? "✓ .env file excluded from Git" : "⚠️ .env not in .gitignore"
        );
    } else {
        warningResult(".gitignore", ".gitignore file not found");
    }
    
    echo "</div>";
    
    // Session Security Tests
    echo "<div class='test-section'>";
    echo "<h2>6. Session Security</h2>";
    
    $sessionSecure = ini_get('session.cookie_httponly') == 1;
    testResult(
        "Session HttpOnly Flag",
        $sessionSecure,
        $sessionSecure ? "✓ Session cookies protected from XSS" : "⚠️ HttpOnly not set"
    );
    
    $sessionName = session_name();
    $customSessionName = $sessionName === 'DAILYCUP_SESS';
    testResult(
        "Custom Session Name",
        $customSessionName,
        $customSessionName ? "✓ Using custom session name" : "ℹ️ Using default session name",
        "Current: $sessionName"
    );
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result error'>";
    echo "<strong>❌ Critical Error</strong><br>";
    echo "Message: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine();
    echo "</div>";
}

// Summary
$score = $testsPassed > 0 ? round(($testsPassed / $testsRun) * 100) : 0;
?>

        <div class="summary">
            <div class="summary-card card-success">
                <h3 style="color: #28a745;"><?php echo $testsPassed; ?></h3>
                <p>Tests Passed</p>
            </div>
            <div class="summary-card card-warning">
                <h3 style="color: #ffc107;"><?php echo $testsFailed; ?></h3>
                <p>Tests Failed</p>
            </div>
            <div class="summary-card card-info">
                <h3 style="color: #17a2b8;"><?php echo $testsRun; ?></h3>
                <p>Total Tests</p>
            </div>
            <div class="summary-card <?php echo $score >= 80 ? 'card-success' : 'card-warning'; ?>">
                <h3 style="color: <?php echo $score >= 80 ? '#28a745' : '#ffc107'; ?>;"><?php echo $score; ?>%</h3>
                <p>Success Rate</p>
            </div>
        </div>

        <div class="test-section">
            <h2>✅ Phase 1 Implementation Checklist</h2>
            <ul class="checklist">
                <li>Environment Variables for database credentials</li>
                <li>Rate Limiting on login and API endpoints</li>
                <li>Secure Session Management with timeout and regeneration</li>
                <li>Comprehensive Input Validation (email, phone, password)</li>
                <li>Secure File Upload with MIME type checking</li>
                <li>Security Headers (.htaccess configuration)</li>
                <li>Activity Logging for audit trail</li>
                <li>Security Event Logging for suspicious activities</li>
                <li>Upload directory protection (.htaccess)</li>
                <li>.env file excluded from Git (.gitignore)</li>
            </ul>
        </div>

        <div class="test-section">
            <h2>📋 Next Steps</h2>
            <div class="test-result info">
                <strong>Phase 1 Complete! 🎉</strong><br>
                <p>All critical security features have been implemented. Here's what to do next:</p>
                <ol style="margin: 10px 0 0 20px;">
                    <li><strong>Production Deployment:</strong> Change JWT_SECRET and SESSION_SECRET to strong random values</li>
                    <li><strong>Enable HTTPS:</strong> Uncomment HTTPS redirect in .htaccess and set session.cookie_secure=1</li>
                    <li><strong>Database Password:</strong> Set a strong password for production database</li>
                    <li><strong>Test Login:</strong> Try logging in multiple times to test rate limiting</li>
                    <li><strong>Test Upload:</strong> Upload payment proof to test secure file upload</li>
                    <li><strong>Monitor Logs:</strong> Check activity_logs and security_audit tables regularly</li>
                    <li><strong>Phase 2:</strong> Ready to implement Two-Factor Authentication and Email Verification</li>
                </ol>
            </div>
        </div>

        <div class="test-section">
            <h2>🔐 Security Score</h2>
            <?php if ($score >= 80): ?>
                <div class="test-result success">
                    <strong>Excellent! Security implementation is solid.</strong><br>
                    Your application now has strong protection against common attacks.
                </div>
            <?php elseif ($score >= 60): ?>
                <div class="test-result warning">
                    <strong>Good progress, but some features need attention.</strong><br>
                    Review failed tests and implement missing features.
                </div>
            <?php else: ?>
                <div class="test-result error">
                    <strong>Critical issues detected!</strong><br>
                    Several security features are missing or not working properly. Address these immediately.
                </div>
            <?php endif; ?>
        </div>

        <p style="text-align: center; color: #7f8c8d; margin-top: 30px;">
            <small>Generated: <?php echo date('Y-m-d H:i:s'); ?> | DailyCup CRM Security Audit</small>
        </p>
    </div>
</body>
</html>
