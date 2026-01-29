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

$method = $_SERVER['REQUEST_METHOD'];

// Only allow GET for this endpoint
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get query parameters
    $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
    $orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // Build query based on parameters
    if ($orderId) {
        // Get specific order by order_number
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.user_id,
                    o.final_amount as total,
                    o.payment_status,
                    o.payment_method,
                    o.status,
                    o.notes,
                    o.created_at,
                    o.updated_at,
                    u.name as customer_name,
                    u.email as customer_email,
                    u.phone as customer_phone,
                    COUNT(oi.id) as items_count
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON oi.order_id = o.id
                WHERE o.order_number = ?
                GROUP BY o.id
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
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
                        oi.quantity,
                        oi.price,
                        oi.subtotal,
                        p.name as product_name,
                        p.image as product_image
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $order
        ]);
        
    } elseif ($customerId) {
        // Get orders for specific customer
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.final_amount as total,
                    o.payment_status,
                    o.payment_method,
                    o.status,
                    o.created_at as date,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $orders
        ]);
        
    } else {
        // Get all orders
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.final_amount as total,
                    o.payment_status,
                    o.payment_method,
                    o.status,
                    o.created_at as date,
                    u.name as customer,
                    u.email,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $orders
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
