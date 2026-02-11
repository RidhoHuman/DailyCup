<?php
/**
 * Get Order Tracking Details
 * Returns complete tracking information for an order
 * 
 * GET /api/orders/tracking.php?order_id=xxx
 * Authorization: Bearer <JWT_TOKEN> (optional for guest orders)
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $orderId = $_GET['order_id'] ?? '';
    
    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id is required']);
        exit;
    }

    // Optional JWT validation (allow guest tracking)
    $userId = null;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!empty($authHeader) && preg_match('/Bearer\s+(.*)/', $authHeader, $matches)) {
        $token = $matches[1];
        $decoded = validateJWT($token);
        if ($decoded) {
            $userId = $decoded->user_id;
        }
    }

    // Get order details
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.name as courier_name,
            c.phone as courier_phone,
            c.vehicle_type,
            c.vehicle_number,
            c.current_location_lat as courier_lat,
            c.current_location_lng as courier_lng
        FROM orders o
        LEFT JOIN couriers c ON o.courier_id = c.id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Get status history
    $historyStmt = $pdo->prepare("
        SELECT * FROM order_status_log 
        WHERE order_id = ? 
        ORDER BY created_at ASC
    ");
    $historyStmt->execute([$orderId]);
    $statusHistory = $historyStmt->fetchAll();

    // Get recent location updates (last 10)
    $locationStmt = $pdo->prepare("
        SELECT * FROM order_locations 
        WHERE order_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $locationStmt->execute([$orderId]);
    $locations = $locationStmt->fetchAll();

    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT * FROM order_items 
        WHERE order_id = ?
    ");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();

    // Check COD verification if applicable
    $codVerification = null;
    if ($order['payment_method'] === 'cod') {
        $codStmt = $pdo->prepare("
            SELECT is_verified, is_trusted_user, verified_at 
            FROM cod_verifications 
            WHERE order_id = ?
        ");
        $codStmt->execute([$orderId]);
        $codVerification = $codStmt->fetch();
    }

    // Build response
    $response = [
        'success' => true,
        'order' => [
            'order_id' => $order['order_id'],
            'customer_name' => $order['customer_name'],
            'customer_phone' => $order['customer_phone'],
            'customer_address' => $order['customer_address'],
            'total' => (float)$order['total'],
            'status' => $order['status'],
            'payment_method' => $order['payment_method'],
            'created_at' => $order['created_at'],
            'estimated_delivery' => $order['estimated_delivery'],
            'completed_at' => $order['completed_at'],
            'cancelled_at' => $order['cancelled_at'],
            'cancellation_reason' => $order['cancellation_reason']
        ],
        'items' => $items,
        'courier' => $order['courier_id'] ? [
            'id' => $order['courier_id'],
            'name' => $order['courier_name'],
            'phone' => $order['courier_phone'],
            'vehicle_type' => $order['vehicle_type'],
            'vehicle_number' => $order['vehicle_number'],
            'photo' => $order['courier_photo'],
            'current_location' => [
                'lat' => $order['courier_lat'] ? (float)$order['courier_lat'] : null,
                'lng' => $order['courier_lng'] ? (float)$order['courier_lng'] : null
            ]
        ] : null,
        'status_history' => $statusHistory,
        'location_trail' => $locations,
        'cod_verification' => $codVerification
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch tracking data',
        'message' => $e->getMessage()
    ]);
}
?>
