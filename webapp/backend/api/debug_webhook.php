<?php
require_once __DIR__ . '/cors.php';
/**
 * Debug Webhook - Show detailed errors
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Webhook Debug Test</h1>";

$orderId = $_GET['order'] ?? 'ORD-1769421296-8765';

// Simulate webhook payload
$payload = [
    'id' => 'invoice_test_' . time(),
    'external_id' => $orderId,
    'status' => 'PAID',
    'amount' => 10000,
    'paid_amount' => 10000,
    'payer_email' => 'ridhohuman11@gmail.com',
    'payment_method' => 'QRIS',
    'payment_channel' => 'QRIS',
    'paid_at' => date('c'),
    'updated' => date('c')
];

echo "<h2>Testing Webhook Handler Directly</h2>";
echo "<p>Order: <strong>$orderId</strong></p>";

// Manually execute webhook logic with error display
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/input_sanitizer.php';
    
    echo "✅ Config loaded<br>";
    
    // Extract data
    $ext = InputSanitizer::id($payload['external_id'] ?? null);
    $status = InputSanitizer::string($payload['status'] ?? '', 50);
    $paymentId = InputSanitizer::string($payload['id'] ?? '', 100);
    $amount = InputSanitizer::float($payload['amount'] ?? $payload['paid_amount'] ?? 0);
    
    echo "✅ Data extracted: $ext, $status, $paymentId<br>";
    
    // Connect to database
    $conn = new mysqli(
        getenv('DB_HOST') ?: 'localhost',
        getenv('DB_USER') ?: 'root',
        getenv('DB_PASS') ?: '',
        getenv('DB_NAME') ?: 'dailycup_db'
    );
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Database connected<br>";
    
    // Check if order exists with user info
    $stmt = $conn->prepare("
        SELECT o.id, o.payment_status, o.status, u.name as customer_name, u.email as customer_email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ?
    ");
    $stmt->bind_param("s", $ext);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        throw new Exception("Order not found: $ext");
    }
    
    echo "✅ Order found: ID={$order['id']}, Status={$order['payment_status']}<br>";
    
    $orderId = $order['id'];
    
    // Update order
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'processing', paid_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    
    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error);
    }
    
    echo "✅ Order updated to PAID<br>";
    
    // Get order with items and user info
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email,
               GROUP_CONCAT(
                   CONCAT(oi.product_name, '|', oi.quantity, '|', oi.unit_price) 
                   SEPARATOR ';'
               ) as items_data
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderData = $result->fetch_assoc();
    
    echo "✅ Order data retrieved<br>";
    echo "<pre>Customer: {$orderData['customer_name']} ({$orderData['customer_email']})</pre>";
    
    // Parse items
    $items = [];
    if (!empty($orderData['items_data'])) {
        foreach (explode(';', $orderData['items_data']) as $itemStr) {
            if (empty($itemStr)) continue;
            $parts = explode('|', $itemStr);
            if (count($parts) === 3) {
                list($name, $qty, $price) = $parts;
                $items[] = [
                    'name' => $name,
                    'quantity' => (int)$qty,
                    'price' => (float)$price
                ];
            }
        }
    }
    
    echo "✅ Items parsed: " . count($items) . " items<br>";
    
    $orderData['items'] = $items;
    $orderData['order_number'] = $ext;
    $orderData['payment_method'] = 'Xendit';
    $orderData['total'] = $orderData['total_amount'] ?? 0; // Map total_amount to total for EmailService
    
    // Try sending email
    require_once __DIR__ . '/email/EmailService.php';
    
    $customer = [
        'name' => $orderData['customer_name'] ?? 'Customer',
        'email' => $orderData['customer_email'] ?? null
    ];
    
    echo "✅ EmailService loaded<br>";
    echo "<p>Will send to: {$customer['email']}</p>";
    
    if (!empty($customer['email'])) {
        EmailService::setUseQueue(false); // Send immediately for testing
        $sent = EmailService::sendPaymentConfirmation($orderData, $customer);
        
        if ($sent) {
            echo "<div style='background:#e8f5e9;padding:15px;border-radius:8px;margin:10px 0;'>✅ Email sent successfully!</div>";
        } else {
            echo "<div style='background:#ffebee;padding:15px;border-radius:8px;margin:10px 0;'>❌ Email sending failed</div>";
        }
    }
    
    echo "<h2>✅ Test Complete</h2>";
    
    // Check final status
    $stmt = $conn->prepare("SELECT payment_status, status, paid_at FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $finalOrder = $result->fetch_assoc();
    
    echo "<table border='1' cellpadding='10' style='margin-top:20px;'>
        <tr><th>Payment Status</th><td>{$finalOrder['payment_status']}</td></tr>
        <tr><th>Order Status</th><td>{$finalOrder['status']}</td></tr>
        <tr><th>Paid At</th><td>{$finalOrder['paid_at']}</td></tr>
    </table>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='background:#ffebee;padding:15px;border-radius:8px;margin:10px 0;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><br><strong>Stack Trace:</strong><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<br><a href='test_webhook.php'>← Back</a>";
