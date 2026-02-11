<?php
/**
 * Test Email Sending Directly (Bypass Queue)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';

// Force direct sending (no queue)
EmailService::setUseQueue(false);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Direct Email</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 8px; color: #2e7d32; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 8px; color: #c62828; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; color: #1565c0; margin: 10px 0; }
        button { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        button:hover { background: #1565c0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test Direct Email Sending</h1>
        <p>Testing email delivery bypassing queue system</p>
";

// Test 1: Simple payment confirmation email
echo "<h2>Test 1: Payment Confirmation Email</h2>";

$testOrder = [
    'order_number' => 'TEST-' . time(),
    'total_amount' => 45000,
    'total' => 45000,
    'payment_method' => 'Xendit QRIS',
    'created_at' => date('Y-m-d H:i:s'),
    'items' => [
        [
            'name' => 'Espresso',
            'quantity' => 2,
            'price' => 15000
        ],
        [
            'name' => 'Cappuccino',
            'quantity' => 1,
            'price' => 25000
        ]
    ]
];

$testCustomer = [
    'name' => 'Ridho Test',
    'email' => 'ridhohuman11@gmail.com'
];

echo "<div class='info'>";
echo "<strong>Sending to:</strong> {$testCustomer['email']}<br>";
echo "<strong>Order:</strong> {$testOrder['order_number']}<br>";
echo "<strong>Total:</strong> Rp " . number_format($testOrder['total'], 0, ',', '.') . "<br>";
echo "</div>";

try {
    echo "<p>⏳ Sending email...</p>";
    
    $sent = EmailService::sendPaymentConfirmation($testOrder, $testCustomer);
    
    if ($sent) {
        echo "<div class='success'>";
        echo "✅ <strong>Email sent successfully!</strong><br>";
        echo "Check inbox: {$testCustomer['email']}<br>";
        echo "Don't forget to check SPAM folder!";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "❌ <strong>Email sending failed</strong><br>";
        echo "Check error log for details";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

// Test 2: Check email queue
echo "<h2>Test 2: Email Queue Status</h2>";

try {
    require_once __DIR__ . '/email/EmailQueue.php';
    
    $pending = EmailQueue::getPending();
    
    echo "<div class='info'>";
    echo "<strong>Queue count:</strong> " . count($pending) . " email(s)<br>";
    echo "</div>";
    
    if (!empty($pending)) {
        echo "<h3>Queued Emails:</h3>";
        echo "<table border='1' cellpadding='10' style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background:#e3f2fd;'>
                <th>ID</th>
                <th>To</th>
                <th>Subject</th>
                <th>Created</th>
                <th>Attempts</th>
                <th>Status</th>
              </tr>";
        
        foreach ($pending as $email) {
            $rowStyle = $email['status'] === 'failed' ? 'background:#ffebee;' : '';
            echo "<tr style='$rowStyle'>
                    <td>{$email['id']}</td>
                    <td>{$email['recipient']}</td>
                    <td>" . htmlspecialchars(substr($email['subject'], 0, 50)) . "</td>
                    <td>{$email['created_at']}</td>
                    <td>{$email['attempts']}</td>
                    <td>{$email['status']}</td>
                  </tr>";
        }
        
        echo "</table>";
        
        // Button to process queue
        echo "<form method='post' style='margin-top:20px;'>";
        echo "<button type='submit' name='process_queue'>🚀 Process Queue Now</button>";
        echo "</form>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Error checking queue: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Process queue if requested
if (isset($_POST['process_queue'])) {
    echo "<h3>Processing Queue...</h3>";
    try {
        require_once __DIR__ . '/email/process_queue.php';
        echo "<div class='success'>✅ Queue processing completed! Check results above.</div>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    } catch (Exception $e) {
        echo "<div class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Test 3: SMTP Configuration
echo "<h2>Test 3: SMTP Configuration</h2>";
echo "<div class='info'>";
echo "<strong>SMTP Host:</strong> " . (getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST'] ?? 'Not set') . "<br>";
echo "<strong>SMTP Port:</strong> " . (getenv('SMTP_PORT') ?: $_ENV['SMTP_PORT'] ?? 'Not set') . "<br>";
echo "<strong>SMTP Username:</strong> " . (getenv('SMTP_USERNAME') ?: $_ENV['SMTP_USERNAME'] ?? 'Not set') . "<br>";
echo "<strong>SMTP Encryption:</strong> " . (getenv('SMTP_ENCRYPTION') ?: $_ENV['SMTP_ENCRYPTION'] ?? 'Not set') . "<br>";
echo "<strong>Email Enabled:</strong> " . (getenv('SMTP_ENABLED') ?: $_ENV['SMTP_ENABLED'] ?? 'false') . "<br>";
echo "</div>";

echo "</div>
</body>
</html>";
?>
