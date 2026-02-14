<?php
/**
 * Get Order Detail API
 * Returns complete order information including items, customer, kurir, and history
 */

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $orderId = $_GET['order_id'] ?? null;
    $orderNumber = $_GET['order_number'] ?? null;
    
    if (!$orderId && !$orderNumber) {
        throw new Exception('Order ID or Order Number is required');
    }
    
    // Get order details
    $query = "
        SELECT 
            o.*,
            u.name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            u.trust_score,
            u.total_successful_orders,
            u.is_verified_user,
            k.name as kurir_name,
            k.phone as kurir_phone,
            k.vehicle_type,
            k.vehicle_number,
            k.status as kurir_status,
            admin.name as admin_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN kurir k ON k.id = o.kurir_id
        LEFT JOIN users admin ON admin.id = o.admin_confirmed_by
        WHERE " . ($orderId ? "o.id = ?" : "o.order_number = ?") . "
    ";
    
    $stmt = $conn->prepare($query);
    if ($orderId) {
        $stmt->bind_param("i", $orderId);
    } else {
        $stmt->bind_param("s", $orderNumber);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT 
            oi.*,
            p.name as product_name_current,
            p.image as product_image
        FROM order_items oi
        LEFT JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Parse addons if JSON
        if ($row['addons']) {
            $row['addons_parsed'] = json_decode($row['addons'], true);
        }
        $items[] = $row;
    }
    $stmt->close();
    
    // Get order status history
    $stmt = $conn->prepare("
        SELECT 
            osl.*,
            CASE 
                WHEN osl.changed_by_type = 'admin' THEN u.name
                WHEN osl.changed_by_type = 'kurir' THEN k.name
                ELSE NULL
            END as changed_by_name
        FROM order_status_logs osl
        LEFT JOIN users u ON u.id = osl.changed_by_id AND osl.changed_by_type = 'admin'
        LEFT JOIN kurir k ON k.id = osl.changed_by_id AND osl.changed_by_type = 'kurir'
        WHERE osl.order_id = ?
        ORDER BY osl.created_at ASC
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
    
    // Get delivery history (kurir updates)
    $stmt = $conn->prepare("
        SELECT 
            dh.*,
            k.name as kurir_name
        FROM delivery_history dh
        JOIN kurir k ON k.id = dh.kurir_id
        WHERE dh.order_id = ?
        ORDER BY dh.created_at ASC
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $deliveryHistory = [];
    while ($row = $result->fetch_assoc()) {
        $deliveryHistory[] = $row;
    }
    $stmt->close();
    
    // Get current kurir location if assigned
    if ($order['kurir_id']) {
        $stmt = $conn->prepare("
            SELECT latitude, longitude, updated_at
            FROM kurir_location
            WHERE kurir_id = ?
            ORDER BY updated_at DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $order['kurir_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $kurirLocation = $result->fetch_assoc();
        $stmt->close();
    } else {
        $kurirLocation = null;
    }
    
    // Build response
    $response = [
        'success' => true,
        'order' => $order,
        'items' => $items,
        'history' => $history,
        'delivery_history' => $deliveryHistory,
        'kurir_location' => $kurirLocation
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
