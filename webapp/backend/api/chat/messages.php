<?php
/**
 * Chat Messages API
 * Handles sending and retrieving chat messages
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            getMessages($db, $userData);
            break;

        case 'POST':
            sendMessage($db, $userData);
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
 * Get messages for a conversation
 */
function getMessages($db, $userData) {
    $conversationId = intval($_GET['conversation_id'] ?? 0);

    if (!$conversationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID required']);
        return;
    }

    // Verify access
    $isAdmin = $userData['role'] === 'admin';
    if (!$isAdmin) {
        // Check if conversation belongs to user
        $stmt = $db->prepare("SELECT id FROM chat_conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversationId, $userData['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
    }

    // Get messages
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $senderType = $isAdmin ? 'customer' : 'admin';
    $updateStmt = $db->prepare("UPDATE chat_messages SET is_read = 1 
                                WHERE conversation_id = ? AND sender_type = ? AND is_read = 0");
    $updateStmt->execute([$conversationId, $senderType]);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
}

/**
 * Send a message
 */
function sendMessage($db, $userData) {
    $input = json_decode(file_get_contents('php://input'), true);
    $conversationId = intval($input['conversation_id'] ?? 0);
    $message = trim($input['message'] ?? '');

    if (!$conversationId || empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID and message are required']);
        return;
    }

    // Verify access
    $isAdmin = $userData['role'] === 'admin';
    if (!$isAdmin) {
        // Check if conversation belongs to user
        $stmt = $db->prepare("SELECT id FROM chat_conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversationId, $userData['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }
    }

    try {
        $db->beginTransaction();

        // Insert message
        $senderType = $isAdmin ? 'admin' : 'customer';
        $stmt = $db->prepare("INSERT INTO chat_messages (conversation_id, sender_type, sender_name, message) 
                             VALUES (?, ?, ?, ?)");
        $stmt->execute([$conversationId, $senderType, $userData['name'], $message]);

        // Update conversation
        $stmt = $db->prepare("UPDATE chat_conversations 
                             SET last_message = ?, last_message_at = NOW(), updated_at = NOW() 
                             WHERE id = ?");
        $stmt->execute([$message, $conversationId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
?>
