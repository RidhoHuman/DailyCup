<?php
/**
 * Xendit Webhook Handler
 * 
 * Receives payment notifications from Xendit and updates order status.
 * Implements HMAC signature verification for security.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/webhook_signature.php';
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/input_sanitizer.php';

header('Content-Type: application/json');

// Rate limiting for webhooks
$clientIP = RateLimiter::getClientIP();
RateLimiter::enforce($clientIP, 'webhook');

// Get raw payload before any processing
$raw = file_get_contents('php://input');

// Log incoming webhook
error_log("XENDIT WEBHOOK: Received from IP $clientIP");

// Verify webhook signature (HMAC or callback token)
$webhookSecret = getenv('XENDIT_WEBHOOK_SECRET');
$callbackToken = getenv('XENDIT_CALLBACK_TOKEN');

$isVerified = false;

// Try HMAC signature first (more secure)
if ($webhookSecret) {
  $signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
  if (!empty($signature)) {
    $expectedSignature = hash_hmac('sha256', $raw, $webhookSecret);
    $isVerified = hash_equals($expectedSignature, $signature);
    
    if (!$isVerified) {
      error_log("XENDIT WEBHOOK: HMAC signature verification failed");
    }
  }
}

// Fallback to callback token if HMAC not configured or failed
if (!$isVerified && $callbackToken) {
  $headerToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? null;
  $queryToken = $_GET['token'] ?? null;
  $isVerified = ($headerToken === $callbackToken || $queryToken === $callbackToken);
  
  if (!$isVerified) {
    error_log("XENDIT WEBHOOK: Callback token verification failed");
  }
}

// If no verification method configured, log warning but allow (for testing)
if (!$webhookSecret && !$callbackToken) {
  error_log("XENDIT WEBHOOK: WARNING - No verification configured! Configure XENDIT_WEBHOOK_SECRET or XENDIT_CALLBACK_TOKEN");
  $isVerified = true; // Allow for testing, remove in production
}

// Reject if verification failed
if (!$isVerified) {
  WebhookSignature::log('xendit', false, ['ip' => $clientIP]);
  AuditLog::logSecurityAlert('WEBHOOK_VERIFICATION_FAILED', [
    'provider' => 'xendit',
    'ip' => $clientIP
  ]);
  
  http_response_code(401);
  echo json_encode(['success' => false, 'reason' => 'Invalid signature or token']);
  exit;
}

// Parse payload
$payload = json_decode($raw, true);
if (!$payload) {
  http_response_code(400);
  echo json_encode(['success' => false, 'reason' => 'Invalid JSON payload']);
  exit;
}

// Log successful verification
WebhookSignature::log('xendit', true, $payload);

// Extract data with sanitization
$ext = InputSanitizer::id($payload['external_id'] ?? null);
$status = InputSanitizer::string($payload['status'] ?? '', 50);
$paymentId = InputSanitizer::string($payload['id'] ?? '', 100);
$amount = InputSanitizer::float($payload['amount'] ?? $payload['paid_amount'] ?? 0);

// Connect to database
$conn = new mysqli(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'dailycup_db'
);

if ($conn->connect_error) {
    error_log("XENDIT WEBHOOK: Database connection failed - " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'reason' => 'Database error']);
    exit;
}

// Check if order exists in database with user info
$stmt = $conn->prepare("
    SELECT o.id, o.payment_status, o.status, o.user_id, u.name as customer_name, u.email as customer_email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.order_number = ?
");
$stmt->bind_param("s", $ext);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    error_log("XENDIT WEBHOOK: Order not found in database: $ext");
    echo json_encode(['success' => false, 'reason' => 'Order not found']);
    exit;
}

$previousStatus = $order['payment_status'];
$orderId = $order['id'];

// Map Xendit statuses to our internal status
if ($status === 'PAID' || $status === 'SETTLED') {
    // Update order in database
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'processing', paid_at = NOW(), updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    
    if (!$stmt->execute()) {
        error_log("XENDIT WEBHOOK: Failed to update order - " . $stmt->error);
    } else {
        error_log("XENDIT WEBHOOK: Order $ext updated to PAID");
    }
    
    // Log payment received
    AuditLog::logPaymentReceived($ext, $paymentId, $amount, 'xendit');
    
    // Get order details with items and user info for email
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
    $orderData['items'] = $items;
    $orderData['order_number'] = $ext;
    $orderData['payment_method'] = 'Xendit';
    $orderData['total'] = $orderData['total_amount'] ?? 0; // Map total_amount to total for EmailService
    
    // Send payment confirmation email
    try {
        require_once __DIR__ . '/email/EmailService.php';
        
        $customer = [
            'name' => $orderData['customer_name'] ?? 'Customer',
            'email' => $orderData['customer_email'] ?? null
        ];
        
        if (!empty($customer['email'])) {
            EmailService::sendPaymentConfirmation($orderData, $customer);
            error_log("XENDIT WEBHOOK: Payment confirmation email queued for {$customer['email']}");
        }
    } catch (Exception $emailErr) {
        error_log("XENDIT WEBHOOK: Failed to send payment email - " . $emailErr->getMessage());
    }
    
} else if ($status === 'EXPIRED' || $status === 'FAILED') {
    // Update failed status
    $failureReason = $payload['failure_reason'] ?? 'Payment failed';
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'failed', status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    AuditLog::log(AuditLog::ACTION_PAYMENT_FAILED, [
        'order_id' => $ext,
        'status' => $status,
        'reason' => $failureReason
    ]);
    
} else {
    // Other statuses
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $statusLower = strtolower($status);
    $stmt->bind_param("si", $statusLower, $orderId);
    $stmt->execute();
}

// Log status change
AuditLog::log(AuditLog::ACTION_ORDER_UPDATE, [
    'order_id' => $ext,
    'previous_status' => $previousStatus,
    'new_status' => $status
]);

$conn->close();

error_log("XENDIT WEBHOOK: Order $ext processed successfully");
echo json_encode(['success' => true]);
?>