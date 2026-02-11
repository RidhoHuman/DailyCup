<?php
// Test endpoint to send a notification to a user
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Get user from token
$token = getBearerToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

try {
    $userData = validateToken($token);
    $userId = $userData['user_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get parameters
$type = $data['type'] ?? 'info';
$title = $data['title'] ?? 'Test Notification';
$message = $data['message'] ?? 'This is a test notification';
$actionUrl = $data['action_url'] ?? null;
$icon = $data['icon'] ?? '';
$metadata = $data['metadata'] ?? [];

try {
    $query = "INSERT INTO user_notifications 
              (user_id, type, title, message, data, icon, action_url) 
              VALUES 
              (:user_id, :type, :title, :message, :data, :icon, :action_url)";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':message', $message);
    $stmt->bindValue(':data', json_encode($metadata));
    $stmt->bindParam(':icon', $icon);
    $stmt->bindParam(':action_url', $actionUrl);

    if ($stmt->execute()) {
        $notificationId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Notification sent',
            'id' => $notificationId,
            'notification' => [
                'id' => $notificationId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $metadata,
                'icon' => $icon,
                'action_url' => $actionUrl,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to create notification');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating notification: ' . $e->getMessage()
    ]);
}
