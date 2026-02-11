<?php
/**
 * Mark Notification as Read API
 * POST /api/notifications/read.php
 * 
 * Body:
 * - id: notification ID to mark as read
 * - all: true to mark all as read
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate user
$user = JWT::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Extract user ID
$userId = isset($user['user_id']) ? (int)$user['user_id'] : (isset($user['id']) ? (int)$user['id'] : null);

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user token']);
    exit;
}

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

require_once __DIR__ . '/NotificationService.php';

$notificationService = new NotificationService($pdo);

// Get body
$body = json_decode(file_get_contents('php://input'), true);

if (isset($body['all']) && $body['all'] === true) {
    // Mark all as read
    $count = $notificationService->markAllAsRead($userId);
    echo json_encode([
        'success' => true,
        'message' => "Marked {$count} notifications as read"
    ]);
} elseif (isset($body['id'])) {
    // Mark single notification as read
    $notificationId = (int)$body['id'];
    $result = $notificationService->markAsRead($notificationId, $userId);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id or all parameter']);
}
