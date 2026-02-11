<?php
/**
 * Track Order API
 * 
 * Accepts order_number and returns order details for tracking
 * No authentication required (public tracking)
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get order number from request
$orderNumber = $_GET['order_number'] ?? $_GET['id'] ?? null;

if (!$orderNumber) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order number is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get order by order_number
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.payment_status,
            o.payment_method,
            o.delivery_method,
            o.delivery_address,
            COALESCE(o.customer_name, u.name) as customer_name,
            COALESCE(o.customer_phone, u.phone) as customer_phone,
            o.total_amount,
            o.final_amount,
            o.created_at
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $orderNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Return order info
    echo json_encode([
        'success' => true,
        'order' => [
            'id' => $order['order_number'], // Use order_number as ID for URL
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $order['payment_method'],
            'delivery_method' => $order['delivery_method'],
            'delivery_address' => $order['delivery_address'],
            'customer_name' => $order['customer_name'],
            'customer_phone' => $order['customer_phone'],
            'total' => (float)($order['final_amount'] ?: $order['total_amount']),
            'created_at' => $order['created_at']
        ]
    ]);

} catch (Exception $e) {
    error_log("Track Order Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
