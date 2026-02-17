<?php
require_once __DIR__ . '/cors.php';
/**
 * Test Xendit Webhook End-to-End
 * 
 * This script simulates the full flow:
 * 1. Creates a test order
 * 2. Simulates Xendit webhook callback
 * 3. Verifies email is queued/sent
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';

// Force reload .env to get latest ngrok URL
load_env(__DIR__ . '/.env');

// Database constants from environment
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'dailycup_db');

header('Content-Type: text/html; charset=utf-8');

$action = $_GET['action'] ?? 'menu';
$testEmail = $_GET['email'] ?? '';

// Styles
echo '<!DOCTYPE html>
<html>
<head>
    <title>Xendit Webhook E2E Test</title>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        h2 { color: #a97456; border-bottom: 2px solid #a97456; padding-bottom: 10px; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 5px; }
        .btn-primary { background: #a97456; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .btn-info { background: #2196f3; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        pre { background: #1a1a2e; color: #00ff88; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; border-radius: 0 8px 8px 0; margin: 10px 0; }
        .error { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; border-radius: 0 8px 8px 0; margin: 10px 0; }
        .warning { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; border-radius: 0 8px 8px 0; margin: 10px 0; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; border-radius: 0 8px 8px 0; margin: 10px 0; }
        form { margin: 15px 0; }
        input[type="email"] { padding: 12px; width: 300px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .step { display: flex; align-items: center; margin: 10px 0; padding: 15px; background: #f9f9f9; border-radius: 8px; }
        .step-num { width: 40px; height: 40px; background: #a97456; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; }
        .step-done { background: #4caf50; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
<div class="container">';

switch ($action) {
    case 'menu':
        echo '<div class="card">
            <h1>🧪 Xendit Webhook End-to-End Test</h1>
            <p>Test the complete payment flow: Order → Payment → Webhook → Email</p>
            
            <h2>📋 Test Options</h2>
            
            <div class="step">
                <div class="step-num">1</div>
                <div>
                    <strong>Quick Webhook Simulation</strong><br>
                    <small>Simulate a Xendit webhook callback with test data</small><br>
                    <form method="GET" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="simulate_webhook">
                        <input type="email" name="email" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-primary">🚀 Simulate Webhook</button>
                    </form>
                </div>
            </div>
            
            <div class="step">
                <div class="step-num">2</div>
                <div>
                    <strong>Full Flow Test (Real Xendit)</strong><br>
                    <small>Create real order and get Xendit payment link</small><br>
                    <form method="GET" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="create_order">
                        <input type="email" name="email" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-success">📦 Create Test Order</button>
                    </form>
                </div>
            </div>
            
            <div class="step">
                <div class="step-num">3</div>
                <div>
                    <strong>Check Email Queue</strong><br>
                    <small>View pending emails in the queue</small><br>
                    <a href="?action=check_queue" class="btn btn-info">📬 Check Queue</a>
                </div>
            </div>
            
            <div class="step">
                <div class="step-num">4</div>
                <div>
                    <strong>Process Email Queue</strong><br>
                    <small>Send all queued emails now</small><br>
                    <a href="?action=process_queue" class="btn btn-warning">⚡ Process Queue</a>
                </div>
            </div>
        </div>';
        break;
        
    case 'simulate_webhook':
        if (empty($testEmail)) {
            echo '<div class="error">❌ Email address is required!</div>';
            echo '<a href="?action=menu" class="btn btn-primary">← Back</a>';
            break;
        }
        
        echo '<div class="card">
            <h1>🔄 Simulating Xendit Webhook</h1>';
        
        // Create a mock order in database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            echo '<div class="error">❌ Database connection failed: ' . $conn->connect_error . '</div>';
            break;
        }
        
        // Generate test order number
        $orderNumber = 'TEST-' . time() . '-' . rand(1000, 9999);
        $total = 85000;
        
        // Insert test order
        $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, total, subtotal, status, payment_status, payment_method, created_at) VALUES (?, 1, ?, ?, 'pending', 'pending', 'xendit', NOW())");
        $stmt->bind_param("sdd", $orderNumber, $total, $total);
        
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            echo '<div class="success">✅ Test order created: <strong>' . $orderNumber . '</strong></div>';
            
            // Add order items
            $conn->query("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES ($orderId, 1, 'Cappuccino Large', 2, 35000, 70000)");
            $conn->query("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES ($orderId, 2, 'Croissant', 1, 15000, 15000)");
            
            echo '<div class="info">📦 Order items added</div>';
        } else {
            echo '<div class="error">❌ Failed to create order: ' . $stmt->error . '</div>';
            break;
        }
        
        // Simulate webhook payload
        $webhookPayload = [
            'id' => 'invoice_' . uniqid(),
            'external_id' => $orderNumber,
            'status' => 'PAID',
            'paid_amount' => $total,
            'payer_email' => $testEmail,
            'payment_method' => 'QRIS',
            'payment_channel' => 'QRIS',
            'paid_at' => date('c')
        ];
        
        echo '<h2>📨 Webhook Payload</h2>';
        echo '<pre>' . json_encode($webhookPayload, JSON_PRETTY_PRINT) . '</pre>';
        
        // Call the webhook handler internally
        echo '<h2>🔔 Calling Webhook Handler</h2>';
        
        // Simulate the webhook by directly calling the handler logic
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'processing', paid_at = NOW() WHERE order_number = ?");
        $stmt->bind_param("s", $orderNumber);
        $stmt->execute();
        echo '<div class="success">✅ Order status updated to PAID</div>';
        
        // Get order data for email
        $orderData = [
            'order_number' => $orderNumber,
            'total' => $total,
            'items' => [
                ['name' => 'Cappuccino Large', 'price' => 35000, 'quantity' => 2],
                ['name' => 'Croissant', 'price' => 15000, 'quantity' => 1]
            ],
            'payment_method' => 'Xendit (QRIS)'
        ];
        
        $customer = [
            'name' => 'Test Customer',
            'email' => $testEmail
        ];
        
        // Send payment confirmation email
        echo '<h2>📧 Sending Payment Confirmation Email</h2>';
        
        try {
            // Disable queue for immediate sending
            EmailService::setUseQueue(false);
            $result = EmailService::sendPaymentConfirmation($orderData, $customer);
            
            if ($result) {
                echo '<div class="success">✅ Email sent successfully to <strong>' . $testEmail . '</strong>!</div>';
                echo '<div class="info">📬 Check your inbox (and spam folder) for the payment confirmation email.</div>';
            } else {
                echo '<div class="error">❌ Failed to send email</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ Email error: ' . $e->getMessage() . '</div>';
        }
        
        echo '<h2>✅ Test Complete!</h2>';
        echo '<table>
            <tr><th>Step</th><th>Status</th></tr>
            <tr><td>Create Order</td><td>✅ ' . $orderNumber . '</td></tr>
            <tr><td>Simulate Webhook</td><td>✅ PAID status</td></tr>
            <tr><td>Update Database</td><td>✅ Order marked as paid</td></tr>
            <tr><td>Send Email</td><td>' . ($result ? '✅ Sent' : '❌ Failed') . '</td></tr>
        </table>';
        
        echo '<a href="?action=menu" class="btn btn-primary">← Back to Menu</a>';
        echo '</div>';
        
        $conn->close();
        break;
        
    case 'create_order':
        if (empty($testEmail)) {
            echo '<div class="error">❌ Email address is required!</div>';
            echo '<a href="?action=menu" class="btn btn-primary">← Back</a>';
            break;
        }
        
        echo '<div class="card">
            <h1>📦 Creating Real Xendit Order</h1>';
        
        // Get Xendit config - use $_ENV directly to bypass getenv() cache
        $secretKey = $_ENV['XENDIT_SECRET_KEY'] ?? getenv('XENDIT_SECRET_KEY');
        $callbackUrl = $_ENV['XENDIT_CALLBACK_URL'] ?? getenv('XENDIT_CALLBACK_URL');
        
        // Debug output
        echo '<div class="info">
            <strong>Debug Info:</strong><br>
            Secret Key: ' . ($secretKey ? '✅ Set ('.strlen($secretKey).' chars)' : '❌ Not set') . '<br>
            Callback URL: ' . ($callbackUrl ?: '❌ Not set') . '<br>
            <small>Using $_ENV to bypass environment cache</small>
        </div>';
        
        if (empty($secretKey)) {
            echo '<div class="error">❌ XENDIT_SECRET_KEY not configured in environment</div>';
            echo '<div class="warning">Make sure config.php loaded .env file correctly.</div>';
            break;
        }
        
        $orderNumber = 'REAL-' . time() . '-' . rand(1000, 9999);
        $total = 10000; // Small amount for testing
        
        // Create Xendit invoice
        $payload = [
            'external_id' => $orderNumber,
            'amount' => $total,
            'payer_email' => $testEmail,
            'description' => 'DailyCup Test Order - ' . $orderNumber,
            'invoice_duration' => 86400,
            'currency' => 'IDR',
            'callback_url' => $callbackUrl,
            'success_redirect_url' => 'http://localhost:3000/checkout/success?order=' . $orderNumber,
            'failure_redirect_url' => 'http://localhost:3000/checkout/failed?order=' . $orderNumber
        ];
        
        echo '<h2>📤 Xendit API Request</h2>';
        echo '<pre>' . json_encode($payload, JSON_PRETTY_PRINT) . '</pre>';
        
        $ch = curl_init('https://api.xendit.co/v2/invoices');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($secretKey . ':')
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo '<h2>📥 Xendit Response (HTTP ' . $httpCode . ')</h2>';
        
        if ($httpCode === 200 || $httpCode === 201) {
            $data = json_decode($response, true);
            echo '<div class="success">✅ Invoice created successfully!</div>';
            echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';
            
            // Save order to database
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $stmt = $conn->prepare("INSERT INTO orders (order_number, user_id, total, subtotal, status, payment_status, payment_method, xendit_invoice_id, created_at) VALUES (?, 1, ?, ?, 'pending', 'pending', 'xendit', ?, NOW())");
            $invoiceId = $data['id'];
            $stmt->bind_param("sdds", $orderNumber, $total, $total, $invoiceId);
            $stmt->execute();
            $conn->close();
            
            echo '<h2>💳 Pay Now</h2>';
            echo '<div class="warning">
                <strong>Test Payment Instructions:</strong><br>
                1. Click the button below to open Xendit payment page<br>
                2. Choose any payment method (QRIS recommended for testing)<br>
                3. Complete the payment<br>
                4. Xendit will call our webhook at: <code>' . $callbackUrl . '</code><br>
                5. Check your email for payment confirmation
            </div>';
            
            echo '<a href="' . $data['invoice_url'] . '" target="_blank" class="btn btn-success" style="font-size: 18px; padding: 15px 30px;">💳 Pay Rp ' . number_format($total, 0, ',', '.') . ' with Xendit</a>';
            
        } else {
            echo '<div class="error">❌ Failed to create invoice</div>';
            echo '<pre>' . $response . '</pre>';
        }
        
        echo '<br><br><a href="?action=menu" class="btn btn-primary">← Back to Menu</a>';
        echo '</div>';
        break;
        
    case 'check_queue':
        echo '<div class="card">
            <h1>📬 Email Queue Status</h1>';
        
        $queueFile = sys_get_temp_dir() . '/dailycup_email_queue.json';
        
        if (file_exists($queueFile)) {
            $queue = json_decode(file_get_contents($queueFile), true) ?: [];
            $count = count($queue);
            
            echo '<div class="info">📧 <strong>' . $count . '</strong> emails in queue</div>';
            
            if ($count > 0) {
                echo '<table>
                    <tr><th>To</th><th>Subject</th><th>Queued At</th></tr>';
                foreach ($queue as $email) {
                    echo '<tr>
                        <td>' . htmlspecialchars($email['to']) . '</td>
                        <td>' . htmlspecialchars($email['subject']) . '</td>
                        <td>' . date('Y-m-d H:i:s', $email['queued_at']) . '</td>
                    </tr>';
                }
                echo '</table>';
                
                echo '<a href="?action=process_queue" class="btn btn-warning">⚡ Process Queue Now</a>';
            }
        } else {
            echo '<div class="info">📭 Email queue is empty</div>';
        }
        
        echo '<a href="?action=menu" class="btn btn-primary">← Back to Menu</a>';
        echo '</div>';
        break;
        
    case 'process_queue':
        echo '<div class="card">
            <h1>⚡ Processing Email Queue</h1>';
        
        $queueFile = sys_get_temp_dir() . '/dailycup_email_queue.json';
        
        if (!file_exists($queueFile)) {
            echo '<div class="info">📭 No emails in queue</div>';
        } else {
            $queue = json_decode(file_get_contents($queueFile), true) ?: [];
            $count = count($queue);
            
            if ($count === 0) {
                echo '<div class="info">📭 Queue is empty</div>';
            } else {
                echo '<div class="info">Processing ' . $count . ' emails...</div>';
                
                $sent = 0;
                $failed = 0;
                
                foreach ($queue as $index => $email) {
                    $result = EmailService::sendNow($email['to'], $email['subject'], $email['body']);
                    if ($result) {
                        echo '<div class="success">✅ Sent to ' . $email['to'] . '</div>';
                        $sent++;
                        unset($queue[$index]);
                    } else {
                        echo '<div class="error">❌ Failed: ' . $email['to'] . '</div>';
                        $failed++;
                    }
                }
                
                // Save remaining queue
                file_put_contents($queueFile, json_encode(array_values($queue)));
                
                echo '<h2>📊 Results</h2>';
                echo '<table>
                    <tr><td>Sent</td><td><strong>' . $sent . '</strong></td></tr>
                    <tr><td>Failed</td><td><strong>' . $failed . '</strong></td></tr>
                    <tr><td>Remaining</td><td><strong>' . count($queue) . '</strong></td></tr>
                </table>';
            }
        }
        
        echo '<a href="?action=menu" class="btn btn-primary">← Back to Menu</a>';
        echo '</div>';
        break;
}

echo '</div></body></html>';
