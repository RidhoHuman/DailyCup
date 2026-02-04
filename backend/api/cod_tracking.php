<?php
/**
 * COD Tracking API
 * 
 * Endpoints for managing COD (Cash on Delivery) orders
 */

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once '../config/database.php';
require_once __DIR__ . '/notifications/NotificationService.php';

header('Content-Type: application/json');

// Verify authentication
$authUser = JWT::getUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $authUser['user_id'] ?? null;
$userRole = $authUser['role'] ?? 'customer';

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// GET /cod_tracking.php - Get COD tracking info
if ($method === 'GET') {
    // Admin can get all COD orders
    if (isset($_GET['all']) && $_GET['all'] == '1' && $userRole === 'admin') {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    ct.*,
                    o.order_number,
                    o.customer_name,
                    o.customer_phone,
                    o.customer_address,
                    o.total,
                    o.status as order_status
                FROM cod_tracking ct
                JOIN orders o ON ct.order_id = o.order_number
                WHERE o.payment_method = 'cod'
                ORDER BY ct.created_at DESC
            ");
            $stmt->execute();
            $allOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $allOrders,
                'total' => count($allOrders)
            ]);
            exit;
        } catch (PDOException $e) {
            error_log("COD Tracking Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
    }
    
    $orderId = $_GET['order_id'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        exit;
    }
    
    try {
        // Verify order ownership (unless admin)
        if ($userRole !== 'admin') {
            $checkStmt = $pdo->prepare("
                SELECT o.id FROM orders o 
                WHERE o.order_number = ? AND o.user_id = ?
            ");
            $checkStmt->execute([$orderId, $userId]);
            if (!$checkStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
                exit;
            }
        }
        
        // Get tracking info
        $stmt = $pdo->prepare("
            SELECT 
                ct.*,
                o.order_number,
                o.customer_name,
                o.customer_phone,
                o.customer_address,
                o.total,
                o.status as order_status
            FROM cod_tracking ct
            JOIN orders o ON ct.order_id = o.order_number
            WHERE ct.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tracking) {
            // Return basic info if no tracking exists yet
            $orderStmt = $pdo->prepare("
                SELECT order_number, customer_name, customer_phone, customer_address, total, status
                FROM orders WHERE order_number = ?
            ");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'tracking' => null,
                'order' => $order,
                'message' => 'No tracking info available yet'
            ]);
            exit;
        }
        
        // Get status history
        $historyStmt = $pdo->prepare("
            SELECT * FROM cod_status_history 
            WHERE order_id = ? 
            ORDER BY created_at ASC
        ");
        $historyStmt->execute([$orderId]);
        $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'tracking' => $tracking,
            'history' => $history
        ]);
        
    } catch (PDOException $e) {
        error_log("COD Tracking Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

// POST /cod_tracking.php - Create or update COD tracking (Admin only)
if ($method === 'POST') {
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    $orderId = $input['order_id'] ?? null;
    $action = $input['action'] ?? 'update'; // update, create, update_status, confirm_payment
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if order exists and is COD
        $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        if ($order['payment_method'] !== 'cod') {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order is not COD']);
            exit;
        }
        
        if ($action === 'create') {
            // Create new COD tracking
            $stmt = $pdo->prepare("
                INSERT INTO cod_tracking (
                    order_id, courier_name, courier_phone, tracking_number, 
                    status, notes, admin_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $input['courier_name'] ?? null,
                $input['courier_phone'] ?? null,
                $input['tracking_number'] ?? null,
                $input['status'] ?? 'pending',
                $input['notes'] ?? null,
                $input['admin_notes'] ?? null
            ]);
            
            // Log history
            $historyStmt = $pdo->prepare("
                INSERT INTO cod_status_history (order_id, status, changed_by_user_id, notes)
                VALUES (?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $orderId,
                $input['status'] ?? 'pending',
                $userId,
                'COD tracking created'
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'COD tracking created']);
            
        } elseif ($action === 'update_status') {
            // Update COD status
            $newStatus = $input['status'] ?? null;
            $validStatuses = ['pending', 'confirmed', 'packed', 'out_for_delivery', 'delivered', 'payment_received', 'cancelled'];
            
            if (!in_array($newStatus, $validStatuses)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            // Prepare timestamp field
            $timestampField = null;
            switch ($newStatus) {
                case 'confirmed': $timestampField = 'confirmed_at'; break;
                case 'packed': $timestampField = 'packed_at'; break;
                case 'out_for_delivery': $timestampField = 'out_for_delivery_at'; break;
                case 'delivered': $timestampField = 'delivered_at'; break;
            }
            
            // Check if tracking exists, create if not
            $checkStmt = $pdo->prepare("SELECT id FROM cod_tracking WHERE order_id = ?");
            $checkStmt->execute([$orderId]);
            if (!$checkStmt->fetch()) {
                $createStmt = $pdo->prepare("INSERT INTO cod_tracking (order_id, status) VALUES (?, ?)");
                $createStmt->execute([$orderId, $newStatus]);
            } else {
                // Update status
                if ($timestampField) {
                    $updateStmt = $pdo->prepare("
                        UPDATE cod_tracking 
                        SET status = ?, {$timestampField} = NOW(), notes = ?
                        WHERE order_id = ?
                    ");
                    $updateStmt->execute([$newStatus, $input['notes'] ?? null, $orderId]);
                } else {
                    $updateStmt = $pdo->prepare("
                        UPDATE cod_tracking 
                        SET status = ?, notes = ?
                        WHERE order_id = ?
                    ");
                    $updateStmt->execute([$newStatus, $input['notes'] ?? null, $orderId]);
                }
            }
            
            // Log history
            $historyStmt = $pdo->prepare("
                INSERT INTO cod_status_history (order_id, status, changed_by_user_id, notes)
                VALUES (?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $orderId,
                $newStatus,
                $userId,
                $input['notes'] ?? null
            ]);
            
            // Send notification to customer
            if ($order['user_id']) {
                $statusMessages = [
                    'confirmed' => 'Pesanan Anda telah dikonfirmasi dan sedang disiapkan',
                    'packed' => 'Pesanan Anda sudah dikemas dan siap dikirim',
                    'out_for_delivery' => 'Pesanan Anda sedang dalam perjalanan pengiriman',
                    'delivered' => 'Pesanan Anda telah sampai! Terima kasih sudah berbelanja',
                    'payment_received' => 'Pembayaran COD Anda telah diterima. Terima kasih!',
                    'cancelled' => 'Pesanan Anda telah dibatalkan'
                ];
                
                NotificationService::create([
                    'user_id' => $order['user_id'],
                    'title' => 'Status Pesanan COD: ' . strtoupper($newStatus),
                    'message' => $statusMessages[$newStatus] ?? 'Status pesanan diperbarui',
                    'type' => 'order_update'
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Status updated', 'status' => $newStatus]);
            
        } elseif ($action === 'confirm_payment') {
            // Confirm COD payment received
            $paymentAmount = $input['payment_amount'] ?? $order['total'];
            
            $stmt = $pdo->prepare("
                UPDATE cod_tracking 
                SET 
                    payment_received = 1, 
                    payment_received_at = NOW(),
                    payment_amount = ?,
                    payment_notes = ?,
                    status = 'payment_received',
                    receiver_name = ?,
                    receiver_relation = ?
                WHERE order_id = ?
            ");
            $stmt->execute([
                $paymentAmount,
                $input['payment_notes'] ?? null,
                $input['receiver_name'] ?? null,
                $input['receiver_relation'] ?? null,
                $orderId
            ]);
            
            // Update order status
            $orderUpdateStmt = $pdo->prepare("UPDATE orders SET status = 'paid', paid_at = NOW() WHERE order_number = ?");
            $orderUpdateStmt->execute([$orderId]);
            
            // Log history
            $historyStmt = $pdo->prepare("
                INSERT INTO cod_status_history (order_id, status, changed_by_user_id, notes)
                VALUES (?, ?, ?, ?)
            ");
            $historyStmt->execute([
                $orderId,
                'payment_received',
                $userId,
                'Payment confirmed: Rp ' . number_format($paymentAmount, 0, ',', '.')
            ]);
            
            // Send notification
            if ($order['user_id']) {
                NotificationService::create([
                    'user_id' => $order['user_id'],
                    'title' => 'Pembayaran COD Diterima',
                    'message' => 'Pembayaran COD sebesar Rp ' . number_format($paymentAmount, 0, ',', '.') . ' telah diterima. Terima kasih!',
                    'type' => 'payment_success'
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Payment confirmed']);
            
        } else {
            // General update (assign courier, etc)
            $fields = [];
            $values = [];
            
            if (isset($input['courier_name'])) {
                $fields[] = 'courier_name = ?';
                $values[] = $input['courier_name'];
            }
            if (isset($input['courier_phone'])) {
                $fields[] = 'courier_phone = ?';
                $values[] = $input['courier_phone'];
            }
            if (isset($input['tracking_number'])) {
                $fields[] = 'tracking_number = ?';
                $values[] = $input['tracking_number'];
            }
            if (isset($input['notes'])) {
                $fields[] = 'notes = ?';
                $values[] = $input['notes'];
            }
            if (isset($input['admin_notes'])) {
                $fields[] = 'admin_notes = ?';
                $values[] = $input['admin_notes'];
            }
            
            if (empty($fields)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No fields to update']);
                exit;
            }
            
            // Check if tracking exists, create if not
            $checkStmt = $pdo->prepare("SELECT id FROM cod_tracking WHERE order_id = ?");
            $checkStmt->execute([$orderId]);
            if (!$checkStmt->fetch()) {
                // Create new tracking entry
                $createStmt = $pdo->prepare("INSERT INTO cod_tracking (order_id) VALUES (?)");
                $createStmt->execute([$orderId]);
            }
            
            $values[] = $orderId;
            $sql = "UPDATE cod_tracking SET " . implode(', ', $fields) . " WHERE order_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            
            // Log if courier assigned
            if (isset($input['courier_name'])) {
                $historyStmt = $pdo->prepare("
                    INSERT INTO cod_status_history (order_id, status, changed_by_user_id, notes)
                    VALUES (?, ?, ?, ?)
                ");
                $historyStmt->execute([
                    $orderId,
                    'confirmed',
                    $userId,
                    'Courier assigned: ' . $input['courier_name']
                ]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'COD tracking updated']);
        }
                UPDATE cod_tracking 
                SET 
                    courier_name = ?,
                    courier_phone = ?,
                    tracking_number = ?,
                    notes = ?,
                    admin_notes = ?
                WHERE order_id = ?
            ");
            $stmt->execute([
                $input['courier_name'] ?? null,
                $input['courier_phone'] ?? null,
                $input['tracking_number'] ?? null,
                $input['notes'] ?? null,
                $input['admin_notes'] ?? null,
                $orderId
            ]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Tracking info updated']);
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("COD Tracking Update Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
