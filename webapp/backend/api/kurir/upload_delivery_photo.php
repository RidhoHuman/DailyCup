<?php
/**
 * Kurir Delivery Photo Upload API
 * 
 * POST /api/kurir/upload_delivery_photo.php
 * Content-Type: multipart/form-data
 * Body: 
 *   - photo (file): Image file (required)
 *   - order_id (string|int): Order ID or order number
 *   - type (string): 'departure' or 'arrival'
 *   - latitude (float): GPS latitude (optional)
 *   - longitude (float): GPS longitude (optional)
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
    // Validate inputs
    $orderId = $_POST['order_id'] ?? null;
    $type = $_POST['type'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'order_id wajib diisi']);
        exit;
    }

    if (!in_array($type, ['departure', 'arrival'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "type harus 'departure' atau 'arrival'"]);
        exit;
    }

    // Validate photo file
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Foto wajib diupload';
        if (isset($_FILES['photo'])) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'Ukuran file terlalu besar (batas server)',
                UPLOAD_ERR_FORM_SIZE => 'Ukuran file terlalu besar',
                UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
            ];
            $errorMsg = $uploadErrors[$_FILES['photo']['error']] ?? 'Upload gagal';
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit;
    }

    $file = $_FILES['photo'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Format file harus JPEG, PNG, atau WebP']);
        exit;
    }

    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ukuran file maksimal 5MB']);
        exit;
    }

    // Find order
    $orderWhere = is_numeric($orderId) ? "o.id = ?" : "o.order_number = ?";
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number, o.status, o.kurir_id, o.user_id,
               o.kurir_departure_photo, o.kurir_arrival_photo
        FROM orders o
        WHERE $orderWhere
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
        exit;
    }

    // Verify this order belongs to this kurir
    if ((int)$order['kurir_id'] !== (int)$kurirId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Pesanan ini bukan milik Anda']);
        exit;
    }

    // Validate status for photo type
    if ($type === 'departure') {
        if (!in_array($order['status'], ['ready', 'processing'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Foto keberangkatan hanya bisa diupload saat status ready/processing']);
            exit;
        }
    } else { // arrival
        if ($order['status'] !== 'delivering') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Foto tiba hanya bisa diupload saat status delivering']);
            exit;
        }
    }

    // Create upload directory
    $uploadDir = __DIR__ . '/../../uploads/delivery/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = $type . '_' . $order['id'] . '_' . uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan file']);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update order with photo path and change status
    $photoColumn = $type === 'departure' ? 'kurir_departure_photo' : 'kurir_arrival_photo';
    $photoUrl = 'uploads/delivery/' . $filename;

    if ($type === 'departure') {
        // Departure photo → status changes to 'delivering'
        $updateStmt = $pdo->prepare("
            UPDATE orders SET 
                kurir_departure_photo = ?,
                status = 'delivering',
                pickup_time = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$photoUrl, $order['id']]);

        // Set kurir status to busy
        $pdo->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?")->execute([$kurirId]);

        // Notify customer
        $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, 'order_delivering', 'Pesanan Dalam Perjalanan', ?, ?, NOW())
        ")->execute([
            $order['user_id'],
            "Kurir sedang mengantar pesanan #{$order['order_number']} ke lokasi Anda.",
            json_encode(['order_id' => $order['id'], 'order_number' => $order['order_number']])
        ]);

    } else {
        // Arrival photo → status changes to 'completed'
        $pickupTime = $pdo->prepare("SELECT pickup_time FROM orders WHERE id = ?");
        $pickupTime->execute([$order['id']]);
        $pickup = $pickupTime->fetchColumn();

        $deliveryMinutes = null;
        if ($pickup) {
            $deliveryMinutes = (int) round((time() - strtotime($pickup)) / 60);
        }

        $updateStmt = $pdo->prepare("
            UPDATE orders SET 
                kurir_arrival_photo = ?,
                status = 'completed',
                completed_at = NOW(),
                delivery_time = NOW(),
                actual_delivery_time = ?,
                updated_at = NOW(),
                payment_status = CASE WHEN payment_method = 'cod' THEN 'paid' ELSE payment_status END,
                paid_at = CASE WHEN payment_method = 'cod' THEN NOW() ELSE paid_at END
            WHERE id = ?
        ");
        $updateStmt->execute([$photoUrl, $deliveryMinutes, $order['id']]);

        // Update kurir stats
        $pdo->prepare("UPDATE kurir SET total_deliveries = total_deliveries + 1 WHERE id = ?")->execute([$kurirId]);

        // Check if kurir has more active orders
        $activeStmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering') AND id != ?
        ");
        $activeStmt->execute([$kurirId, $order['id']]);
        if ($activeStmt->fetchColumn() == 0) {
            $pdo->prepare("UPDATE kurir SET status = 'available' WHERE id = ?")->execute([$kurirId]);
        }

        // Award loyalty points to customer
        $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 2, total_successful_orders = total_successful_orders + 1 WHERE id = ?")->execute([$order['user_id']]);

        // Notify customer
        $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, data, created_at)
            VALUES (?, 'order_completed', 'Pesanan Selesai', ?, ?, NOW())
        ")->execute([
            $order['user_id'],
            "Pesanan #{$order['order_number']} telah berhasil diantar. Terima kasih!",
            json_encode(['order_id' => $order['id'], 'order_number' => $order['order_number']])
        ]);
    }

    // Log kurir notification
    $pdo->prepare("
        INSERT INTO kurir_notifications (kurir_id, type, title, message, order_id, created_at)
        VALUES (?, 'delivery_photo', ?, ?, ?, NOW())
    ")->execute([
        $kurirId,
        $type === 'departure' ? 'Foto Keberangkatan' : 'Foto Pengantaran',
        "Foto " . ($type === 'departure' ? 'keberangkatan' : 'sampai tujuan') . " pesanan #{$order['order_number']} berhasil diupload",
        $order['id']
    ]);

    $pdo->commit();

    $newStatus = $type === 'departure' ? 'delivering' : 'completed';

    echo json_encode([
        'success' => true,
        'message' => $type === 'departure' 
            ? 'Foto keberangkatan berhasil diupload. Status diubah ke: Sedang Mengantar' 
            : 'Foto pengantaran berhasil diupload. Pesanan selesai!',
        'data' => [
            'orderNumber' => $order['order_number'],
            'photoUrl' => $photoUrl,
            'previousStatus' => $order['status'],
            'newStatus' => $newStatus,
            'actualDeliveryTime' => $type === 'arrival' ? ($deliveryMinutes ?? null) : null,
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Upload delivery photo error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Upload delivery photo error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
