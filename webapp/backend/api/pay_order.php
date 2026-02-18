<?php
/**
 * Pay Order API
 * 
 * Updates order payment status (for manual/COD payments or testing)
 * For real payment gateways, use their webhooks instead
 */

// CORS must be first!
require_once __DIR__ . '/cors.php';
// CORS handled by .htaccess
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/email/EmailService.php';
require_once __DIR__ . '/notifications/NotificationService.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['orderId'] ?? null;
$action = $input['action'] ?? null; // 'paid' or 'failed'

if (!$orderId || !$action) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing parameters']);
  exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    // Get order from database
    $stmt = $pdo->prepare("
        SELECT id, user_id, order_number, status, payment_status 
        FROM orders 
        WHERE order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Update payment status
    $newStatus = ($action === 'paid') ? 'paid' : 'failed';
    $orderStatus = ($action === 'paid') ? 'processing' : 'cancelled';

    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = ?, 
            status = ?,
            updated_at = NOW()
        WHERE order_number = ?
    ");
    $updateStmt->execute([$newStatus, $orderStatus, $orderId]);

    // Log payment action
    if ($action === 'paid') {
        AuditLog::log(AuditLog::ACTION_PAYMENT_RECEIVED, [
            'order_id' => $orderId,
            'payment_method' => 'manual'
        ], null, 'info');

        // In-app notification
        try {
            if (!empty($order['user_id'])) {
                $notificationService = new NotificationService($pdo);
                $notificationService->createOrderNotification((int)$order['user_id'], $orderId, 'paid');
            }
        } catch (Throwable $e) {
            error_log("Notification pay_order paid error: " . $e->getMessage());
        }

        // Send payment confirmation email
        try {
            // Get customer and order items
            $orderStmt = $pdo->prepare("
                SELECT o.*, u.name as customer_name, u.email as customer_email 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.order_number = ?
            ");
            $orderStmt->execute([$orderId]);
            $orderDetails = $orderStmt->fetch();

            $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $itemsStmt->execute([$order['id']]);
            $items = $itemsStmt->fetchAll();

            if ($orderDetails && $orderDetails['customer_email']) {
                $orderData = [
                    'order_number' => $orderId,
                    'items' => array_map(function($item) {
                        return [
                            'name' => $item['product_name'],
                            'quantity' => $item['quantity'],
                            'price' => $item['unit_price']
                        ];
                    }, $items),
                    'total' => $orderDetails['final_amount'],
                    'payment_method' => $orderDetails['payment_method']
                ];

                $customerData = [
                    'name' => $orderDetails['customer_name'],
                    'email' => $orderDetails['customer_email']
                ];

                EmailService::sendPaymentConfirmation($orderData, $customerData);
            }
        } catch (Exception $e) {
            error_log("Failed to send payment confirmation email: " . $e->getMessage());
        }
    } else {
        AuditLog::log(AuditLog::ACTION_PAYMENT_FAILED, [
            'order_id' => $orderId
        ], null, 'warning');

        // In-app notification for failed payment
        try {
            if (!empty($order['user_id'])) {
                $notificationService = new NotificationService($pdo);
                $notificationService->createOrderNotification((int)$order['user_id'], $orderId, 'failed');
            }
        } catch (Throwable $e) {
            error_log("Notification pay_order fail error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Payment status updated',
        'status' => $newStatus
    ]);

} catch (PDOException $e) {
    error_log("Pay order error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
}
?>
