<?php
/**
 * Get Notifications API
 * GET /api/notifications/get.php
 * 
 * Query params:
 * - limit: number of notifications (default 20)
 * - offset: pagination offset (default 0)
 * - unread: 1 to get unread only
 */

require_once __DIR__ . '/../../config/database.php';
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
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Extract user ID from JWT payload
$userId = isset($user['user_id']) ? (int)$user['user_id'] : (isset($user['id']) ? (int)$user['id'] : null);

if (!$userId) {
    error_log("Notification get.php: Invalid user ID in JWT token: " . json_encode($user));
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user token']);
    exit;
}

// Use $pdo from database.php (already connected with proper defaults)
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available']);
    exit;
}

require_once __DIR__ . '/NotificationService.php';

$notificationService = new NotificationService($pdo);

// Get query params
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] == '1';

// Get notifications
try {
    $notifications = $notificationService->getByUser($userId, $limit, $offset, $unreadOnly);
    $unreadCount = $notificationService->getUnreadCount($userId);
} catch (Exception $e) {
    error_log("Notification get.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch notifications', 'message' => $e->getMessage()]);
    exit;
}

// Format response
$response = [
    'success' => true,
    'data' => [
        'notifications' => $notifications,
        'unread_count' => $unreadCount,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => count($notifications) === $limit
        ]
    ]
];

echo json_encode($response);
