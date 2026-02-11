<?php
/**
 * Update Order Status API (Admin Only)
 * 
 * Allows admin to manually update order status
 * PUT /api/admin/update_order_status.php
 * Requires: Admin authentication
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../audit_log.php';
require_once __DIR__ . '/../email/EmailService.php';

header('Content-Type: application/json');

// Only accept POST/PUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require admin authentication
$authUser = JWT::requireAuth();
if ($authUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['orderId'] ?? null;
$newStatus = $input['status'] ?? null;
$paymentStatus = $input['paymentStatus'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Get current order
    $stmt = $pdo->prepare("
        SELECT id, order_number, status, payment_status 
        FROM orders 
        WHERE order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Valid order statuses
    $validOrderStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    $validPaymentStatuses = ['pending', 'paid', 'failed', 'refunded'];

    // Prepare update query
    $updates = [];
    $params = [];

    if ($newStatus && in_array($newStatus, $validOrderStatuses)) {
        $updates[] = "status = ?";
        $params[] = $newStatus;
    }

    if ($paymentStatus && in_array($paymentStatus, $validPaymentStatuses)) {
        $updates[] = "payment_status = ?";
        $params[] = $paymentStatus;
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid status provided']);
        exit;
    }

    // Add updated_at
    $updates[] = "updated_at = NOW()";
    $params[] = $orderId;

    // Update order
    $sql = "UPDATE orders SET " . implode(', ', $updates) . " WHERE order_number = ?";
    $updateStmt = $pdo->prepare($sql);
    $updateStmt->execute($params);

    // Log the status update
    AuditLog::log(AuditLog::ACTION_ORDER_UPDATE, [
        'order_id' => $orderId,
        'old_status' => $order['status'],
        'new_status' => $newStatus ?? $order['status'],
        'old_payment_status' => $order['payment_status'],
        'new_payment_status' => $paymentStatus ?? $order['payment_status']
    ], $authUser['user_id'], 'info');

    // Send status update email if order status changed
    if ($newStatus && $newStatus !== $order['status']) {
        try {
            // Get customer and order details
            $orderStmt = $pdo->prepare("
                SELECT o.*, u.name as customer_name, u.email as customer_email 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.order_number = ?
            ");
            $orderStmt->execute([$orderId]);
            $orderDetails = $orderStmt->fetch();

            if ($orderDetails && $orderDetails['customer_email']) {
                $orderData = [
                    'order_number' => $orderId
                ];

                $customerData = [
                    'name' => $orderDetails['customer_name'],
                    'email' => $orderDetails['customer_email']
                ];

                EmailService::sendStatusUpdate($orderData, $customerData, $newStatus);
            }
        } catch (Exception $e) {
            error_log("Failed to send status update email: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'orderId' => $orderId,
        'status' => $newStatus ?? $order['status'],
        'paymentStatus' => $paymentStatus ?? $order['payment_status']
    ]);

} catch (PDOException $e) {
    error_log("Update order status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update order status']);
}
?>
