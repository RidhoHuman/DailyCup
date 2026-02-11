<?php
/**
 * Kurir Update Order Status API
 * 
 * POST /api/kurir/update_status.php
 * Body: { order_id: int|string, status: string }
 * 
 * Valid status transitions:
 *   confirmed → processing
 *   processing → ready
 *   ready → delivering
 *   delivering → completed
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

// Auth check
$authUser = JWT::getUser();
if (!$authUser || ($authUser['role'] ?? '') !== 'kurir') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Kurir authentication required']);
    exit;
}

$kurirId = $authUser['kurir_id'] ?? $authUser['user_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

    $orderId = $input['order_id'] ?? null;
    $newStatus = $input['status'] ?? '';

    if (!$orderId || empty($newStatus)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'order_id dan status wajib diisi']);
        exit;
    }

    // Find order - support both numeric ID and order_number
    $orderWhere = is_numeric($orderId) ? "o.id = ?" : "o.order_number = ?";
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number, o.status, o.kurir_id, o.payment_method, o.user_id,
               u.name as customer_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE $orderWhere
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
        exit;
    }

    // Verify this order is assigned to this kurir
    if ((int)$order['kurir_id'] !== (int)$kurirId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Pesanan ini bukan milik Anda']);
        exit;
    }

    // Define valid transitions
    $validTransitions = [
        'confirmed'  => ['processing'],
        'processing' => ['ready'],
        'ready'      => ['delivering'],
        'delivering' => ['completed']
    ];

    $currentStatus = $order['status'];
    $allowedNext = $validTransitions[$currentStatus] ?? [];

    if (!in_array($newStatus, $allowedNext)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Tidak bisa mengubah status dari '$currentStatus' ke '$newStatus'",
            'allowed' => $allowedNext
        ]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update order status
    $updateFields = ["status = ?", "updated_at = NOW()"];
    $updateParams = [$newStatus];

    if ($newStatus === 'delivering') {
        $updateFields[] = "pickup_time = NOW()";
    } elseif ($newStatus === 'completed') {
        $updateFields[] = "completed_at = NOW()";
        $updateFields[] = "delivery_time = NOW()";
        // For COD, mark as paid on delivery
        if ($order['payment_method'] === 'cod') {
            $updateFields[] = "payment_status = 'paid'";
            $updateFields[] = "paid_at = NOW()";
        }
    }

    $updateParams[] = $order['id'];
    $updateSql = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute($updateParams);

    // On completion: update kurir stats & status
    if ($newStatus === 'completed') {
        // Increment total deliveries
        $pdo->prepare("UPDATE kurir SET total_deliveries = total_deliveries + 1 WHERE id = ?")->execute([$kurirId]);

        // Check if kurir has more active orders
        $activeStmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering')
        ");
        $activeStmt->execute([$kurirId]);
        
        if ($activeStmt->fetchColumn() == 0) {
            // No more active orders → set available
            $pdo->prepare("UPDATE kurir SET status = 'available' WHERE id = ?")->execute([$kurirId]);
        }

        // Award loyalty points to customer (2 points per order)
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 2, total_successful_orders = total_successful_orders + 1 WHERE id = ?")->execute([$order['user_id']]);

        // Create notification for customer
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, 'order_completed', 'Pesanan Selesai', ?, ?, NOW())
        ");
        $notifData = json_encode(['order_id' => $order['id'], 'order_number' => $order['order_number']]);
        $notifStmt->execute([
            $order['user_id'],
            "Pesanan #{$order['order_number']} telah selesai diantar. Terima kasih!",
            $notifData
        ]);

    } elseif ($newStatus === 'delivering') {
        // Set kurir status to busy
        $pdo->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?")->execute([$kurirId]);

        // Notify customer: order is on the way
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, 'order_delivering', 'Pesanan Dalam Perjalanan', ?, ?, NOW())
        ");
        $notifData = json_encode(['order_id' => $order['id'], 'order_number' => $order['order_number']]);
        $notifStmt->execute([
            $order['user_id'],
            "Kurir sedang mengantar pesanan #{$order['order_number']} ke lokasi Anda.",
            $notifData
        ]);
    }

    // Create kurir notification log
    $kurirNotifStmt = $pdo->prepare("
        INSERT INTO kurir_notifications (kurir_id, type, title, message, order_id, created_at)
        VALUES (?, 'status_update', ?, ?, ?, NOW())
    ");
    $statusLabels = [
        'processing' => 'Pesanan Diproses',
        'ready' => 'Pesanan Siap',
        'delivering' => 'Sedang Mengantar',
        'completed' => 'Pengantaran Selesai'
    ];
    $kurirNotifStmt->execute([
        $kurirId,
        $statusLabels[$newStatus] ?? 'Status Update',
        "Status pesanan #{$order['order_number']} diperbarui ke: $newStatus",
        $order['id']
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status pesanan berhasil diperbarui',
        'data' => [
            'orderNumber' => $order['order_number'],
            'previousStatus' => $currentStatus,
            'newStatus' => $newStatus
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Kurir Update Status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Kurir Update Status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
