<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Create tickets table if not exists
$db->query("CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    priority VARCHAR(20) DEFAULT 'normal',
    status VARCHAR(20) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    assigned_to INT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_priority (priority),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Create ticket_messages table if not exists
$db->query("CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_staff BOOLEAN DEFAULT FALSE,
    attachments JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket_id (ticket_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$method = $_SERVER['REQUEST_METHOD'];
$user = validateToken();

// GET - Get tickets or single ticket
if ($method === 'GET') {
    if (isset($_GET['id'])) {
        // Get single ticket with messages
        $ticketId = intval($_GET['id']);
        
        // Get ticket
        $stmt = $db->prepare("
            SELECT t.*, 
                   u.name as customer_name, 
                   u.email as customer_email,
                   a.name as assigned_name
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN users a ON t.assigned_to = a.id
            WHERE t.id = ?
        ");
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ticket = $result->fetch_assoc();
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit;
        }
        
        // Check permission
        if ($user['role'] !== 'admin' && $ticket['user_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        // Get messages
        $stmt = $db->prepare("
            SELECT tm.*, u.name as user_name, u.role
            FROM ticket_messages tm
            JOIN users u ON tm.user_id = u.id
            WHERE tm.ticket_id = ?
            ORDER BY tm.created_at ASC
        ");
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $messagesResult = $stmt->get_result();
        $messages = [];
        while ($msg = $messagesResult->fetch_assoc()) {
            if ($msg['attachments']) {
                $msg['attachments'] = json_decode($msg['attachments'], true);
            }
            $messages[] = $msg;
        }
        
        $ticket['messages'] = $messages;
        
        echo json_encode(['success' => true, 'ticket' => $ticket]);
        exit;
    }
    
    // Get all tickets (with filters)
    $status = $_GET['status'] ?? 'all';
    $category = $_GET['category'] ?? 'all';
    $priority = $_GET['priority'] ?? 'all';
    $userId = $user['role'] === 'admin' ? ($_GET['user_id'] ?? null) : $user['id'];
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    
    $where = [];
    $params = [];
    $types = '';
    
    if ($userId && $user['role'] !== 'admin') {
        $where[] = "t.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    } elseif ($userId && $user['role'] === 'admin') {
        $where[] = "t.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    if ($status !== 'all') {
        $where[] = "t.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($category !== 'all') {
        $where[] = "t.category = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    if ($priority !== 'all') {
        $where[] = "t.priority = ?";
        $params[] = $priority;
        $types .= 's';
    }
    
    if ($search) {
        $where[] = "(t.ticket_number LIKE ? OR t.subject LIKE ? OR u.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM tickets t JOIN users u ON t.user_id = u.id $whereClause";
    if (count($params) > 0) {
        $stmt = $db->prepare($countQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total = $db->query($countQuery)->fetch_assoc()['total'];
    }
    
    // Get tickets
    $query = "
        SELECT t.*, 
               u.name as customer_name, 
               u.email as customer_email,
               a.name as assigned_name,
               (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count,
               (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_message_at
        FROM tickets t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN users a ON t.assigned_to = a.id
        $whereClause
        ORDER BY 
            CASE t.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
            END,
            t.updated_at DESC
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
    
    $tickets = [];
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'tickets' => $tickets,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    exit;
}

// POST - Create new ticket or add message
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    
    // Add message to existing ticket
    if (isset($data['ticket_id']) && isset($data['message'])) {
        $ticketId = intval($data['ticket_id']);
        $message = trim($data['message']);
        $attachments = $data['attachments'] ?? null;
        
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Message is required']);
            exit;
        }
        
        // Verify ticket exists and user has permission
        $stmt = $db->prepare("SELECT user_id FROM tickets WHERE id = ?");
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ticket not found']);
            exit;
        }
        
        if ($user['role'] !== 'admin' && $ticket['user_id'] != $user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
        
        $isStaff = $user['role'] === 'admin' ? 1 : 0;
        $attachmentsJson = $attachments ? json_encode($attachments) : null;
        
        $stmt = $db->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, message, is_staff, attachments)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisis", $ticketId, $user['id'], $message, $isStaff, $attachmentsJson);
        
        if ($stmt->execute()) {
            // Update ticket status if customer replies to closed ticket
            if ($user['role'] !== 'admin') {
                $db->query("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = $ticketId AND status = 'closed'");
            } else {
                // Admin reply - set to in_progress if open
                $db->query("UPDATE tickets SET status = 'in_progress', updated_at = NOW() WHERE id = $ticketId AND status = 'open'");
            }
            
            echo json_encode(['success' => true, 'message' => 'Message added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add message']);
        }
        exit;
    }
    
    // Create new ticket
    $subject = trim($data['subject'] ?? '');
    $category = $data['category'] ?? 'general';
    $priority = $data['priority'] ?? 'normal';
    $message = trim($data['message'] ?? '');
    $orderId = isset($data['order_id']) ? intval($data['order_id']) : null;
    
    if (empty($subject) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
        exit;
    }
    
    // Generate ticket number
    $ticketNumber = 'TKT-' . strtoupper(uniqid());
    
    // Create ticket
    $stmt = $db->prepare("
        INSERT INTO tickets (ticket_number, user_id, order_id, subject, category, priority, status)
        VALUES (?, ?, ?, ?, ?, ?, 'open')
    ");
    $stmt->bind_param("siisss", $ticketNumber, $user['id'], $orderId, $subject, $category, $priority);
    
    if ($stmt->execute()) {
        $ticketId = $stmt->insert_id;
        
        // Add initial message
        $stmt = $db->prepare("
            INSERT INTO ticket_messages (ticket_id, user_id, message, is_staff)
            VALUES (?, ?, ?, 0)
        ");
        $stmt->bind_param("iis", $ticketId, $user['id'], $message);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Ticket created successfully',
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create ticket']);
    }
    exit;
}

// PUT - Update ticket (admin only)
if ($method === 'PUT') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $ticketId = intval($data['id'] ?? 0);
    
    if (!$ticketId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
        exit;
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($data['status'])) {
        $updates[] = "status = ?";
        $params[] = $data['status'];
        $types .= 's';
        
        // Set closed_at if closing ticket
        if ($data['status'] === 'closed') {
            $updates[] = "closed_at = NOW()";
        }
    }
    
    if (isset($data['priority'])) {
        $updates[] = "priority = ?";
        $params[] = $data['priority'];
        $types .= 's';
    }
    
    if (isset($data['category'])) {
        $updates[] = "category = ?";
        $params[] = $data['category'];
        $types .= 's';
    }
    
    if (isset($data['assigned_to'])) {
        $updates[] = "assigned_to = ?";
        $params[] = $data['assigned_to'] ? intval($data['assigned_to']) : null;
        $types .= 'i';
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No updates provided']);
        exit;
    }
    
    $params[] = $ticketId;
    $types .= 'i';
    
    $query = "UPDATE tickets SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticket updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update ticket']);
    }
    exit;
}

// DELETE - Delete ticket (admin only)
if ($method === 'DELETE') {
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $ticketId = intval($_GET['id'] ?? 0);
    
    if (!$ticketId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
        exit;
    }
    
    $stmt = $db->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticketId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ticket deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete ticket']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
