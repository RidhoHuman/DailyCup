<?php
/**
 * Kurir Order Detail API
 * 
 * GET /api/kurir/order_detail.php?order_id=ORD-xxx
 * 
 * Returns full order details including items, customer info, timeline
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

// Auth check
$authUser = JWT::getUser();
if (!$authUser || ($authUser['role'] ?? '') !== 'kurir') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Kurir authentication required']);
    exit;
}

$kurirId = $authUser['kurir_id'] ?? $authUser['user_id'];
$orderId = $_GET['order_id'] ?? $_GET['id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

try {
    // Get order
    $orderWhere = is_numeric($orderId) ? "o.id = ?" : "o.order_number = ?";
    $stmt = $pdo->prepare("
        SELECT 
            o.id, o.order_number, o.status, o.payment_method, o.payment_status,
            o.delivery_method, o.delivery_address, o.customer_notes,
            o.total_amount, o.final_amount, o.discount_amount,
            o.created_at, o.updated_at, o.assigned_at, o.pickup_time,
            o.delivery_time, o.completed_at, o.kurir_id, o.user_id,
            o.delivery_distance, o.cod_amount_limit,
            o.kurir_departure_photo, o.kurir_arrival_photo,
            o.kurir_arrived_at, o.actual_delivery_time,
            u.name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            u.address as customer_default_address
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE $orderWhere
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
        exit;
    }

    // Verify this order belongs to this kurir
    if ((int)$order['kurir_id'] !== (int)$kurirId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Pesanan ini bukan milik Anda']);
        exit;
    }

    // Get order items
    $itemStmt = $pdo->prepare("
        SELECT 
            oi.product_id, oi.product_name, oi.unit_price, oi.quantity,
            oi.size, oi.temperature, oi.addons, oi.subtotal, oi.notes,
            COALESCE(p.image, '') as image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->execute([$order['id']]);
    $itemRows = $itemStmt->fetchAll();

    $items = [];
    foreach ($itemRows as $row) {
        $variant = [];
        if (!empty($row['size'])) $variant['size'] = $row['size'];
        if (!empty($row['temperature'])) $variant['temperature'] = $row['temperature'];
        if (!empty($row['addons'])) {
            $addons = json_decode($row['addons'], true);
            if ($addons) $variant['addons'] = is_array($addons) ? implode(', ', $addons) : $row['addons'];
        }

        $items[] = [
            'id' => (int)$row['product_id'],
            'name' => $row['product_name'],
            'price' => (float)$row['unit_price'],
            'quantity' => (int)$row['quantity'],
            'subtotal' => (float)$row['subtotal'],
            'variant' => $variant,
            'notes' => $row['notes'],
            'image' => !empty($row['image']) ? $row['image'] : null
        ];
    }

    // Build timeline
    $timeline = [];
    $timeline[] = ['status' => 'Order Placed', 'time' => $order['created_at'], 'completed' => true];

    if ($order['assigned_at']) {
        $timeline[] = ['status' => 'Kurir Assigned', 'time' => $order['assigned_at'], 'completed' => true];
    }

    if (in_array($order['status'], ['processing', 'ready', 'delivering', 'completed'])) {
        $timeline[] = ['status' => 'Processing', 'time' => $order['updated_at'], 'completed' => true];
    }
    if (in_array($order['status'], ['ready', 'delivering', 'completed'])) {
        $timeline[] = ['status' => 'Ready for Pickup', 'time' => null, 'completed' => true];
    }
    if (in_array($order['status'], ['delivering', 'completed'])) {
        $timeline[] = ['status' => 'Delivering', 'time' => $order['pickup_time'], 'completed' => true];
    }
    if ($order['status'] === 'completed') {
        $timeline[] = ['status' => 'Completed', 'time' => $order['completed_at'], 'completed' => true];
    }
    if ($order['status'] === 'cancelled') {
        $timeline[] = ['status' => 'Cancelled', 'time' => $order['updated_at'], 'completed' => true];
    }

    // Determine next action for kurir
    $nextAction = null;
    $transitions = [
        'confirmed'  => ['next' => 'processing', 'label' => 'Mulai Proses'],
        'processing' => ['next' => 'ready',      'label' => 'Tandai Siap'],
        'ready'      => ['next' => 'delivering',  'label' => 'Mulai Antar'],
        'delivering' => ['next' => 'completed',   'label' => 'Selesai Antar']
    ];
    if (isset($transitions[$order['status']])) {
        $nextAction = $transitions[$order['status']];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$order['id'],
            'orderNumber' => $order['order_number'],
            'status' => $order['status'],
            'paymentMethod' => $order['payment_method'],
            'paymentStatus' => $order['payment_status'],
            'deliveryMethod' => $order['delivery_method'],
            'deliveryAddress' => $order['delivery_address'],
            'deliveryDistance' => $order['delivery_distance'] ? (float)$order['delivery_distance'] : null,
            'customerNotes' => $order['customer_notes'],
            'totalAmount' => (float)$order['total_amount'],
            'finalAmount' => (float)$order['final_amount'],
            'customer' => [
                'name' => $order['customer_name'],
                'phone' => $order['customer_phone'],
                'email' => $order['customer_email']
            ],
            'items' => $items,
            'timeline' => $timeline,
            'nextAction' => $nextAction,
            'createdAt' => $order['created_at'],
            'assignedAt' => $order['assigned_at'],
            'pickupTime' => $order['pickup_time'],
            'completedAt' => $order['completed_at'],
            'isCOD' => $order['payment_method'] === 'cod',
            'codAmountLimit' => $order['cod_amount_limit'] ? (float)$order['cod_amount_limit'] : null,
            'kurirDeparturePhoto' => $order['kurir_departure_photo'] ?? null,
            'kurirArrivalPhoto' => $order['kurir_arrival_photo'] ?? null,
            'kurirArrivedAt' => $order['kurir_arrived_at'] ?? null,
            'actualDeliveryTime' => $order['actual_delivery_time'] ? (int)$order['actual_delivery_time'] : null
        ]
    ]);

} catch (PDOException $e) {
    error_log("Kurir Order Detail error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("Kurir Order Detail error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
