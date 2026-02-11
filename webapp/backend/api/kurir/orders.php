<?php
/**
 * Kurir Orders API
 * 
 * GET /api/kurir/orders.php                    — Get assigned orders (active)
 * GET /api/kurir/orders.php?status=completed   — Get completed orders (history)
 * GET /api/kurir/orders.php?status=all         — Get all orders
 * GET /api/kurir/orders.php?date=2026-02-08    — Filter by date
 */

require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

// Auth check - must be kurir
$authUser = JWT::getUser();
if (!$authUser || ($authUser['role'] ?? '') !== 'kurir') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Kurir authentication required']);
    exit;
}

$kurirId = $authUser['kurir_id'] ?? $authUser['user_id'];
$statusFilter = $_GET['status'] ?? 'active';
$dateFilter = $_GET['date'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

try {
    // Build WHERE clause
    $where = ["o.kurir_id = ?"];
    $params = [$kurirId];

    if ($statusFilter === 'active') {
        $where[] = "o.status IN ('confirmed', 'processing', 'ready', 'delivering')";
    } elseif ($statusFilter === 'completed') {
        $where[] = "o.status = 'completed'";
    } elseif ($statusFilter === 'cancelled') {
        $where[] = "o.status = 'cancelled'";
    }
    // 'all' = no status filter

    if ($dateFilter) {
        $where[] = "DATE(o.created_at) = ?";
        $params[] = $dateFilter;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereClause");
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();

    // Get orders with customer info (add LIMIT directly to avoid PDO int binding issues)
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.payment_method,
            o.payment_status,
            o.delivery_method,
            o.delivery_address,
            o.customer_notes,
            o.total_amount,
            o.final_amount,
            o.created_at,
            o.updated_at,
            o.assigned_at,
            o.pickup_time,
            o.delivery_time,
            o.completed_at,
            u.name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE $whereClause
        ORDER BY 
            CASE o.status 
                WHEN 'delivering' THEN 1
                WHEN 'ready' THEN 2
                WHEN 'processing' THEN 3
                WHEN 'confirmed' THEN 4
                ELSE 5
            END,
            o.created_at DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Format response
    $formattedOrders = [];
    foreach ($orders as $order) {
        $formattedOrders[] = [
            'id' => (int)$order['id'],
            'orderNumber' => $order['order_number'],
            'status' => $order['status'],
            'paymentMethod' => $order['payment_method'],
            'paymentStatus' => $order['payment_status'],
            'deliveryMethod' => $order['delivery_method'],
            'deliveryAddress' => $order['delivery_address'],
            'customerNotes' => $order['customer_notes'],
            'totalAmount' => (float)$order['total_amount'],
            'finalAmount' => (float)$order['final_amount'],
            'customer' => [
                'name' => $order['customer_name'],
                'phone' => $order['customer_phone'],
                'email' => $order['customer_email']
            ],
            'itemCount' => (int)$order['item_count'],
            'createdAt' => $order['created_at'],
            'assignedAt' => $order['assigned_at'],
            'completedAt' => $order['completed_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedOrders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalOrders,
            'totalPages' => ceil($totalOrders / $limit)
        ]
    ]);

} catch (PDOException $e) {
    error_log("Kurir Orders error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Kurir Orders error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
