<?php
/**
 * Manual Assign Kurir API
 * Allows admin to manually assign or reassign kurir to order
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../cors.php';
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Add admin authentication
// For now, accept requests (add JWT/session check later)

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = $input['order_id'] ?? null;
    $kurirId = $input['kurir_id'] ?? null;
    $adminId = $input['admin_id'] ?? 1; // TODO: Get from session/JWT
    $notes = $input['notes'] ?? '';
    
    if (!$orderId || !$kurirId) {
        throw new Exception('Order ID and Kurir ID are required');
    }
    
    // Verify order exists and is in valid status
    $stmt = $conn->prepare("
        SELECT id, order_number, status, kurir_id as current_kurir_id
        FROM orders 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    if (!in_array($order['status'], ['pending', 'confirmed', 'processing', 'ready', 'packed', 'delivering'])) {
        throw new Exception('Order cannot be reassigned in current status: ' . $order['status']);
    }
    
    // Verify kurir exists and is available
    $stmt = $conn->prepare("
        SELECT id, name, phone, status, is_active
        FROM kurir 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->bind_param("i", $kurirId);
    $stmt->execute();
    $result = $stmt->get_result();
    $kurir = $result->fetch_assoc();
    $stmt->close();
    
    if (!$kurir) {
        throw new Exception('Kurir not found or inactive');
    }
    
    $conn->begin_transaction();
    
    try {
        $previousKurirId = $order['current_kurir_id'];
        
        // If reassigning, free up previous kurir
        if ($previousKurirId && $previousKurirId != $kurirId) {
            $stmt = $conn->prepare("
                UPDATE kurir 
                SET status = 'available', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $previousKurirId);
            $stmt->execute();
            $stmt->close();
            
            // Notify previous kurir
            $stmt = $conn->prepare("
                INSERT INTO kurir_notifications 
                (kurir_id, type, title, message, order_id, created_at)
                VALUES (?, 'order_reassigned', 'Pesanan Dialihkan', ?, ?, NOW())
            ");
            $reassignMsg = sprintf(
                'Pesanan #%s telah dialihkan ke kurir lain oleh admin',
                $order['order_number']
            );
            $stmt->bind_param("isi", $previousKurirId, $reassignMsg, $orderId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Assign kurir to order
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                kurir_id = ?,
                assigned_at = NOW(),
                status = 'processing',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $kurirId, $orderId);
        $stmt->execute();
        $stmt->close();
        
        // Update kurir status to busy
        $stmt = $conn->prepare("
            UPDATE kurir 
            SET status = 'busy', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $kurirId);
        $stmt->execute();
        $stmt->close();
        
        // Send notification to new kurir
        $stmt = $conn->prepare("
            INSERT INTO kurir_notifications 
            (kurir_id, type, title, message, order_id, is_read, created_at)
            VALUES (?, 'new_order', 'Pesanan Baru Ditugaskan', ?, ?, 0, NOW())
        ");
        $kurirMsg = sprintf(
            'Anda ditugaskan pesanan #%s oleh admin. Segera ambil pesanan di outlet.',
            $order['order_number']
        );
        $stmt->bind_param("isi", $kurirId, $kurirMsg, $orderId);
        $stmt->execute();
        $stmt->close();
        
        // Send notification to customer
        $stmt = $conn->prepare("
            SELECT user_id FROM orders WHERE id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $userId = $stmt->get_result()->fetch_assoc()['user_id'];
        $stmt->close();
        
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, action_url, created_at)
            VALUES (?, 'order_update', 'Kurir Ditugaskan', ?, ?, NOW())
        ");
        $customerMsg = sprintf(
            'Kurir %s telah ditugaskan untuk pesanan #%s Anda',
            $kurir['name'],
            $order['order_number']
        );
        $link = '/orders/' . $order['order_number'];
        $stmt->bind_param("iss", $userId, $customerMsg, $link);
        $stmt->execute();
        $stmt->close();
        
        // Log status change
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, changed_by_id, notes)
            VALUES (?, ?, 'processing', 'admin', ?, ?)
        ");
        $logNotes = sprintf(
            'Manually assigned to kurir: %s (ID: %d). %s',
            $kurir['name'],
            $kurirId,
            $notes ?: 'No additional notes'
        );
        $stmt->bind_param("isis", $orderId, $order['status'], $adminId, $logNotes);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kurir assigned successfully',
            'order_id' => $orderId,
            'kurir' => [
                'id' => $kurir['id'],
                'name' => $kurir['name'],
                'phone' => $kurir['phone']
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
