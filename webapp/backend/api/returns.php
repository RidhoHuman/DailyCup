<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create returns table if not exists
$db->query("CREATE TABLE IF NOT EXISTS product_returns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    return_number VARCHAR(50) UNIQUE NOT NULL,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    items JSON NOT NULL,
    reason VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    images JSON NULL,
    refund_method VARCHAR(50) DEFAULT 'original_payment',
    refund_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'refunded', 'completed') DEFAULT 'pending',
    admin_notes TEXT NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    processed_by INT NULL,
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$method = $_SERVER['REQUEST_METHOD'];
$user = validateToken();

// GET - Get returns
if ($method === 'GET') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    // Get single return
    if (isset($_GET['id'])) {
        $returnId = intval($_GET['id']);
        
        $stmt = $db->prepare("
            SELECT pr.*, 
                   u.name as customer_name,
                   u.email as customer_email,
                   o.order_number,
                   o.total as order_total,
                   a.name as processed_by_name
            FROM product_returns pr
            JOIN users u ON pr.user_id = u.id
            JOIN orders o ON pr.order_id = o.id
            LEFT JOIN users a ON pr.processed_by = a.id
            WHERE pr.id = ?
        ");
        $stmt->bind_param("i", $returnId);
        $stmt->execute();
        $return = $stmt->get_result()->fetch_assoc();
        
        if (!$return) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Return not found']);
            exit;
        }
        
        // Check permission
        if ($user['role'] !== 'admin' && $return['user_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        // Decode JSON fields
        if ($return['items']) {
            $return['items'] = json_decode($return['items'], true);
        }
        if ($return['images']) {
            $return['images'] = json_decode($return['images'], true);
        }
        
        echo json_encode(['success' => true, 'return' => $return]);
        exit;
    }
    
    // Get all returns with filters
    $status = $_GET['status'] ?? 'all';
    $orderId = $_GET['order_id'] ?? null;
    $userId = $user['role'] === 'admin' ? ($_GET['user_id'] ?? null) : $user['id'];
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    
    $where = [];
    $params = [];
    $types = '';
    
    if ($userId && $user['role'] !== 'admin') {
        $where[] = "pr.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    } elseif ($userId && $user['role'] === 'admin') {
        $where[] = "pr.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    if ($status !== 'all') {
        $where[] = "pr.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($orderId) {
        $where[] = "pr.order_id = ?";
        $params[] = intval($orderId);
        $types .= 'i';
    }
    
    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM product_returns pr $whereClause";
    if (count($params) > 0) {
        $stmt = $db->prepare($countQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total = $db->query($countQuery)->fetch_assoc()['total'];
    }
    
    // Get returns
    $query = "
        SELECT pr.*, 
               u.name as customer_name,
               u.email as customer_email,
               o.order_number,
               o.total as order_total,
               a.name as processed_by_name
        FROM product_returns pr
        JOIN users u ON pr.user_id = u.id
        JOIN orders o ON pr.order_id = o.id
        LEFT JOIN users a ON pr.processed_by = a.id
        $whereClause
        ORDER BY pr.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $db->prepare($query);
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $returns = [];
    while ($row = $result->fetch_assoc()) {
        // Decode JSON fields
        if ($row['items']) {
            $row['items'] = json_decode($row['items'], true);
        }
        if ($row['images']) {
            $row['images'] = json_decode($row['images'], true);
        }
        $returns[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'returns' => $returns,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    exit;
}

// POST - Create new return request
if ($method === 'POST') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $orderId = intval($data['order_id'] ?? 0);
    $items = $data['items'] ?? [];
    $reason = $data['reason'] ?? '';
    $description = trim($data['description'] ?? '');
    $images = $data['images'] ?? null;
    $refundMethod = $data['refund_method'] ?? 'original_payment';
    
    // Validate
    if (!$orderId || empty($items) || empty($reason) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify order ownership
    $stmt = $db->prepare("SELECT id, total, status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $user['id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    // Check if order can be returned (e.g., completed status)
    if (!in_array($order['status'], ['completed', 'delivered'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only completed/delivered orders can be returned']);
        exit;
    }
    
    // Check for existing return
    $stmt = $db->prepare("
        SELECT id FROM product_returns 
        WHERE order_id = ? AND status NOT IN ('rejected', 'completed')
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A return request already exists for this order']);
        exit;
    }
    
    // Calculate refund amount from selected items
    $refundAmount = 0;
    foreach ($items as $item) {
        $refundAmount += floatval($item['price'] ?? 0) * intval($item['quantity'] ?? 1);
    }
    
    // Generate return number
    $returnNumber = 'RET-' . strtoupper(uniqid());
    
    $itemsJson = json_encode($items);
    $imagesJson = $images ? json_encode($images) : null;
    
    // Create return request
    $stmt = $db->prepare("
        INSERT INTO product_returns 
        (return_number, order_id, user_id, items, reason, description, images, refund_method, refund_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("siiissssd", $returnNumber, $orderId, $user['id'], $itemsJson, $reason, $description, $imagesJson, $refundMethod, $refundAmount);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Return request submitted successfully',
            'return_id' => $stmt->insert_id,
            'return_number' => $returnNumber
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create return request']);
    }
    exit;
}

// PUT - Update return (admin only)
if ($method === 'PUT') {
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $returnId = intval($data['id'] ?? 0);
    
    if (!$returnId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Return ID is required']);
        exit;
    }
    
    // Get return details
    $stmt = $db->prepare("SELECT * FROM product_returns WHERE id = ?");
    $stmt->bind_param("i", $returnId);
    $stmt->execute();
    $return = $stmt->get_result()->fetch_assoc();
    
    if (!$return) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Return not found']);
        exit;
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];
        $types .= 's';
        
        // Set processed info when status changes to final states
        if (in_array($data['status'], ['approved', 'rejected', 'refunded', 'completed'])) {
            $updates[] = "processed_at = NOW()";
            $updates[] = "processed_by = ?";
            $params[] = $user['id'];
            $types .= 'i';
        }
        
        // Update order status if return is approved
        if ($data['status'] === 'approved') {
            $db->query("UPDATE orders SET status = 'returned' WHERE id = {$return['order_id']}");
        }
    }
    
    if (isset($data['admin_notes'])) {
        $updates[] = "admin_notes = ?";
        $params[] = $data['admin_notes'];
        $types .= 's';
    }
    
    if (isset($data['rejection_reason'])) {
        $updates[] = "rejection_reason = ?";
        $params[] = $data['rejection_reason'];
        $types .= 's';
    }
    
    if (isset($data['refund_amount'])) {
        $updates[] = "refund_amount = ?";
        $params[] = floatval($data['refund_amount']);
        $types .= 'd';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }
    
    $params[] = $returnId;
    $types .= 'i';
    
    $query = "UPDATE product_returns SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Return updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update return']);
    }
    exit;
}

// DELETE - Cancel return (customer only, pending returns)
if ($method === 'DELETE') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $returnId = intval($_GET['id'] ?? 0);
    
    if (!$returnId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Return ID is required']);
        exit;
    }
    
    // Verify ownership and status
    $stmt = $db->prepare("SELECT * FROM product_returns WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $returnId, $user['id']);
    $stmt->execute();
    $return = $stmt->get_result()->fetch_assoc();
    
    if (!$return) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Return not found or cannot be cancelled']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM product_returns WHERE id = ?");
    $stmt->bind_param("i", $returnId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Return cancelled successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to cancel return']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
