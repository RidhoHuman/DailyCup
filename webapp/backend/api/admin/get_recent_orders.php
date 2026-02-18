<?php
require_once __DIR__ . '/../../cors.php';
/**
 * Get Recent Orders API
 *
 * Returns recent orders with customer details for admin dashboard
 * GET /api/admin/get_recent_orders.php?limit=10
 * Requires: Admin authentication
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require admin authentication
$authUser = JWT::requireAuth();
if ($authUser['role'] !== 'admin' && $authUser['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Get limit from query parameter (default 10, max 50)
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 10;

    // Fetch recent orders with customer details
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.final_amount as total,
            o.payment_status as status,
            o.created_at,
            u.name as customer_name,
            u.email as customer_email,
            (
                SELECT COUNT(*)
                FROM order_items oi
                WHERE oi.order_id = o.id
            ) as items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT :limit
    ");
    // Bind integer parameter for LIMIT (PDO quirk with execute array treats it as string)
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();

    // Format orders for frontend
    $formattedOrders = array_map(function($order) {
        return [
            'id' => $order['order_number'] ?? 'ORD-' . $order['id'],
            'customer' => $order['customer_name'] ?? 'Guest',
            'email' => $order['customer_email'] ?? '-',
            'total' => (float)$order['total'],
            'status' => $order['status'],
            'items' => (int)$order['items_count'],
            'date' => date('Y-m-d H:i:s', strtotime($order['created_at']))
        ];
    }, $orders);

    echo json_encode([
        'success' => true,
        'data' => $formattedOrders
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
