<?php
/**
 * Subscribe to Push Notifications
 * POST /api/notifications/subscribe.php
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

$userId = isset($user['user_id']) ? (int)$user['user_id'] : (isset($user['id']) ? (int)$user['id'] : null);

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user token']);
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

if (!isset($body['endpoint']) || !isset($body['keys'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing subscription data']);
    exit;
}

$endpoint = $body['endpoint'];
$p256dh = $body['keys']['p256dh'] ?? '';
$auth = $body['keys']['auth'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    // Check if subscription already exists
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$endpoint]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing subscription
        $stmt = $pdo->prepare("
            UPDATE push_subscriptions 
            SET user_id = ?, p256dh_key = ?, auth_key = ?, user_agent = ?, is_active = 1, updated_at = NOW()
            WHERE endpoint = ?
        ");
        $stmt->execute([$userId, $p256dh, $auth, $userAgent, $endpoint]);
    } else {
        // Insert new subscription
        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth, $userAgent]);
    }

    echo json_encode(['success' => true, 'message' => 'Subscription saved']);
} catch (PDOException $e) {
    error_log("Push subscription error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save subscription']);
}
