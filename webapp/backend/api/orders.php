<?php
/**
 * Orders API
 * GET /api/orders.php - Get all orders or filtered orders
 * GET /api/orders.php?customer_id=X - Get orders for specific customer
 * GET /api/orders.php?order_id=X - Get specific order
 */

// CORS handled by .htaccess

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle PUT requests (for updating orders)
if ($method === 'PUT') {
    // Require admin authentication
    $authUser = JWT::requireAdmin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = isset($_GET['id']) ? $_GET['id'] : null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Order ID required']);
        exit;
    }
    
    try {
        // First, get current order data to check payment_method
        $stmt = $pdo->prepare("SELECT payment_method, payment_status FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentOrder) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        $autoUpdateMessage = '';
        
        // Handle order status update
        if (isset($input['status'])) {
            $newStatus = $input['status'];
            $updates[] = "status = ?";
            $params[] = $newStatus;
            
            // AUTO PAYMENT STATUS LOGIC
            // Rule 1: Completed + Cash = Auto Paid
            if ($newStatus === 'completed' && strtolower($currentOrder['payment_method']) === 'cash') {
                $updates[] = "payment_status = ?";
                $params[] = 'paid';
                $autoUpdateMessage = ' | Payment auto-updated to PAID (Cash payment)';
                error_log("[Orders] Auto-update: Order #{$orderId} completed with cash → payment_status = paid");
            }
            
            // Rule 2: Cancelled = Auto Failed
            elseif ($newStatus === 'cancelled') {
                $updates[] = "payment_status = ?";
                $params[] = 'failed';
                $autoUpdateMessage = ' | Payment auto-updated to FAILED (Order cancelled)';
                error_log("[Orders] Auto-update: Order #{$orderId} cancelled → payment_status = failed");
            }
        }
        
        // Manual payment status update (only if not auto-updated above)
        // This allows admin to manually override if needed, or update for transfer/qris
        if (isset($input['payment_status']) && empty($autoUpdateMessage)) {
            $updates[] = "payment_status = ?";
            $params[] = $input['payment_status'];
        }
        
        // Kurir assignment
        if (isset($input['kurir_id'])) {
            $updates[] = "kurir_id = ?";
            $params[] = $input['kurir_id'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit;
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $orderId;
        
        $sql = "UPDATE orders SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order updated successfully' . $autoUpdateMessage
        ]);
        exit;
        
    } catch (PDOException $e) {
        error_log("Order update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update order']);
        exit;
    }
}

// Only allow GET and PUT
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get query parameters
    $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    // Accept both 'id' and 'order_id' for single order query
    $orderId = isset($_GET['id']) ? $_GET['id'] : (isset($_GET['order_id']) ? $_GET['order_id'] : null);
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $paymentStatus = isset($_GET['payment']) ? trim($_GET['payment']) : null;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // Build query based on parameters
    if ($orderId) {
        // Get specific order by order_number
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.user_id,
                    o.total_amount,
                    o.discount_amount,
                    o.final_amount,
                    o.delivery_method,
                    o.delivery_address,
                    o.payment_status,
                    o.payment_method,
                    o.status,
                    o.customer_notes as notes,
                    o.created_at,
                    o.updated_at,
                    o.kurir_id,
                    k.name as kurir_name,
                    u.name as customer_name,
                    u.email as email,
                    u.phone as phone,
                    COUNT(oi.id) as items_count
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON oi.order_id = o.id
                LEFT JOIN kurir k ON o.kurir_id = k.id
                WHERE o.id = ? OR o.order_number = ?
                GROUP BY o.id
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId, $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            exit;
        }
        
        // Get order items
        $itemsSql = "SELECT 
                        oi.id,
                        oi.product_id,
                        oi.product_name,
                        oi.quantity,
                        oi.unit_price as price,
                        oi.subtotal
                    FROM order_items oi
                    WHERE oi.order_id = ?";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
        
    } elseif ($customerId) {
        // Get orders for specific customer
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.total_amount,
                    o.discount_amount,
                    o.final_amount,
                    o.payment_status,
                    o.payment_method,
                    o.status,
                    o.customer_notes as notes,
                    o.created_at,
                    o.updated_at,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $orders,
            'orders' => $orders
        ]);
        
    } else {
        // Get all orders with optional filters
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.user_id,
                    o.total_amount,
                    o.discount_amount,
                    o.points_used,
                    o.points_value,
                    o.final_amount,
                    o.delivery_method,
                    o.delivery_address,
                    o.payment_status,
                    o.payment_method,
                    o.status,
                    o.customer_notes as notes,
                    o.created_at,
                    o.updated_at,
                    u.name as customer_name,
                    u.email,
                    u.phone,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id";
        
        // Build WHERE clause dynamically
        $whereClauses = [];
        $params = [];
        
        if ($status) {
            $whereClauses[] = "o.status = ?";
            $params[] = $status;
        }
        
        if ($paymentStatus) {
            $whereClauses[] = "o.payment_status = ?";
            $params[] = $paymentStatus;
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add items if requested
        if (isset($_GET['include_items']) && $_GET['include_items'] == '1') {
            foreach ($orders as &$order) {
                $itemsSql = "SELECT 
                                oi.id,
                                oi.product_id,
                                oi.product_name,
                                oi.quantity,
                                oi.unit_price as price,
                                oi.subtotal
                            FROM order_items oi
                            WHERE oi.order_id = ?";
                $itemsStmt = $pdo->prepare($itemsSql);
                $itemsStmt->execute([$order['id']]);
                $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $orders,
            'orders' => $orders,
            'filters' => [
                'status' => $status,
                'payment' => $paymentStatus
            ],
            'total' => count($orders)
        ]);
    }

} catch (PDOException $e) {
    error_log("Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'message' => $e->getMessage() // Helpful for debugging
    ]);
}
