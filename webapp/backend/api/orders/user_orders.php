<?php
/**
 * User Orders API
 * GET /api/orders/user_orders.php - Get orders for the authenticated user
 * 
 * Returns the logged-in user's orders with items
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Authenticate user
$user = JWT::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = isset($user['user_id']) ? (int)$user['user_id'] : (isset($user['id']) ? (int)$user['id'] : null);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user token']);
    exit;
}

try {
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;

    $sql = "SELECT 
                o.id,
                o.order_number,
                o.order_number as order_id,
                o.total_amount,
                o.discount_amount,
                o.final_amount,
                o.final_amount as total,
                o.delivery_method,
                o.delivery_address,
                o.payment_status,
                o.payment_method,
                o.status,
                o.customer_notes,
                o.created_at,
                o.updated_at
            FROM orders o
            WHERE o.user_id = ?";
    
    $params = [$userId];

    if ($status && $status !== 'all') {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY o.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each order
    foreach ($orders as &$order) {
        $itemsSql = "SELECT 
                        oi.id,
                        oi.product_id,
                        oi.product_name as name,
                        oi.product_name,
                        oi.quantity,
                        oi.unit_price as price,
                        oi.subtotal
                    FROM order_items oi
                    WHERE oi.order_id = ?";
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Type cast
        $order['id'] = (int)$order['id'];
        $order['total_amount'] = (float)$order['total_amount'];
        $order['final_amount'] = (float)$order['final_amount'];
        $order['total'] = (float)$order['total'];
        $order['discount_amount'] = (float)($order['discount_amount'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'orders' => $orders,
            'total' => count($orders)
        ]
    ]);

} catch (PDOException $e) {
    error_log("User Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
