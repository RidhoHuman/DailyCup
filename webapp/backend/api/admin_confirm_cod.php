<?php
/**
 * Admin Confirm COD Order API
 * Manual confirmation for COD orders
 * 
 * Actions:
 * - approve: Confirm COD order → assign kurir
 * - reject: Cancel order + optional blacklist
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../cors.php';
// CORS handled centrally (cors.php / .htaccess)

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Authenticate admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$adminId = $_SESSION['user_id'];

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $orderId = $input['order_id'] ?? null;
    $action = $input['action'] ?? null; // 'approve' or 'reject'
    $reason = $input['reason'] ?? '';
    $isFraud = $input['is_fraud'] ?? false; // Blacklist user if fraud
    
    if (!$orderId || !in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid request data');
    }
    
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, u.name as customer_name, u.phone, u.email,
               u.cod_blacklisted, u.total_successful_orders
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.id = ? AND o.payment_method = 'cod'
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found or not a COD order');
    }
    
    $conn->beginTransaction();
    
    // ============================================
    // ACTION: APPROVE COD ORDER
    // ============================================
    if ($action === 'approve') {
        // Update order status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                status = 'confirmed',
                payment_status = 'pending',
                admin_confirmed_at = NOW(),
                admin_confirmed_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$adminId, $orderId]);
        
        // Insert status log
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, changed_by_id, notes)
            VALUES (?, ?, 'confirmed', 'admin', ?, 'COD order approved by admin')
        ");
        $stmt->execute([$orderId, $order['status'], $adminId]);
        
        // Send notification to customer
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, action_url, created_at)
            VALUES (?, 'order_confirmed', 'Pesanan Dikonfirmasi', ?, ?, NOW())
        ");
        $customerMsg = sprintf(
            'Pesanan #%s telah dikonfirmasi admin. Kurir akan segera ditugaskan untuk mengambil pesanan Anda.',
            $order['order_number']
        );
        $stmt->execute([
            $order['user_id'],
            $customerMsg,
            '/orders/' . $order['order_number']
        ]);
        
        // ============================================
        // AUTO ASSIGN KURIR
        // ============================================
        $kurirStmt = $conn->prepare("
            SELECT id, name, phone 
            FROM kurir 
            WHERE status = 'available' 
            AND is_active = 1
            ORDER BY RAND()
            LIMIT 1
        ");
        $kurirStmt->execute();
        $kurir = $kurirStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($kurir) {
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
            $stmt->execute([$kurir['id'], $orderId]);
            
            // Update kurir status
            $stmt = $conn->prepare("
                UPDATE kurir 
                SET status = 'busy', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$kurir['id']]);
            
            // Send notification to kurir
            $stmt = $conn->prepare("
                INSERT INTO kurir_notifications 
                (kurir_id, type, title, message, order_id, is_read, created_at)
                VALUES (?, 'new_order', 'Pesanan Baru', ?, ?, 0, NOW())
            ");
            $kurirMsg = sprintf(
                'Pesanan baru #%s menunggu diambil. Total: Rp %s. Pembayaran: COD',
                $order['order_number'],
                number_format($order['final_amount'], 0, ',', '.')
            );
            $stmt->execute([$kurir['id'], $kurirMsg, $orderId]);
            
            // Log status change
            $stmt = $conn->prepare("
                INSERT INTO order_status_logs 
                (order_id, from_status, to_status, changed_by_type, notes)
                VALUES (?, 'confirmed', 'processing', 'system', ?)
            ");
            $logNote = sprintf('Auto-assigned to kurir: %s (ID: %d)', $kurir['name'], $kurir['id']);
            $stmt->execute([$orderId, $logNote]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'COD order approved and kurir assigned',
            'order_status' => 'processing',
            'kurir_assigned' => $kurir ? true : false,
            'kurir_name' => $kurir['name'] ?? null
        ]);
        
    }
    // ============================================
    // ACTION: REJECT COD ORDER
    // ============================================
    else if ($action === 'reject') {
        // Cancel order
        $stmt = $conn->prepare("
            UPDATE orders 
            SET 
                status = 'cancelled',
                cancellation_reason = ?,
                admin_confirmed_at = NOW(),
                admin_confirmed_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $cancelReason = $reason ?: 'Order ditolak oleh admin';
        $stmt->execute([$cancelReason, $adminId, $orderId]);
        
        // Insert status log
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, changed_by_id, reason)
            VALUES (?, ?, 'cancelled', 'admin', ?, ?)
        ");
        $stmt->execute([$orderId, $order['status'], $adminId, $cancelReason]);
        
        // Send notification to customer
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, action_url, created_at)
            VALUES (?, 'order_cancelled', 'Pesanan Dibatalkan', ?, ?, NOW())
        ");
        $customerMsg = sprintf(
            'Pesanan #%s dibatalkan. Alasan: %s',
            $order['order_number'],
            $cancelReason
        );
        $stmt->execute([
            $order['user_id'],
            $customerMsg,
            '/orders/' . $order['order_number']
        ]);
        
        // ============================================
        // BLACKLIST USER IF FRAUD
        // ============================================
        if ($isFraud) {
            // Blacklist user from COD
            $stmt = $conn->prepare("
                UPDATE users 
                SET 
                    cod_blacklisted = 1,
                    cod_enabled = 0,
                    blacklist_reason = ?,
                    blacklist_date = NOW()
                WHERE id = ?
            ");
            $blacklistReason = 'Fake/fraud COD order: ' . $cancelReason;
            $stmt->execute([$blacklistReason, $order['user_id']]);
            
            // Log fraud activity
            $stmt = $conn->prepare("
                INSERT INTO user_fraud_logs 
                (user_id, order_id, fraud_type, severity, description, admin_action, reported_by)
                VALUES (?, ?, 'fake_order', 'high', ?, 'cod_ban', ?)
            ");
            $stmt->execute([
                $order['user_id'],
                $orderId,
                $cancelReason,
                $adminId
            ]);
            
            // Send warning notification to user
            $stmt = $conn->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, created_at)
                VALUES (?, 'account_warning', '⚠️ Akun Anda Diblokir dari COD', ?, NOW())
            ");
            $warningMsg = sprintf(
                'Akun Anda telah diblokir dari menggunakan COD karena: %s. Silakan hubungi customer service jika Anda merasa ini kesalahan.',
                $cancelReason
            );
            $stmt->execute([$order['user_id'], $warningMsg]);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'COD order rejected' . ($isFraud ? ' and user blacklisted' : ''),
            'order_status' => 'cancelled',
            'user_blacklisted' => $isFraud
        ]);
    }
    
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
