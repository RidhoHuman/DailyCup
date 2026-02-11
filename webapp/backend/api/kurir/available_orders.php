<?php
/**
 * Kurir Available Orders API
 * 
 * GET /api/kurir/available_orders.php - Get orders that are ready to be claimed by kurir
 * Shows orders with:
 * - payment_status = 'paid'
 * - delivery_method = 'delivery'
 * - kurir_id IS NULL (not yet assigned)
 * - status IN ('confirmed', 'processing', 'ready')
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
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

try {
    // Check if kurir is active and available
    $kurirCheck = $pdo->prepare("SELECT id, name, status, is_active FROM kurir WHERE id = ?");
    $kurirCheck->execute([$kurirId]);
    $kurir = $kurirCheck->fetch(PDO::FETCH_ASSOC);

    if (!$kurir || !$kurir['is_active']) {
        echo json_encode([
            'success' => false, 
            'error' => 'Akun kurir tidak aktif. Hubungi admin.',
            'data' => []
        ]);
        exit;
    }

    // Count kurir's current active orders
    $activeOrdersStmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders 
        WHERE kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering')
    ");
    $activeOrdersStmt->execute([$kurirId]);
    $activeOrderCount = (int)$activeOrdersStmt->fetchColumn();

    // Get total available orders count (exclude COD - requires admin assignment)
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders o
        WHERE o.kurir_id IS NULL 
          AND o.payment_status = 'paid'
          AND o.delivery_method = 'delivery'
          AND o.payment_method != 'cod'
          AND o.status IN ('confirmed', 'processing', 'ready')
    ");
    $countStmt->execute();
    $totalOrders = $countStmt->fetchColumn();

    // Get available orders (exclude COD - requires admin assignment)
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.payment_method,
            o.payment_status,
            o.delivery_method,
            o.delivery_address,
            o.delivery_lat,
            o.delivery_lng,
            o.customer_notes,
            o.total_amount,
            o.final_amount,
            o.created_at,
            COALESCE(o.customer_name, u.name) as customer_name,
            COALESCE(o.customer_phone, u.phone) as customer_phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.kurir_id IS NULL 
          AND o.payment_status = 'paid'
          AND o.delivery_method = 'delivery'
          AND o.payment_method != 'cod'
          AND o.status IN ('confirmed', 'processing', 'ready')
        ORDER BY o.created_at ASC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format orders
    $formattedOrders = array_map(function($order) {
        return [
            'id' => (int)$order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'payment_method' => $order['payment_method'],
            'payment_status' => $order['payment_status'],
            'delivery_address' => $order['delivery_address'],
            'delivery_lat' => $order['delivery_lat'] ? (float)$order['delivery_lat'] : null,
            'delivery_lng' => $order['delivery_lng'] ? (float)$order['delivery_lng'] : null,
            'customer_notes' => $order['customer_notes'],
            'total_amount' => (float)$order['final_amount'] ?: (float)$order['total_amount'],
            'created_at' => $order['created_at'],
            'customer' => [
                'name' => $order['customer_name'],
                'phone' => $order['customer_phone']
            ]
        ];
    }, $orders);

    echo json_encode([
        'success' => true,
        'data' => $formattedOrders,
        'kurir_status' => $kurir['status'],
        'active_orders_count' => $activeOrderCount,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$totalOrders,
            'total_pages' => ceil($totalOrders / $limit)
        ]
    ]);

} catch (Exception $e) {
    error_log("Available orders error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
