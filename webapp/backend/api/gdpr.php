<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create GDPR requests table if not exists
$db->query("CREATE TABLE IF NOT EXISTS gdpr_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    request_type ENUM('data_export', 'account_deletion') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    reason TEXT NULL,
    admin_notes TEXT NULL,
    export_file_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    processed_by INT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (request_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$method = $_SERVER['REQUEST_METHOD'];
$user = validateToken();

// GET - Get GDPR requests
if ($method === 'GET') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    // Get single request
    if (isset($_GET['id'])) {
        $requestId = intval($_GET['id']);
        
        $stmt = $db->prepare("
            SELECT gr.*, 
                   u.name as user_name,
                   u.email as user_email,
                   a.name as processed_by_name
            FROM gdpr_requests gr
            JOIN users u ON gr.user_id = u.id
            LEFT JOIN users a ON gr.processed_by = a.id
            WHERE gr.id = ?
        ");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        // Check permission
        if ($user['role'] !== 'admin' && $request['user_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        echo json_encode(['success' => true, 'request' => $request]);
        exit;
    }
    
    // Get all requests with filters
    $status = $_GET['status'] ?? 'all';
    $type = $_GET['type'] ?? 'all';
    $userId = $user['role'] === 'admin' ? ($_GET['user_id'] ?? null) : $user['id'];
    
    $where = [];
    $params = [];
    $types = '';
    
    if ($userId && $user['role'] !== 'admin') {
        $where[] = "gr.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    } elseif ($userId && $user['role'] === 'admin') {
        $where[] = "gr.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    if ($status !== 'all') {
        $where[] = "gr.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($type !== 'all') {
        $where[] = "gr.request_type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "
        SELECT gr.*, 
               u.name as user_name,
               u.email as user_email,
               a.name as processed_by_name
        FROM gdpr_requests gr
        JOIN users u ON gr.user_id = u.id
        LEFT JOIN users a ON gr.processed_by = a.id
        $whereClause
        ORDER BY gr.created_at DESC
    ";
    
    if (count($params) > 0) {
        $stmt = $db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $db->query($query);
    }
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    
    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

// POST - Create new GDPR request or export data
if ($method === 'POST') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Handle data export action
    if (isset($_GET['action']) && $_GET['action'] === 'export' && isset($_GET['request_id'])) {
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        $requestId = intval($_GET['request_id']);
        
        // Get request details
        $stmt = $db->prepare("SELECT * FROM gdpr_requests WHERE id = ? AND request_type = 'data_export'");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        $userId = $request['user_id'];
        
        // Collect user data
        $userData = [];
        
        // User info
        $stmt = $db->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $userData['user'] = $stmt->get_result()->fetch_assoc();
        
        // Orders
        $stmt = $db->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(CONCAT(oi.product_name, ' x', oi.quantity) SEPARATOR ', ') as items
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData['orders'] = [];
        while ($row = $result->fetch_assoc()) {
            $userData['orders'][] = $row;
        }
        
        // Reviews
        $stmt = $db->prepare("SELECT * FROM reviews WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData['reviews'] = [];
        while ($row = $result->fetch_assoc()) {
            $userData['reviews'][] = $row;
        }
        
        // Addresses (if table exists)
        $tables = $db->query("SHOW TABLES LIKE 'addresses'");
        if ($tables->num_rows > 0) {
            $stmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData['addresses'] = [];
            while ($row = $result->fetch_assoc()) {
                $userData['addresses'][] = $row;
            }
        }
        
        // Loyalty points (if table exists)
        $tables = $db->query("SHOW TABLES LIKE 'loyalty_points'");
        if ($tables->num_rows > 0) {
            $stmt = $db->prepare("SELECT * FROM loyalty_points WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData['loyalty_points'] = [];
            while ($row = $result->fetch_assoc()) {
                $userData['loyalty_points'][] = $row;
            }
        }
        
        // Tickets
        $tables = $db->query("SHOW TABLES LIKE 'tickets'");
        if ($tables->num_rows > 0) {
            $stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData['tickets'] = [];
            while ($row = $result->fetch_assoc()) {
                $userData['tickets'][] = $row;
            }
        }
        
        // Create export directory if not exists
        $exportDir = __DIR__ . '/../../exports/gdpr';
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        // Generate filename
        $fileName = 'user_data_' . $userId . '_' . time() . '.json';
        $filePath = $exportDir . '/' . $fileName;
        
        // Save data to file
        file_put_contents($filePath, json_encode($userData, JSON_PRETTY_PRINT));
        
        // Update request
        $relativeFilePath = 'exports/gdpr/' . $fileName;
        $stmt = $db->prepare("
            UPDATE gdpr_requests 
            SET status = 'completed', 
                export_file_path = ?, 
                completed_at = NOW(),
                processed_by = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sii", $relativeFilePath, $user['id'], $requestId);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Data exported successfully',
            'file_path' => $relativeFilePath
        ]);
        exit;
    }
    
    // Create new GDPR request
    $requestType = $data['request_type'] ?? '';
    $reason = $data['reason'] ?? null;
    
    if (!in_array($requestType, ['data_export', 'account_deletion'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request type']);
        exit;
    }
    
    // Check for existing pending request
    $stmt = $db->prepare("
        SELECT id FROM gdpr_requests 
        WHERE user_id = ? AND request_type = ? AND status IN ('pending', 'processing')
    ");
    $stmt->bind_param("is", $user['id'], $requestType);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You already have a pending request of this type']);
        exit;
    }
    
    // Create request
    $stmt = $db->prepare("
        INSERT INTO gdpr_requests (user_id, request_type, reason, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iss", $user['id'], $requestType, $reason);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'GDPR request created successfully',
            'request_id' => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create request']);
    }
    exit;
}

// PUT - Update GDPR request (admin only)
if ($method === 'PUT') {
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = intval($data['id'] ?? 0);
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        exit;
    }
    
    // Handle account deletion
    if (isset($data['action']) && $data['action'] === 'delete_account') {
        // Get request
        $stmt = $db->prepare("SELECT * FROM gdpr_requests WHERE id = ? AND request_type = 'account_deletion'");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if (!$request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        $userId = $request['user_id'];
        
        // Delete user (cascade will delete related data)
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            // Update request
            $stmt = $db->prepare("
                UPDATE gdpr_requests 
                SET status = 'completed', 
                    completed_at = NOW(),
                    processed_by = ?,
                    admin_notes = 'Account deleted successfully'
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $user['id'], $requestId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete account']);
        }
        exit;
    }
    
    // Update request status
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];
        $types .= 's';
        
        if ($data['status'] === 'completed' || $data['status'] === 'rejected') {
            $updates[] = "completed_at = NOW()";
            $updates[] = "processed_by = ?";
            $params[] = $user['id'];
            $types .= 'i';
        }
    }
    
    if (isset($data['admin_notes'])) {
        $updates[] = "admin_notes = ?";
        $params[] = $data['admin_notes'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }
    
    $params[] = $requestId;
    $types .= 'i';
    
    $query = "UPDATE gdpr_requests SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update request']);
    }
    exit;
}

// DELETE - Cancel GDPR request (customer only, pending requests)
if ($method === 'DELETE') {
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    $requestId = intval($_GET['id'] ?? 0);
    
    if (!$requestId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Request ID is required']);
        exit;
    }
    
    // Verify ownership and status
    $stmt = $db->prepare("SELECT * FROM gdpr_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $requestId, $user['id']);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Request not found or cannot be cancelled']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM gdpr_requests WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request cancelled successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to cancel request']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
