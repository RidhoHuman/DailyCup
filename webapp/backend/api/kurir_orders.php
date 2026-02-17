<?php
/**
 * Kurir Orders API
 * Get orders assigned to courier
 */

require_once __DIR__ . '/../cors.php';
// CORS handled centrally (cors.php / .htaccess)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

// Authenticate kurir
$headers = getallheaders();
$token = null;

foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $token = str_replace('Bearer ', '', $value);
        break;
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

$decoded = JWT::verify($token);
if (!$decoded || !isset($decoded['user_id']) || ($decoded['role'] ?? '') !== 'kurir') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$kurirId = $decoded['user_id'];

try {
    $status = $_GET['status'] ?? 'active';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    // Build status condition
    if ($status === 'active') {
        $statusCondition = "o.status IN ('confirmed', 'processing', 'ready', 'delivering')";
    } elseif ($status === 'completed') {
        $statusCondition = "o.status = 'completed'";
    } else {
        $statusCondition = "o.status = ?";
    }
    
    // Get orders
    $query = "
        SELECT 
            o.id,
            o.order_number as orderNumber,
            o.status,
            o.payment_method as paymentMethod,
            o.payment_status as paymentStatus,
            o.delivery_address as deliveryAddress,
            o.customer_notes as customerNotes,
            o.final_amount as finalAmount,
            o.created_at as createdAt,
            o.assigned_at as assignedAt,
            u.name as customerName,
            u.phone as customerPhone,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as itemCount
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.kurir_id = ? AND $statusCondition
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params = [$kurirId];
    if ($status !== 'active' && $status !== 'completed') {
        $params[] = $status;
    }
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format customer info
    foreach ($orders as &$order) {
        $order['customer'] = [
            'name' => $order['customerName'],
            'phone' => $order['customerPhone']
        ];
        unset($order['customerName'], $order['customerPhone']);
        
        // Convert numeric strings to numbers
        $order['id'] = (int)$order['id'];
        $order['finalAmount'] = (float)$order['finalAmount'];
        $order['itemCount'] = (int)$order['itemCount'];
    }
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM orders 
        WHERE kurir_id = ? AND $statusCondition
    ";
    $countParams = [$kurirId];
    if ($status !== 'active' && $status !== 'completed') {
        $countParams[] = $status;
    }
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $orders,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Kurir Orders Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
