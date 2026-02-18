<?php
require_once __DIR__ . '/cors.php';
/**
 * Debug Webhook Test - Simplified
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Webhook Test</h1>";

// Step 1: Test config loading
echo "<h2>1. Config Loading</h2>";
require_once __DIR__ . '/config.php';
echo "✅ config.php loaded<br>";

// Step 2: Test environment variables
echo "<h2>2. Environment Variables</h2>";
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'dailycup_db';
$xenditKey = getenv('XENDIT_SECRET_KEY');
$smtpEnabled = getenv('SMTP_ENABLED');

echo "DB_HOST: " . ($dbHost ?: '(not set)') . "<br>";
echo "DB_NAME: " . ($dbName ?: '(not set)') . "<br>";
echo "XENDIT_SECRET_KEY: " . ($xenditKey ? '✅ Set ('.strlen($xenditKey).' chars)' : '❌ Not set') . "<br>";
echo "SMTP_ENABLED: " . ($smtpEnabled ?: '(not set)') . "<br>";

// Step 3: Test database connection
echo "<h2>3. Database Connection</h2>";
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($conn->connect_error) {
        echo "❌ Connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Connected to database: $dbName<br>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

// Step 4: Test EmailService
echo "<h2>4. EmailService</h2>";
try {
    require_once __DIR__ . '/email/EmailService.php';
    echo "✅ EmailService loaded<br>";
    
    // Test template loading
    $templatePath = __DIR__ . '/templates/email/payment_confirmation.html';
    if (file_exists($templatePath)) {
        echo "✅ Template exists: payment_confirmation.html<br>";
    } else {
        echo "❌ Template NOT found at: $templatePath<br>";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}

// Step 5: Test simple email send (no actual send)
echo "<h2>5. Test Email Preparation</h2>";
try {
    $testOrder = [
        'order_number' => 'TEST-123',
        'total' => 85000,
        'items' => [
            ['name' => 'Cappuccino', 'price' => 35000, 'quantity' => 2]
        ],
        'payment_method' => 'xendit'
    ];
    
    $testCustomer = [
        'name' => 'Test User',
        'email' => 'test@example.com'
    ];
    
    // Load template directly
    $data = [
        'customer_name' => $testCustomer['name'],
        'order_number' => $testOrder['order_number'],
        'payment_date' => date('d F Y, H:i'),
        'items_html' => '<tr><td>Cappuccino</td><td>2</td><td>Rp 70.000</td></tr>',
        'total_paid' => 'Rp 85.000',
        'payment_method' => 'Xendit',
        'order_url' => 'http://localhost:3000/orders/TEST-123'
    ];
    
    $html = EmailService::loadTemplate('payment_confirmation', $data);
    
    if (!empty($html)) {
        echo "✅ Template loaded successfully (" . strlen($html) . " chars)<br>";
        echo "<details><summary>Preview HTML</summary><iframe srcdoc='" . htmlspecialchars($html) . "' style='width:100%;height:300px;border:1px solid #ccc;'></iframe></details>";
    } else {
        echo "❌ Template returned empty<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>✅ Debug Complete</h2>";
echo "<a href='test_webhook.php'>← Back to Test Webhook</a>";
