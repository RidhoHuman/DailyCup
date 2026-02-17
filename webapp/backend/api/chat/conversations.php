<?php
require_once __DIR__ . '/../cors.php';
/**
 * Chat Conversations API
 * Manages chat conversations between customers and support
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../input_sanitizer.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Use $pdo from database.php
$db = $pdo;

// Verify authentication
$userData = JWT::getUser();
if (!$userData) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Create tables if not exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS chat_conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_name VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('open', 'closed', 'pending') DEFAULT 'open',
        unread_count INT DEFAULT 0,
        last_message TEXT,
        last_message_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_updated_at (updated_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_type ENUM('customer', 'admin') NOT NULL,
        sender_name VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conversation_id (conversation_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Tables might already exist
}

$userData = JWT::getUser();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            getConversations($db, $userData);
            break;

        case 'POST':
            createConversation($db, $userData);
            break;

        case 'PUT':
            updateConversation($db, $userData);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Get conversations list
 */
function getConversations($db, $userData) {
    $isAdmin = $userData['role'] === 'admin';
    $userId = $userData['user_id'];

    if ($isAdmin) {
        // Admin sees all conversations
        $query = "SELECT 
                    c.*,
                    (SELECT COUNT(*) FROM chat_messages 
                     WHERE conversation_id = c.id AND is_read = 0 AND sender_type = 'customer') as unread_count
                  FROM chat_conversations c 
                  ORDER BY c.updated_at DESC";
        $stmt = $db->query($query);
    } else {
        // Customer sees only their conversations
        $query = "SELECT 
                    c.*,
                    (SELECT COUNT(*) FROM chat_messages 
                     WHERE conversation_id = c.id AND is_read = 0 AND sender_type = 'admin') as unread_count
                  FROM chat_conversations c 
                  WHERE c.user_id = ? 
                  ORDER BY c.updated_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
    }

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'conversations' => $conversations
    ]);
}

/**
 * Create new conversation (customer only)
 */
function createConversation($db, $userData) {
    $input = json_decode(file_get_contents('php://input'), true);
    $subject = trim($input['subject'] ?? '');
    $firstMessage = trim($input['message'] ?? '');

    if (empty($subject) || empty($firstMessage)) {
        http_response_code(400);
        echo json_encode(['error' => 'Subject and message are required']);
        return;
    }

    try {
        $db->beginTransaction();

        // Create conversation
        $stmt = $db->prepare("INSERT INTO chat_conversations (user_id, user_name, subject, last_message, last_message_at) 
                             VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userData['user_id'],
            $userData['name'],
            $subject,
            $firstMessage
        ]);

        $conversationId = $db->lastInsertId();

        // Add first message
        $stmt = $db->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_name, message) 
                             VALUES (?, 'customer', ?, ?)");
        $stmt->execute([$conversationId, $userData['name'], $firstMessage]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'conversation_id' => $conversationId,
            'message' => 'Conversation created successfully'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Update conversation (admin only - change status)
 */
function updateConversation($db, $userData) {
    if ($userData['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = intval($input['conversation_id'] ?? 0);
    $status = $input['status'] ?? '';

    if (!$conversationId || !in_array($status, ['open', 'closed', 'pending'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid conversation ID or status']);
        return;
    }

    $stmt = $db->prepare("UPDATE chat_conversations SET status = ? WHERE id = ?");
    $stmt->execute([$status, $conversationId]);

    echo json_encode([
        'success' => true,
        'message' => 'Conversation status updated'
    ]);
}
