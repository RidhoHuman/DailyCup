<?php
/**
 * Get Unread Count API
 * GET /api/notifications/count.php
 * 
 * Returns just the unread count for polling
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate user
$user = JWT::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'count' => 0]);
    exit;
}

// Extract user ID
$userId = isset($user['user_id']) ? (int)$user['user_id'] : (isset($user['id']) ? (int)$user['id'] : null);

if (!$userId) {
    echo json_encode(['success' => false, 'count' => 0]);
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
    echo json_encode(['error' => 'Database connection failed', 'count' => 0]);
    exit;
}

require_once __DIR__ . '/NotificationService.php';

$notificationService = new NotificationService($pdo);
$count = $notificationService->getUnreadCount($userId);

echo json_encode([
    'success' => true,
    'count' => $count
]);
