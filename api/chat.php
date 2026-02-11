<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check customer login
if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Get action from query string, POST, or JSON body
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// If no action yet, try to get from JSON body
if (empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

try {
    switch ($action) {
        case 'send':
            // Send message
            $input = json_decode(file_get_contents('php://input'), true);
            $message = trim($input['message'] ?? '');
            
            if (empty($message)) {
                throw new Exception('Message cannot be empty');
            }
            
            $stmt = $db->prepare("INSERT INTO chat_messages (user_id, message, sender_type) VALUES (?, ?, 'customer')");
            $stmt->execute([$userId, $message]);
            
            // Create notification for admin - get admin user_id first
            $adminStmt = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
            $adminId = $adminStmt->fetchColumn();
            
            if ($adminId) {
                $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                     VALUES (?, 'chat', 'Pesan Chat Baru', ?)");
                $stmt->execute([$adminId, 'Customer: ' . substr($message, 0, 50)]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Message sent']);
            break;
            
        case 'get':
            // Get messages
            $lastId = intval($_GET['last_id'] ?? 0);
            
            $stmt = $db->prepare("SELECT id, message, sender_type, created_at 
                                 FROM chat_messages 
                                 WHERE user_id = ? AND id > ?
                                 ORDER BY created_at ASC
                                 LIMIT 50");
            $stmt->execute([$userId, $lastId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        case 'mark_read':
            // Mark messages as read
            $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 
                                 WHERE user_id = ? AND sender_type = 'admin' AND is_read = 0");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'unread_count':
            // Get unread count
            try {
                $stmt = $db->prepare("SELECT COUNT(*) FROM chat_messages 
                                     WHERE user_id = ? AND sender_type = 'admin' AND is_read = 0");
                $stmt->execute([$userId]);
                $count = $stmt->fetchColumn();
                
                echo json_encode(['count' => intval($count)]);
            } catch (PDOException $e) {
                // Table might not exist yet - return 0
                echo json_encode(['count' => 0]);
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
