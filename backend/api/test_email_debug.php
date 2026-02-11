<?php
/**
 * Debug Email Sending - Show ALL errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment manually
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . "=" . trim($value));
        }
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';

// Force direct sending with debug
EmailService::setUseQueue(false);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Debug</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 8px; color: #2e7d32; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 8px; color: #c62828; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; color: #1565c0; margin: 10px 0; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        input, select { padding: 10px; width: 100%; font-size: 16px; border: 2px solid #ddd; border-radius: 5px; margin: 10px 0; }
        button { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Email Debug Test</h1>
";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if (!$recipientEmail) {
        echo "<div class='error'>❌ Email tidak valid!</div>";
    } else {
        echo "<h2>Step 1: Environment Check</h2>";
        echo "<div class='info'>";
        echo "SMTP_HOST: " . (getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST'] ?? 'NOT SET') . "<br>";
        echo "SMTP_PORT: " . (getenv('SMTP_PORT') ?: $_ENV['SMTP_PORT'] ?? 'NOT SET') . "<br>";
        echo "SMTP_USERNAME: " . (getenv('SMTP_USERNAME') ?: $_ENV['SMTP_USERNAME'] ?? 'NOT SET') . "<br>";
        echo "SMTP_FROM_EMAIL: " . (getenv('SMTP_FROM_EMAIL') ?: $_ENV['SMTP_FROM_EMAIL'] ?? 'NOT SET') . "<br>";
        echo "</div>";
        
        echo "<h2>Step 2: Template Check</h2>";
        $templatePath = __DIR__ . '/templates/email/payment_confirmation.html';
        if (file_exists($templatePath)) {
            echo "<div class='success'>✅ Template found: $templatePath</div>";
            $templateSize = filesize($templatePath);
            echo "<div class='info'>Template size: " . number_format($templateSize) . " bytes</div>";
        } else {
            echo "<div class='error'>❌ Template NOT found: $templatePath</div>";
        }
        
        echo "<h2>Step 3: Prepare Order Data</h2>";
        $orderNumber = 'DEBUG-' . time();
        $testOrder = [
            'order_number' => $orderNumber,
            'total_amount' => 75000,
            'total' => 75000,
            'payment_method' => 'Xendit QRIS',
            'created_at' => date('Y-m-d H:i:s'),
            'items' => [
                ['name' => 'Latte', 'quantity' => 2, 'price' => 25000],
                ['name' => 'Espresso', 'quantity' => 1, 'price' => 25000]
            ]
        ];
        
        $testCustomer = [
            'name' => 'Debug Test',
            'email' => $recipientEmail
        ];
        
        echo "<div class='info'>";
        echo "Order Number: {$testOrder['order_number']}<br>";
        echo "Total: Rp " . number_format($testOrder['total'], 0, ',', '.') . "<br>";
        echo "Items: " . count($testOrder['items']) . "<br>";
        echo "Send to: {$testCustomer['email']}<br>";
        echo "</div>";
        
        echo "<h2>Step 4: Send Email with Full Error Reporting</h2>";
        
        try {
            echo "<pre style='background: #fff3cd; color: #856404; padding: 10px;'>Calling EmailService::sendPaymentConfirmation()...</pre>";
            
            ob_start();
            $sent = EmailService::sendPaymentConfirmation($testOrder, $testCustomer);
            $output = ob_get_clean();
            
            if ($output) {
                echo "<div class='info'><strong>Output:</strong><pre>" . htmlspecialchars($output) . "</pre></div>";
            }
            
            if ($sent) {
                echo "<div class='success'>";
                echo "<h3>✅ Email Sent Successfully!</h3>";
                echo "Check: $recipientEmail<br>";
                echo "Subject: Payment Received - $orderNumber<br>";
                echo "Tunggu 30-60 detik, cek Inbox dan SPAM!";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "❌ EmailService returned FALSE<br>";
                echo "Check PHP error log for details";
                echo "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>Exception Caught:</strong><br>";
            echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>File:</strong> " . $e->getFile() . "<br>";
            echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        } catch (Error $e) {
            echo "<div class='error'>";
            echo "<strong>Fatal Error:</strong><br>";
            echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "<strong>File:</strong> " . $e->getFile() . "<br>";
            echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
            echo "</div>";
        }
        
        // Check error log
        echo "<h2>Step 5: PHP Error Log (Last 20 lines)</h2>";
        $errorLog = ini_get('error_log');
        if (empty($errorLog)) {
            $errorLog = 'C:/laragon/www/error.log'; // Laragon default
        }
        
        if (file_exists($errorLog)) {
            $lines = file($errorLog);
            $lastLines = array_slice($lines, -20);
            echo "<pre style='max-height: 300px; overflow-y: auto;'>" . htmlspecialchars(implode('', $lastLines)) . "</pre>";
        } else {
            echo "<div class='info'>Error log not found at: $errorLog</div>";
        }
    }
    
    echo "<hr style='margin: 30px 0;'>";
}

// Form
echo "<h2>Test Email Debug</h2>";
echo "<form method='post'>";
echo "<label><strong>Email Tujuan:</strong></label>";
echo "<select name='email' required>";
echo "<option value='ariashop87@gmail.com'>ariashop87@gmail.com</option>";
echo "<option value='ridhoaria316@gmail.com'>ridhoaria316@gmail.com</option>";
echo "<option value='ridhohuman11@gmail.com'>ridhohuman11@gmail.com (asli)</option>";
echo "</select>";
echo "<button type='submit'>🐛 Debug Email Send</button>";
echo "</form>";

echo "</div>
</body>
</html>";
?>
