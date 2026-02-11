<?php
/**
 * Assign Courier to Order
 * Admin endpoint to assign available courier to order
 * 
 * POST /api/orders/assign_courier.php
 * Authorization: Bearer <JWT_TOKEN> (Admin only)
 * Body: { order_id: string, courier_id: int }
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Verify JWT token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing or invalid Authorization header']);
        exit;
    }

    $token = $matches[1];
    $decoded = validateJWT($token);
    
    if (!$decoded || $decoded->role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['order_id']) || !isset($input['courier_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id and courier_id are required']);
        exit;
    }

    $orderId = $input['order_id'];
    $courierId = (int)$input['courier_id'];

    // Verify order exists and is in valid state
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    if (!in_array($order['status'], ['preparing', 'queueing'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Order must be in preparing or queueing status to assign courier',
            'current_status' => $order['status']
        ]);
        exit;
    }

    // Verify courier exists and is available
    $courierStmt = $pdo->prepare("SELECT * FROM couriers WHERE id = ?");
    $courierStmt->execute([$courierId]);
    $courier = $courierStmt->fetch();

    if (!$courier) {
        http_response_code(404);
        echo json_encode(['error' => 'Courier not found']);
        exit;
    }

    if (!$courier['is_available'] || !$courier['is_active']) {
        http_response_code(400);
        echo json_encode(['error' => 'Courier is not available']);
        exit;
    }

    // Assign courier and update status to on_delivery
    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET courier_id = ?, 
            status = 'on_delivery',
            estimated_delivery = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
        WHERE order_id = ?
    ");
    $updateStmt->execute([$courierId, $orderId]);

    // Log status change
    $logStmt = $pdo->prepare("
        INSERT INTO order_status_log 
        (order_id, status, message, changed_by, changed_by_type, metadata) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $logStmt->execute([
        $orderId,
        'on_delivery',
        "Courier {$courier['name']} assigned",
        $decoded->user_id,
        'admin',
        json_encode(['courier_id' => $courierId, 'courier_name' => $courier['name']])
    ]);

    // Mark courier as unavailable
    $courierUpdateStmt = $pdo->prepare("UPDATE couriers SET is_available = FALSE WHERE id = ?");
    $courierUpdateStmt->execute([$courierId]);

    echo json_encode([
        'success' => true,
        'message' => 'Courier assigned successfully',
        'order_id' => $orderId,
        'courier' => [
            'id' => $courier['id'],
            'name' => $courier['name'],
            'phone' => $courier['phone'],
            'vehicle_type' => $courier['vehicle_type']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to assign courier',
        'message' => $e->getMessage()
    ]);
}
?>
