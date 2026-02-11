<?php
/**
 * Kurir Claim Order API
 * 
 * POST /api/kurir/claim_order.php
 * Allows kurir to claim an available (unassigned) order
 * 
 * Body: { "order_id": "ORD-xxx" } or { "order_number": "ORD-xxx" }
 */

require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

// Auth check - must be kurir
$authUser = JWT::getUser();
if (!$authUser || ($authUser['role'] ?? '') !== 'kurir') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Kurir authentication required']);
    exit;
}

$kurirId = $authUser['kurir_id'] ?? $authUser['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$orderNumber = $input['order_id'] ?? $input['order_number'] ?? null;

if (!$orderNumber) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if kurir is active
    $kurirCheck = $pdo->prepare("SELECT id, name, status, is_active FROM kurir WHERE id = ? FOR UPDATE");
    $kurirCheck->execute([$kurirId]);
    $kurir = $kurirCheck->fetch(PDO::FETCH_ASSOC);

    if (!$kurir || !$kurir['is_active']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Akun kurir tidak aktif']);
        exit;
    }

    // Check kurir's active orders count (limit to 5 max)
    $activeOrdersStmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders 
        WHERE kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering')
    ");
    $activeOrdersStmt->execute([$kurirId]);
    $activeOrderCount = (int)$activeOrdersStmt->fetchColumn();

    $maxOrders = 5; // Max orders per kurir
    if ($activeOrderCount >= $maxOrders) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false, 
            'error' => "Anda sudah memiliki $activeOrderCount pesanan aktif. Selesaikan dulu sebelum mengambil pesanan baru.",
            'active_orders' => $activeOrderCount,
            'max_orders' => $maxOrders
        ]);
        exit;
    }

    // Get order and lock row for update
    $orderStmt = $pdo->prepare("
        SELECT o.*, COALESCE(o.customer_name, u.name) as customer_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.order_number = ? FOR UPDATE
    ");
    $orderStmt->execute([$orderNumber]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Order tidak ditemukan']);
        exit;
    }

    // Check if order is available to claim
    if ($order['kurir_id'] !== null) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Order sudah diambil oleh kurir lain']);
        exit;
    }

    if ($order['payment_status'] !== 'paid') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Order belum dibayar']);
        exit;
    }

    if ($order['delivery_method'] !== 'delivery') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Order ini bukan delivery (pickup)']);
        exit;
    }

    if (!in_array($order['status'], ['confirmed', 'processing', 'ready'])) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Order tidak dalam status yang bisa diambil']);
        exit;
    }

    // COD orders must be assigned by admin after verification
    if ($order['payment_method'] === 'cod') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Order COD harus di-assign oleh admin setelah verifikasi']);
        exit;
    }

    // Claim the order - assign kurir
    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET kurir_id = ?, 
            assigned_at = NOW(),
            updated_at = NOW()
        WHERE order_number = ? AND kurir_id IS NULL
    ");
    $updateStmt->execute([$kurirId, $orderNumber]);

    if ($updateStmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Gagal mengambil order. Mungkin sudah diambil kurir lain.']);
        exit;
    }

    // Update kurir status if they have multiple orders
    $newActiveCount = $activeOrderCount + 1;
    if ($newActiveCount >= 3) {
        $pdo->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?")->execute([$kurirId]);
    }

    // Create notification for customer
    $notifStmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, data, created_at)
        VALUES (?, 'order_update', 'Kurir Ditugaskan', ?, ?, NOW())
    ");
    $notifMessage = "Pesanan #{$orderNumber} telah diambil oleh kurir {$kurir['name']}. Pesanan Anda akan segera diantar!";
    $notifData = json_encode(['order_number' => $orderNumber, 'kurir_name' => $kurir['name']]);
    $notifStmt->execute([$order['user_id'], $notifMessage, $notifData]);

    // Log to delivery history if table exists
    try {
        $historyStmt = $pdo->prepare("
            INSERT INTO delivery_history (order_id, kurir_id, status, notes, created_at)
            VALUES (?, ?, 'assigned', 'Kurir claimed order', NOW())
        ");
        $historyStmt->execute([$order['id'], $kurirId]);
    } catch (Exception $e) {
        // Delivery history table might not exist, ignore
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Berhasil mengambil pesanan #{$orderNumber}",
        'order' => [
            'order_number' => $orderNumber,
            'customer_name' => $order['customer_name'],
            'delivery_address' => $order['delivery_address'],
            'total_amount' => (float)($order['final_amount'] ?: $order['total_amount'])
        ],
        'active_orders_count' => $newActiveCount
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Claim order error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
