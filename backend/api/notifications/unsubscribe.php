<?php
/**
 * Unsubscribe from Push Notifications
 * POST /api/notifications/unsubscribe.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user = JWT::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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

$body = json_decode(file_get_contents('php://input'), true);

if (!isset($body['endpoint'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing endpoint']);
    exit;
}

$endpoint = $body['endpoint'];

try {
    $stmt = $pdo->prepare("UPDATE push_subscriptions SET is_active = 0 WHERE endpoint = ?");
    $stmt->execute([$endpoint]);

    echo json_encode(['success' => true, 'message' => 'Unsubscribed successfully']);
} catch (PDOException $e) {
    error_log("Push unsubscribe error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to unsubscribe']);
}
