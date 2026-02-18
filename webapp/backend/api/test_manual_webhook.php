<?php
require_once __DIR__ . '/cors.php';
/**
 * Manual Webhook Test - Simulate Xendit Callback
 */

$orderId = $_GET['order'] ?? 'ORD-1769421296-8765';

echo "<h1>Manual Webhook Test</h1>";
echo "<p>Simulating Xendit callback for order: <strong>$orderId</strong></p>";

// Simulate Xendit webhook payload
$payload = [
    'id' => 'invoice_' . uniqid(),
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

echo "<h2>Webhook Payload:</h2>";
echo "<pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre>";

// Call webhook handler
$webhookUrl = 'http://localhost/DailyCup/webapp/backend/api/notify_xendit.php';
echo "<h2>Calling Webhook: $webhookUrl</h2>";

$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Callback-Token: ' . (getenv('XENDIT_CALLBACK_TOKEN') ?: 'test')
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Webhook Response (HTTP $httpCode):</h2>";

if ($error) {
    echo "<div style='background:#ffebee;padding:15px;border-radius:8px;'>❌ Error: $error</div>";
} else {
    echo "<div style='background:#e8f5e9;padding:15px;border-radius:8px;'>✅ Response received</div>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Check database
require_once __DIR__ . '/config.php';
$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'dailycup_db'
);

$stmt = $conn->prepare("SELECT payment_status, status, paid_at FROM orders WHERE order_number = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

echo "<h2>Order Status in Database:</h2>";
if ($order) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>
        <tr><th>Payment Status</th><td>" . $order['payment_status'] . "</td></tr>
        <tr><th>Order Status</th><td>" . $order['status'] . "</td></tr>
        <tr><th>Paid At</th><td>" . ($order['paid_at'] ?: 'NULL') . "</td></tr>
    </table>";
} else {
    echo "<div style='background:#ffebee;padding:15px;'>Order not found in database</div>";
}

echo "<br><a href='test_webhook.php'>← Back to Test Menu</a>";
