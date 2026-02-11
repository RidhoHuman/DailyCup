<?php
/**
 * Courier Photo Upload & Delivery Completion
 * Courier must upload photo before marking order as completed
 * 
 * POST /api/orders/courier_complete.php
 * Authorization: Bearer <JWT_TOKEN> (Admin/Courier only)
 * Content-Type: multipart/form-data
 * Body: { order_id: string, photo: file }
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
    
    if (!$decoded || !in_array($decoded->role, ['admin', 'courier'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin or courier access required']);
        exit;
    }

    // Get form data
    $orderId = $_POST['order_id'] ?? '';
    
    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id is required']);
        exit;
    }

    // Verify photo file
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Photo upload is required']);
        exit;
    }

    $photo = $_FILES['photo'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($photo['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Only JPEG, PNG, and WebP images are allowed']);
        exit;
    }

    if ($photo['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'Photo size must not exceed 5MB']);
        exit;
    }

    // Get order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    if ($order['status'] !== 'on_delivery') {
        http_response_code(400);
        echo json_encode([
            'error' => 'Order must be in on_delivery status',
            'current_status' => $order['status']
        ]);
        exit;
    }

    // Upload photo
    $uploadDir = __DIR__ . '/../../data/courier_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
    $filename = 'delivery_' . $orderId . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    if (!move_uploaded_file($photo['tmp_name'], $uploadPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload photo']);
        exit;
    }

    // Update order: set photo and mark as completed
    $photoUrl = '/backend/data/courier_photos/' . $filename;
    
    $updateStmt = $pdo->prepare("
        UPDATE orders 
        SET courier_photo = ?,
            status = 'completed',
            completed_at = NOW()
        WHERE order_id = ?
    ");
    $updateStmt->execute([$photoUrl, $orderId]);

    // Log status change
    $logStmt = $pdo->prepare("
        INSERT INTO order_status_log 
        (order_id, status, message, changed_by, changed_by_type, metadata) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $logStmt->execute([
        $orderId,
        'completed',
        'Order delivered with photo verification',
        $decoded->user_id,
        $decoded->role === 'courier' ? 'courier' : 'admin',
        json_encode(['photo_url' => $photoUrl])
    ]);

    // Mark courier as available again
    if ($order['courier_id']) {
        $courierStmt = $pdo->prepare("
            UPDATE couriers 
            SET is_available = TRUE, 
                total_deliveries = total_deliveries + 1 
            WHERE id = ?
        ");
        $courierStmt->execute([$order['courier_id']]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Order completed successfully',
        'order_id' => $orderId,
        'photo_url' => $photoUrl,
        'completed_at' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to complete order',
        'message' => $e->getMessage()
    ]);
}
?>
