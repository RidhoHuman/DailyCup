<?php
/**
 * Get Order API
 * 
 * Retrieves order details by order ID.
 * Implements rate limiting and input sanitization.
 */

// CORS must be first!
// CORS handled by .htaccess
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/input_sanitizer.php';

header('Content-Type: application/json');

// Rate limiting
$clientIP = RateLimiter::getClientIP();
RateLimiter::enforce($clientIP, 'default');

// Sanitize order ID (order_number from database)
$orderId = InputSanitizer::string($_GET['orderId'] ?? '', 100);

if (!$orderId) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing or invalid orderId']);
  exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // Get order from database
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.total_amount,
            o.discount_amount,
            o.final_amount,
            o.delivery_method,
            o.delivery_address,
            o.customer_notes,
            o.status,
            o.payment_method,
            o.payment_status,
            o.created_at,
            o.updated_at,
            u.name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderId]);
    $orderData = $stmt->fetch();

    if (!$orderData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Get order items
    $itemStmt = $pdo->prepare("
        SELECT 
            product_id as id,
            product_name as name,
            size,
            temperature,
            quantity,
            unit_price as price,
            subtotal,
            notes
        FROM order_items
        WHERE order_id = ?
    ");
    $itemStmt->execute([$orderData['id']]);
    $items = $itemStmt->fetchAll();

    // Format response to match frontend expectations
    $order = [
        'id' => $orderData['order_number'],
        'total' => (float)$orderData['final_amount'],
        'subtotal' => (float)$orderData['total_amount'],
        'discount' => (float)$orderData['discount_amount'],
        'status' => $orderData['payment_status'] ?: $orderData['status'], // Use payment_status if available
        'paymentMethod' => $orderData['payment_method'],
        'deliveryMethod' => $orderData['delivery_method'],
        'items' => array_map(function($item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => (float)$item['price'],
                'quantity' => (int)$item['quantity'],
                'size' => $item['size'],
                'temperature' => $item['temperature'],
                'notes' => $item['notes']
            ];
        }, $items),
        'customer' => [
            'name' => $orderData['customer_name'],
            'email' => $orderData['customer_email'],
            'phone' => $orderData['customer_phone'],
            'address' => $orderData['delivery_address']
        ],
        'notes' => $orderData['customer_notes'],
        'created_at' => $orderData['created_at'],
        'updated_at' => $orderData['updated_at']
    ];

    echo json_encode(['success' => true, 'order' => $order]);

} catch (PDOException $e) {
    error_log("Get order error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve order']);
}
?>