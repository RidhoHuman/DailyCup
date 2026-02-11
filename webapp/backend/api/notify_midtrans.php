<?php
/**
 * Midtrans Payment Notification Handler
 * 
 * Receives payment notifications from Midtrans webhook
 * and updates order status in database
 */

// CORS must be first!
// CORS handled by .htaccess
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/email/EmailService.php';
require_once __DIR__ . '/notifications/NotificationService.php';

header('Content-Type: application/json');

// Get notification payload
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid payload']);
  exit;
}

// Log the notification
error_log("Midtrans Notification: " . $raw);

// Extract order ID and transaction status
$orderId = $payload['order_id'] ?? null;
$transactionStatus = $payload['transaction_status'] ?? null;
$fraudStatus = $payload['fraud_status'] ?? 'accept';

if (!$orderId || !$transactionStatus) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Missing required fields']);
  exit;
}

// Verify signature (important for security)
$serverKey = getenv('MIDTRANS_SERVER_KEY');
if ($serverKey) {
    $signatureKey = $payload['signature_key'] ?? '';
    $expectedSignature = hash('sha512', $orderId . $payload['status_code'] . $payload['gross_amount'] . $serverKey);
    
    if ($signatureKey !== $expectedSignature) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid signature']);
        AuditLog::logSecurityAlert('INVALID_MIDTRANS_SIGNATURE', [
            'order_id' => $orderId,
            'expected' => substr($expectedSignature, 0, 20) . '...',
            'received' => substr($signatureKey, 0, 20) . '...'
        ]);
        exit;
    }
}

require_once __DIR__ . '/../config/database.php';

try {
    // Map Midtrans status to our payment status
    $paymentStatus = 'pending';
    $orderStatus = 'pending';
    
    switch ($transactionStatus) {
        case 'capture':
            if ($fraudStatus == 'accept') {
                $paymentStatus = 'paid';
                $orderStatus = 'processing';
            }
            break;
        case 'settlement':
            $paymentStatus = 'paid';
            $orderStatus = 'processing';
            break;
        case 'pending':
            $paymentStatus = 'pending';
            $orderStatus = 'pending';
            break;
        case 'deny':
        case 'expire':
        case 'cancel':
            $paymentStatus = 'failed';
            $orderStatus = 'cancelled';
            break;
    }

    // Update order in database
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_status = ?, 
            status = ?,
            midtrans_response = ?,
            updated_at = NOW()
        WHERE order_number = ?
    ");
    $stmt->execute([
        $paymentStatus,
        $orderStatus,
        json_encode($payload),
        $orderId
    ]);

    // Log payment notification
    if ($paymentStatus === 'paid') {
        AuditLog::log(AuditLog::ACTION_PAYMENT_RECEIVED, [
            'order_id' => $orderId,
            'payment_method' => 'midtrans',
            'transaction_status' => $transactionStatus
        ], null, 'info');

        // In-app notification
        try {
            $orderUserStmt = $pdo->prepare("SELECT user_id FROM orders WHERE order_number = ? LIMIT 1");
            $orderUserStmt->execute([$orderId]);
            $orderRow = $orderUserStmt->fetch();
            if ($orderRow && !empty($orderRow['user_id'])) {
                $notificationService = new NotificationService($pdo);
                $notificationService->createOrderNotification((int)$orderRow['user_id'], $orderId, 'paid');
            }
        } catch (Throwable $e) {
            error_log("Notification midtrans paid error: " . $e->getMessage());
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
            $itemsStmt->execute([$orderDetails['id']]);
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
                    'payment_method' => 'Midtrans'
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
    } else if ($paymentStatus === 'failed') {
        AuditLog::log(AuditLog::ACTION_PAYMENT_FAILED, [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus
        ], null, 'warning');

        // In-app notification for failed payment
        try {
            $orderUserStmt = $pdo->prepare("SELECT user_id FROM orders WHERE order_number = ? LIMIT 1");
            $orderUserStmt->execute([$orderId]);
            $orderRow = $orderUserStmt->fetch();
            if ($orderRow && !empty($orderRow['user_id'])) {
                $notificationService = new NotificationService($pdo);
                $notificationService->createOrderNotification((int)$orderRow['user_id'], $orderId, 'failed');
            }
        } catch (Throwable $e) {
            error_log("Notification midtrans failed error: " . $e->getMessage());
        }
    }

    echo json_encode(['success' => true, 'message' => 'Notification processed']);

} catch (PDOException $e) {
    error_log("Midtrans notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>