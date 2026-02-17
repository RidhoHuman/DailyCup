<?php
/**
 * Kurir Update Delivery Status API
 * Real-time status tracking for order delivery
 * 
 * Status Flow:
 * 1. going_to_store → Kurir menuju outlet
 * 2. arrived_at_store → Kurir sampai di outlet
 * 3. picked_up → Pesanan diambil, menuju customer
 * 4. nearby → Kurir mendekati lokasi customer
 * 5. delivered → Pesanan diterima (with photo proof)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $kurirId = $input['kurir_id'] ?? null;
    $orderId = $input['order_id'] ?? null;
    $newStatus = $input['status'] ?? null;
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    $notes = $input['notes'] ?? '';
    $photoProof = $input['photo_proof'] ?? null; // Base64 or URL
    
    $validStatuses = ['going_to_store', 'arrived_at_store', 'picked_up', 'nearby', 'delivered'];
    
    if (!$kurirId || !$orderId || !in_array($newStatus, $validStatuses)) {
        throw new Exception('Invalid request data');
    }
    
    // Verify kurir assignment
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name, u.phone, u.email,
               k.name as kurir_name, k.phone as kurir_phone
        FROM orders o
        JOIN users u ON u.id = o.user_id
        JOIN kurir k ON k.id = o.kurir_id
        WHERE o.id = ? AND o.kurir_id = ?
    ");
    $stmt->execute([$orderId, $kurirId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found or kurir not assigned to this order');
    }
    
    $conn->beginTransaction();
    
    // ============================================
    // STATUS: GOING TO STORE
    // ============================================
    if ($newStatus === 'going_to_store') {
        // No order status change, just log
        $notifTitle = 'Kurir Menuju Outlet';
        $notifMsg = sprintf(
            'Kurir %s sedang menuju outlet untuk mengambil pesanan #%s',
            $order['kurir_name'],
            $order['order_number']
        );
    }
    
    // ============================================
    // STATUS: ARRIVED AT STORE
    // ============================================
    else if ($newStatus === 'arrived_at_store') {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                kurir_arrived_at = NOW(),
                status = 'ready',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        $notifTitle = 'Kurir Tiba di Outlet';
        $notifMsg = sprintf(
            'Kurir %s telah tiba di outlet dan akan segera mengambil pesanan #%s',
            $order['kurir_name'],
            $order['order_number']
        );
        
        // Log status change
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, changed_by_id, notes)
            VALUES (?, ?, 'ready', 'kurir', ?, 'Kurir arrived at store')
        ");
        $stmt->execute([$orderId, $order['status'], $kurirId]);
    }
    
    // ============================================
    // STATUS: PICKED UP (ON THE WAY)
    // ============================================
    else if ($newStatus === 'picked_up') {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                pickup_time = NOW(),
                status = 'delivering',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        $notifTitle = 'Pesanan Dalam Perjalanan';
        $notifMsg = sprintf(
            '🚀 Kurir %s telah mengambil pesanan #%s dan sedang dalam perjalanan ke lokasi Anda!',
            $order['kurir_name'],
            $order['order_number']
        );
        
        // Save departure photo if provided
        if ($photoProof) {
            $photoPath = saveDeliveryPhoto($photoProof, $orderId, 'departure');
            $stmt = $conn->prepare("
                UPDATE orders 
                SET kurir_departure_photo = ?
                WHERE id = ?
            ");
            $stmt->execute([$photoPath, $orderId]);
        }
        
        // Log status change
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, changed_by_id, notes)
            VALUES (?, ?, 'delivering', 'kurir', ?, 'Order picked up from store')
        ");
        $stmt->execute([$orderId, $order['status'], $kurirId]);
    }
    
    // ============================================
    // STATUS: NEARBY
    // ============================================
    else if ($newStatus === 'nearby') {
        // Don't change order status, just notify
        $notifTitle = 'Kurir Sudah Dekat!';
        $notifMsg = sprintf(
            '📍 Kurir %s sudah dekat dengan lokasi Anda! Silakan tunggu di depan, pesanan #%s akan segera tiba.',
            $order['kurir_name'],
            $order['order_number']
        );
    }
    
    // ============================================
    // STATUS: DELIVERED
    // ============================================
    else if ($newStatus === 'delivered') {
        // Save delivery photo (REQUIRED)
        if (!$photoProof) {
            throw new Exception('Photo proof is required for delivery confirmation');
        }
        
        $photoPath = saveDeliveryPhoto($photoProof, $orderId, 'delivered');
        
        // Update order to completed
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                delivery_time = NOW(),
                status = 'completed',
                payment_status = IF(payment_method = 'cod', 'paid', payment_status),
                completed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        // Update kurir back to available
        $stmt = $conn->prepare("
            UPDATE kurir 
            SET 
                status = 'available',
                total_deliveries = total_deliveries + 1,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$kurirId]);
        
        // Update user trust score
        $stmt = $conn->prepare("
            UPDATE users 
            SET 
                trust_score = LEAST(100, trust_score + 10),
                total_successful_orders = total_successful_orders + 1
            WHERE id = ?
        ");
        $stmt->execute([$order['user_id']]);
        
        $notifTitle = '✅ Pesanan Diterima';
        $notifMsg = sprintf(
            'Pesanan #%s telah diterima dengan aman. Terima kasih telah berbelanja di DailyCup! Total pembayaran: Rp %s',
            $order['order_number'],
            number_format($order['final_amount'], 0, ',', '.')
        );
        
        // Log status change
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, changed_by_id, notes)
            VALUES (?, ?, 'completed', 'kurir', ?, ?)
        ");
        $deliveryNotes = 'Order delivered successfully with photo proof: ' . $photoPath;
        $stmt->execute([$orderId, $order['status'], $kurirId, $deliveryNotes]);
        
        // Send admin notification for COD payment received
        if ($order['payment_method'] === 'cod') {
            $stmt = $conn->prepare("
                INSERT INTO admin_notifications 
                (type, title, message, link, created_at)
                VALUES ('payment_received', 'Pembayaran COD Diterima', ?, ?, NOW())
            ");
            $adminMsg = sprintf(
                'Kurir %s telah menerima pembayaran COD sebesar Rp %s untuk pesanan #%s',
                $order['kurir_name'],
                number_format($order['final_amount'], 0, ',', '.'),
                $order['order_number']
            );
            $stmt->execute([$adminMsg, '/admin/orders/' . $order['id']]);
        }
    }
    
    // ============================================
    // SAVE TO DELIVERY HISTORY
    // ============================================
    $stmt = $conn->prepare("
        INSERT INTO delivery_history 
        (order_id, kurir_id, status, notes, location_lat, location_lng, photo_proof, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $orderId,
        $kurirId,
        $newStatus,
        $notes,
        $latitude,
        $longitude,
        $photoPath ?? null
    ]);
    
    // ============================================
    // SEND NOTIFICATION TO CUSTOMER
    // ============================================
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type, title, message, action_url, created_at)
        VALUES (?, 'order_update', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $order['user_id'],
        $notifTitle,
        $notifMsg,
        '/orders/' . $order['order_number']
    ]);
    
    // ============================================
    // UPDATE KURIR LOCATION
    // ============================================
    if ($latitude && $longitude) {
        $stmt = $conn->prepare("
            INSERT INTO kurir_location (kurir_id, latitude, longitude, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                updated_at = NOW()
        ");
        $stmt->execute([$kurirId, $latitude, $longitude]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'order_status' => $newStatus === 'delivered' ? 'completed' : ($order['status'] ?? 'processing'),
        'notification_sent' => true
    ]);
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Save delivery photo to server
 * 
 * @param string $photoData Base64 encoded image or URL
 * @param int $orderId Order ID
 * @param string $type 'departure' or 'delivered'
 * @return string File path
 */
function saveDeliveryPhoto($photoData, $orderId, $type) {
    $uploadDir = __DIR__ . '/../../../assets/images/deliveries/';
    
    // Create directory if not exists
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Check if base64
    if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $matches)) {
        $imageType = $matches[1];
        $photoData = substr($photoData, strpos($photoData, ',') + 1);
        $photoData = base64_decode($photoData);
        
        $filename = sprintf('%s_%s_%s.%s', $orderId, $type, time(), $imageType);
        $filepath = $uploadDir . $filename;
        
        file_put_contents($filepath, $photoData);
        
        return 'assets/images/deliveries/' . $filename;
    }
    
    // If URL, return as is
    return $photoData;
}
