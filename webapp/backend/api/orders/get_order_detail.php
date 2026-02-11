<?php
/**
 * Get Order Detail API
 * 
 * Fetches complete order information including items, status, and timeline
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate order ID
$orderId = $_GET['order_id'] ?? $_GET['id'] ?? null;
if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

// Verify authentication
$authUser = JWT::getUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userId = $authUser['user_id'] ?? null;
$userRole = $authUser['role'] ?? 'customer';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get order with items
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.order_number,
            COALESCE(o.customer_name, u.name) as customer_name,
            COALESCE(o.customer_phone, u.phone) as customer_phone,
            u.email as customer_email,
            o.delivery_address as customer_address,
            o.delivery_lat,
            o.delivery_lng,
            o.total_amount as subtotal,
            o.discount_amount as discount,
            COALESCE(o.discount_from_points, 0) as discount_points,
            o.total_amount,
            o.final_amount,
            o.status,
            o.payment_method,
            o.payment_status,
            o.delivery_method,
            o.customer_notes as notes,
            o.created_at,
            o.updated_at,
            o.paid_at,
            o.completed_at,
            o.user_id,
            o.kurir_departure_photo,
            o.kurir_arrival_photo,
            o.kurir_arrived_at,
            o.actual_delivery_time,
            o.kurir_id,
            k.name as kurir_name,
            k.phone as kurir_phone,
            k.vehicle_type as kurir_vehicle,
            k.photo as kurir_photo
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN kurir k ON o.kurir_id = k.id
        WHERE o.order_number = ?
    ");
    
    $stmt->bind_param("s", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Authorization check: customers can only see their own orders
    if ($userRole === 'customer' && $order['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Fetch order items separately (safer than GROUP_CONCAT)
    $itemStmt = $conn->prepare("
        SELECT 
            oi.product_id,
            oi.product_name,
            oi.unit_price,
            oi.quantity,
            oi.size,
            oi.temperature,
            oi.addons,
            COALESCE(p.image, '') as image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $itemStmt->bind_param("i", $order['id']);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();
    
    $items = [];
    while ($row = $itemResult->fetch_assoc()) {
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
            'variant' => $variant,
            'image' => !empty($row['image']) ? $row['image'] : null
        ];
    }
    
    // Build timeline
    $timeline = [];
    
    // Order placed
    $timeline[] = [
        'status' => 'Order Placed',
        'date' => date('d M, H:i', strtotime($order['created_at'])),
        'completed' => true,
        'icon' => 'bi-cart-check'
    ];
    
    // Payment status
    if ($order['payment_status'] === 'paid' && $order['paid_at']) {
        $timeline[] = [
            'status' => 'Payment Confirmed',
            'date' => date('d M, H:i', strtotime($order['paid_at'])),
            'completed' => true,
            'icon' => 'bi-wallet2'
        ];
    } elseif ($order['payment_method'] === 'cod') {
        $timeline[] = [
            'status' => 'Payment on Delivery',
            'date' => 'COD',
            'completed' => false,
            'icon' => 'bi-cash-coin'
        ];
    }
    
    // Order processing
    if (in_array($order['status'], ['processing', 'ready', 'packed', 'delivering', 'out_for_delivery', 'delivered', 'completed'])) {
        $timeline[] = [
            'status' => 'Preparing Order',
            'date' => $order['updated_at'] ? date('d M, H:i', strtotime($order['updated_at'])) : 'In progress',
            'completed' => true,
            'icon' => 'bi-cup-hot'
        ];
    }
    
    // Delivery
    if (in_array($order['status'], ['delivering', 'out_for_delivery', 'delivered', 'completed'])) {
        $timeline[] = [
            'status' => 'Out for Delivery',
            'date' => $order['kurir_name'] ? 'Kurir: ' . $order['kurir_name'] : 'Estimated soon',
            'completed' => in_array($order['status'], ['delivered', 'completed']),
            'icon' => 'bi-truck'
        ];
    }
    
    // Delivered
    if ($order['status'] === 'delivered' || $order['status'] === 'completed') {
        $timeline[] = [
            'status' => 'Delivered',
            'date' => $order['completed_at'] ? date('d M, H:i', strtotime($order['completed_at'])) : date('d M, H:i'),
            'completed' => true,
            'icon' => 'bi-house-door'
        ];
    } else {
        $timeline[] = [
            'status' => 'Delivered',
            'date' => '-',
            'completed' => false,
            'icon' => 'bi-house-door'
        ];
    }
    
    // Cancelled
    if ($order['status'] === 'cancelled') {
        $timeline[] = [
            'status' => 'Order Cancelled',
            'date' => $order['updated_at'] ? date('d M, H:i', strtotime($order['updated_at'])) : '',
            'completed' => true,
            'icon' => 'bi-x-circle'
        ];
    }
    
    // Format response
    $response = [
        'success' => true,
        'data' => [
            'id' => $order['order_number'],
            'date' => date('d M Y, H:i', strtotime($order['created_at'])),
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'items' => $items,
            'subtotal' => (float)$order['subtotal'],
            'deliveryFee' => 0,
            'discount' => (float)($order['discount'] ?? 0) + (float)($order['discount_points'] ?? 0),
            'total' => (float)$order['final_amount'],
            'shippingAddress' => [
                'name' => $order['customer_name'] ?? 'N/A',
                'phone' => $order['customer_phone'] ?? 'N/A',
                'address' => $order['customer_address'] ?? 'N/A',
                'lat' => $order['delivery_lat'] ? (float)$order['delivery_lat'] : null,
                'lng' => $order['delivery_lng'] ? (float)$order['delivery_lng'] : null,
                'geocode_status' => $order['geocode_status'] ?? null,
                'geocoded_at' => $order['geocoded_at'] ?? null,
                'geocode_error' => $order['geocode_error'] ?? null
            ],
            'paymentMethod' => $order['payment_method'] ?? 'N/A',
            'deliveryMethod' => $order['delivery_method'],
            'notes' => $order['notes'],
            'timeline' => $timeline,
            'kurirDeparturePhoto' => $order['kurir_departure_photo'] ?? null,
            'kurirArrivalPhoto' => $order['kurir_arrival_photo'] ?? null,
            'kurirArrivedAt' => $order['kurir_arrived_at'] ?? null,
            'actualDeliveryTime' => $order['actual_delivery_time'] ? (int)$order['actual_delivery_time'] : null,
            'kurir' => $order['kurir_id'] ? [
                'id' => (int)$order['kurir_id'],
                'name' => $order['kurir_name'] ?? 'Kurir',
                'phone' => $order['kurir_phone'] ?? '-',
                'vehicle_type' => $order['kurir_vehicle'] ?? null,
                'photo' => $order['kurir_photo'] ?? null
            ] : null,
            'geocode_status' => $order['geocode_status'] ?? null,
            'geocoded_at' => $order['geocoded_at'] ?? null,
            'geocode_error' => $order['geocode_error'] ?? null
        ]
    ];
    
    // Backwards compatibility: some clients expect top-level 'order' key
    $response['order'] = $response['data'];

    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Get Order Detail Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch order details',
        'error' => $e->getMessage(), // Include actual error for debugging
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
