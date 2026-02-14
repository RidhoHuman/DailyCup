<?php
/**
 * Sync Xendit Invoice Status
 * Checks Xendit API for invoice status and updates database
 * Used for development when webhooks can't reach localhost
 */

// CORS handled centrally (cors.php / .htaccess)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load config which loads .env
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/database.php';

// Get Xendit key from environment (loaded by config.php)
$xenditKey = getenv('XENDIT_SECRET_KEY');

if (!$xenditKey) {
    error_log("XENDIT_SYNC: XENDIT_SECRET_KEY not found in environment");
    echo json_encode(['success' => false, 'message' => 'Xendit key not configured']);
    exit;
}

// Get order ID from request
$orderId = $_GET['orderId'] ?? $_POST['orderId'] ?? null;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

try {
    // Get order from database
    $stmt = $conn->prepare("SELECT id, order_number, payment_status, payment_method FROM orders WHERE order_number = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Only sync if payment is still pending and method is xendit
    if ($order['payment_status'] === 'paid') {
        echo json_encode([
            'success' => true, 
            'message' => 'Order already paid',
            'payment_status' => 'paid',
            'synced' => false
        ]);
        exit;
    }

    if ($order['payment_method'] !== 'xendit') {
        echo json_encode([
            'success' => true, 
            'message' => 'Not a Xendit payment',
            'payment_status' => $order['payment_status'],
            'synced' => false
        ]);
        exit;
    }

    // Call Xendit API to check invoice status
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/v2/invoices?external_id=' . urlencode($orderId));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($xenditKey . ':')
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to check Xendit status',
            'http_code' => $httpCode
        ]);
        exit;
    }

    $invoices = json_decode($response, true);
    
    if (empty($invoices) || !isset($invoices[0])) {
        echo json_encode([
            'success' => true, 
            'message' => 'No invoice found at Xendit',
            'payment_status' => $order['payment_status'],
            'synced' => false
        ]);
        exit;
    }

    $invoice = $invoices[0];
    $xenditStatus = $invoice['status'] ?? 'UNKNOWN';
    
    // Map Xendit status to our status
    $newPaymentStatus = $order['payment_status'];
    $newOrderStatus = null;
    
    if ($xenditStatus === 'PAID' || $xenditStatus === 'SETTLED') {
        $newPaymentStatus = 'paid';
        $newOrderStatus = 'confirmed';
    } elseif ($xenditStatus === 'EXPIRED') {
        $newPaymentStatus = 'expired';
        $newOrderStatus = 'cancelled';
    } elseif ($xenditStatus === 'FAILED') {
        $newPaymentStatus = 'failed';
    }

    // Update database if status changed
    $synced = false;
    $kurirAssigned = null;
    if ($newPaymentStatus !== $order['payment_status']) {
        if ($newOrderStatus) {
            $updateStmt = $conn->prepare("UPDATE orders SET payment_status = ?, status = ?, paid_at = NOW() WHERE order_number = ?");
            $updateStmt->execute([$newPaymentStatus, $newOrderStatus, $orderId]);
        } else {
            $updateStmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE order_number = ?");
            $updateStmt->execute([$newPaymentStatus, $orderId]);
        }
        $synced = true;
        
        error_log("XENDIT_SYNC: Order $orderId synced from {$order['payment_status']} to $newPaymentStatus (Xendit: $xenditStatus)");

        // Auto-assign kurir when payment is confirmed (for delivery orders)
        if ($newPaymentStatus === 'paid') {
            $kurirAssigned = autoAssignKurir($conn, $orderId);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $synced ? 'Status synced from Xendit' : 'Status unchanged',
        'payment_status' => $newPaymentStatus,
        'xendit_status' => $xenditStatus,
        'synced' => $synced,
        'kurir_assigned' => $kurirAssigned
    ]);

} catch (Exception $e) {
    error_log("XENDIT_SYNC Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Auto-assign kurir to delivery order
 * Uses load-balancing: assigns to kurir with least active orders
 */
function autoAssignKurir($conn, $orderNumber) {
    try {
        // Get order details
        $orderStmt = $conn->prepare("SELECT id, delivery_method, kurir_id, user_id, payment_method FROM orders WHERE order_number = ?");
        $orderStmt->execute([$orderNumber]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order || $order['delivery_method'] !== 'delivery' || $order['kurir_id']) {
            return null; // Not a delivery order or already assigned
        }

        // COD orders must be manually assigned by admin after verification
        if ($order['payment_method'] === 'cod') {
            error_log("XENDIT_SYNC: Skip auto-assign for COD order $orderNumber - requires admin verification");
            return null;
        }

        // Find available kurir with least active orders (load balancing)
        $kurirStmt = $conn->prepare("
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
            error_log("XENDIT_SYNC: No available kurir for order $orderNumber");
            return null;
        }

        // Assign kurir
        $updateStmt = $conn->prepare("UPDATE orders SET kurir_id = ?, assigned_at = NOW() WHERE order_number = ?");
        $updateStmt->execute([$kurir['id'], $orderNumber]);

        // Update kurir status to busy if they have 3+ active orders
        if (($kurir['active_orders'] + 1) >= 3) {
            $conn->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?")->execute([$kurir['id']]);
        }

        // Create notification for customer
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, 'order_update', 'Kurir Ditugaskan', ?, ?, NOW())
        ");
        $notifMessage = "Pesanan #{$orderNumber} telah ditugaskan ke kurir {$kurir['name']}. Pesanan Anda akan segera diantar!";
        $notifData = json_encode(['order_number' => $orderNumber, 'kurir_name' => $kurir['name']]);
        $notifStmt->execute([$order['user_id'], $notifMessage, $notifData]);

        error_log("XENDIT_SYNC: Auto-assigned kurir {$kurir['name']} (ID: {$kurir['id']}) to order $orderNumber");

        return [
            'kurir_id' => $kurir['id'],
            'kurir_name' => $kurir['name']
        ];

    } catch (Exception $e) {
        error_log("XENDIT_SYNC Auto-assign error: " . $e->getMessage());
        return null;
    }
}
