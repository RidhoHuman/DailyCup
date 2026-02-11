<?php
/**
 * Xendit Payment Webhook Handler
 * 
 * This endpoint receives payment notifications from Xendit
 * and automatically updates order payment status.
 * 
 * Xendit will POST to this URL when payment status changes.
 * 
 * Setup: Configure this URL in Xendit Dashboard:
 * https://your-domain.com/api/xendit_webhook.php
 * OR (for development with ngrok):
 * https://abc123.ngrok-free.app/DailyCup/webapp/backend/api/xendit_webhook.php
 */

// Log all incoming requests for debugging
$logFile = __DIR__ . '/../logs/xendit_webhooks.log';
$requestBody = file_get_contents('php://input');
$logEntry = "[" . date('Y-m-d H:i:s') . "] Webhook received:\n" . $requestBody . "\n---\n\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// CORS handled by .htaccess
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Verify webhook signature (IMPORTANT for security!)
// Get Xendit webhook token from environment or config
$xenditWebhookToken = getenv('XENDIT_WEBHOOK_TOKEN') ?: 'your-xendit-webhook-verification-token';
$receivedToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? '';

// For development, you can disable this check, but NEVER in production!
$skipVerification = (getenv('ENVIRONMENT') === 'development');

if (!$skipVerification && $receivedToken !== $xenditWebhookToken) {
    error_log("[Xendit Webhook] Invalid webhook token");
    http_response_code(401);
    echo json_encode(['error' => 'Invalid webhook token']);
    exit;
}

try {
    // Parse webhook payload
    $payload = json_decode($requestBody, true);
    
    if (!$payload) {
        throw new Exception('Invalid JSON payload');
    }
    
    // Xendit sends different webhook types
    // Common fields: id, status, external_id, payment_method
    $paymentStatus = strtolower($payload['status'] ?? '');
    $externalId = $payload['external_id'] ?? ''; // This should be your order_number
    $paymentMethod = $payload['payment_method'] ?? '';
    $paidAmount = $payload['paid_amount'] ?? $payload['amount'] ?? 0;
    
    error_log("[Xendit Webhook] Processing payment for order: {$externalId}, status: {$paymentStatus}");
    
    // Map Xendit status to our payment_status
    $ourPaymentStatus = mapXenditStatus($paymentStatus);
    
    if (!$externalId || !$ourPaymentStatus) {
        throw new Exception('Missing required fields: external_id or status');
    }
    
    global $pdo;
    
    // Find order by order_number (external_id)
    $stmt = $pdo->prepare("SELECT id, final_amount, payment_status FROM orders WHERE order_number = ?");
    $stmt->execute([$externalId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        error_log("[Xendit Webhook] Order not found: {$externalId}");
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Validate amount (optional but recommended)
    if ($ourPaymentStatus === 'paid' && $paidAmount < $order['final_amount']) {
        error_log("[Xendit Webhook] Amount mismatch: expected {$order['final_amount']}, got {$paidAmount}");
        // You can decide to reject or flag this
    }
    
    // Update payment status and order status if paid
    $orderStatus = null;
    if ($ourPaymentStatus === 'paid') {
        $orderStatus = 'confirmed';
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = ?,
                status = ?,
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ourPaymentStatus, $orderStatus, $order['id']]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ourPaymentStatus, $order['id']]);
    }
    
    error_log("[Xendit Webhook] ✅ Order #{$externalId} payment status updated to: {$ourPaymentStatus}");
    
    // Auto-assign kurir if payment is successful
    $kurirAssigned = null;
    if ($ourPaymentStatus === 'paid') {
        $kurirAssigned = autoAssignKurir($pdo, $externalId);
    }
    
    // Send notification to admin/customer (optional)
    // You can integrate with your notification system here
    
    // Respond to Xendit (MUST respond with 200 OK to acknowledge)
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated',
        'order_id' => $externalId,
        'payment_status' => $ourPaymentStatus,
        'kurir_assigned' => $kurirAssigned
    ]);
    
} catch (Exception $e) {
    error_log("[Xendit Webhook] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Webhook processing failed',
        'message' => $e->getMessage()
    ]);
}

/**
 * Map Xendit payment status to our internal payment_status
 */
function mapXenditStatus($xenditStatus) {
    $statusMap = [
        'paid' => 'paid',
        'settled' => 'paid',
        'completed' => 'paid',
        'success' => 'paid',
        'pending' => 'pending',
        'expired' => 'failed',
        'failed' => 'failed',
        'cancelled' => 'failed',
        'refunded' => 'refunded',
    ];
    
    return $statusMap[$xenditStatus] ?? null;
}

/**
 * Auto-assign kurir to delivery order
 * Uses load-balancing: assigns to kurir with least active orders
 */
function autoAssignKurir($pdo, $orderNumber) {
    try {
        // Get order details
        $orderStmt = $pdo->prepare("SELECT id, delivery_method, kurir_id, user_id, payment_method FROM orders WHERE order_number = ?");
        $orderStmt->execute([$orderNumber]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || $order['delivery_method'] !== 'delivery' || $order['kurir_id']) {
            error_log("[Xendit Webhook] Skip auto-assign: not delivery or already assigned");
            return null; // Not a delivery order or already assigned
        }

        // COD orders must be manually assigned by admin after verification
        if ($order['payment_method'] === 'cod') {
            error_log("[Xendit Webhook] Skip auto-assign: COD order requires admin verification");
            return null;
        }

        // Find available kurir with least active orders (load balancing)
        $kurirStmt = $pdo->prepare("
            SELECT k.id, k.name, COUNT(o.id) as active_orders
            FROM kurir k
            LEFT JOIN orders o ON k.id = o.kurir_id 
                AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
            WHERE k.status IN ('available', 'busy') AND k.is_active = 1
            GROUP BY k.id
            HAVING active_orders < 5
            ORDER BY 
                CASE WHEN k.status = 'available' THEN 0 ELSE 1 END,
                active_orders ASC,
                k.rating DESC
            LIMIT 1
        ");
        $kurirStmt->execute();
        $kurir = $kurirStmt->fetch(PDO::FETCH_ASSOC);

        if (!$kurir) {
            error_log("[Xendit Webhook] No available kurir for order $orderNumber");
            return null;
        }

        // Assign kurir
        $updateStmt = $pdo->prepare("UPDATE orders SET kurir_id = ?, assigned_at = NOW() WHERE order_number = ?");
        $updateStmt->execute([$kurir['id'], $orderNumber]);

        // Update kurir status to busy if they have 3+ active orders
        if (($kurir['active_orders'] + 1) >= 3) {
            $pdo->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?")->execute([$kurir['id']]);
        }

        // Create notification for customer
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, 'order_update', 'Kurir Ditugaskan', ?, ?, NOW())
        ");
        $notifMessage = "Pesanan #{$orderNumber} telah ditugaskan ke kurir {$kurir['name']}. Pesanan Anda akan segera diantar!";
        $notifData = json_encode(['order_number' => $orderNumber, 'kurir_name' => $kurir['name']]);
        $notifStmt->execute([$order['user_id'], $notifMessage, $notifData]);

        error_log("[Xendit Webhook] ✅ Auto-assigned kurir {$kurir['name']} (ID: {$kurir['id']}) to order $orderNumber");

        return [
            'kurir_id' => $kurir['id'],
            'kurir_name' => $kurir['name']
        ];

    } catch (Exception $e) {
        error_log("[Xendit Webhook] Auto-assign error: " . $e->getMessage());
        return null;
    }
}
