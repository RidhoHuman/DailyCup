<?php
/**
 * Get Unread Count API
 * GET /api/notifications/count.php
 * 
 * Returns just the unread count for polling
 */

// CORS must be first
require_once __DIR__ . '/../cors.php';

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
    // Debug log to help trace CI/local test tokens
    error_log('[notifications/count.php] JWT::getUser returned null (unauthorized)');
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

// Use $pdo from database.php (already connected with proper defaults)
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available', 'count' => 0]);
    exit;
}

require_once __DIR__ . '/NotificationService.php';

$notificationService = new NotificationService($pdo);
$count = $notificationService->getUnreadCount($userId);

error_log("Notification count.php: user_id={$userId}, unread_count={$count}");

echo json_encode([
    'success' => true,
    'count' => $count
]);
