<?php
/**
 * Order Status Update API
 * Update order status with state machine validation
 * 
 * POST /api/orders/update_status.php
 * Authorization: Bearer <JWT_TOKEN>
 * Body: { 
 *   order_id: string, 
 *   status: string, 
 *   courier_id?: int,
 *   message?: string,
 *   metadata?: object 
 * }
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
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    $userId = $decoded->user_id;
    $userRole = $decoded->role ?? 'customer';

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['order_id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id and status are required']);
        exit;
    }

    $orderId = $input['order_id'];
    $newStatus = $input['status'];
    $courierId = $input['courier_id'] ?? null;
    $message = $input['message'] ?? null;
    $metadata = $input['metadata'] ?? null;

    // Validate status value
    $validStatuses = [
        'pending_payment',
        'waiting_confirmation',
        'queueing',
        'preparing',
        'on_delivery',
        'completed',
        'cancelled'
    ];

    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status value']);
        exit;
    }

    // Get current order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Check permissions
    // Admin/courier can update any order
    // Customer can only cancel their own pending orders
    if ($userRole !== 'admin' && $userRole !== 'courier') {
        if ($order['user_id'] != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        
        // Customers can only cancel pending orders
        if ($newStatus !== 'cancelled' || !in_array($order['status'], ['pending_payment', 'waiting_confirmation'])) {
            http_response_code(403);
            echo json_encode(['error' => 'You can only cancel pending orders']);
            exit;
        }
    }

    // State machine validation
    $currentStatus = $order['status'];
    $allowedTransitions = [
        'pending_payment' => ['waiting_confirmation', 'cancelled'],
        'waiting_confirmation' => ['queueing', 'cancelled'],
        'queueing' => ['preparing', 'cancelled'],
        'preparing' => ['on_delivery', 'cancelled'],
        'on_delivery' => ['completed', 'cancelled'],
        'completed' => [],  // Final state
        'cancelled' => []   // Final state
    ];

    if (!isset($allowedTransitions[$currentStatus]) || 
        !in_array($newStatus, $allowedTransitions[$currentStatus])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid status transition',
            'current_status' => $currentStatus,
            'new_status' => $newStatus,
            'allowed' => $allowedTransitions[$currentStatus]
        ]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update order status
    $updateFields = ['status = ?'];
    $updateParams = [$newStatus];

    if ($courierId) {
        $updateFields[] = 'courier_id = ?';
        $updateParams[] = $courierId;
    }

    if ($newStatus === 'on_delivery') {
        $updateFields[] = 'estimated_delivery = DATE_ADD(NOW(), INTERVAL 30 MINUTE)';
    }

    if ($newStatus === 'completed') {
        $updateFields[] = 'completed_at = NOW()';
    }

    if ($newStatus === 'cancelled') {
        $updateFields[] = 'cancelled_at = NOW()';
        if ($message) {
            $updateFields[] = 'cancellation_reason = ?';
            $updateParams[] = $message;
        }
    }

    $updateParams[] = $orderId;
    $updateSql = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE order_id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);

    // Log status change
    $logStmt = $pdo->prepare("
        INSERT INTO order_status_log 
        (order_id, status, message, changed_by, changed_by_type, metadata) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $changedByType = $userRole === 'admin' ? 'admin' : ($userRole === 'courier' ? 'courier' : 'customer');
    $metadataJson = $metadata ? json_encode($metadata) : null;
    
    $logStmt->execute([
        $orderId,
        $newStatus,
        $message,
        $userId,
        $changedByType,
        $metadataJson
    ]);

    $pdo->commit();

    // Get updated order
    $stmt->execute([$orderId]);
    $updatedOrder = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'order' => [
            'order_id' => $updatedOrder['order_id'],
            'status' => $updatedOrder['status'],
            'courier_id' => $updatedOrder['courier_id'],
            'estimated_delivery' => $updatedOrder['estimated_delivery'],
            'updated_at' => $updatedOrder['updated_at']
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update order status',
        'message' => $e->getMessage()
    ]);
}
?>
