<?php
/**
 * Send Push Notification API
 * Backend endpoint to send push notifications to subscribed users
 * Requires: composer require minishlink/web-push
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

// Check if web-push library is installed
if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Web Push library not installed',
        'install' => 'Run: cd backend && composer require minishlink/web-push'
    ]);
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify JWT token
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

try {
    $decoded = validateJWT($token);
    $userId = $decoded->user_id;
    $isAdmin = $decoded->role === 'admin';
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['title']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing title or message']);
    exit;
}

$title = $data['title'];
$message = $data['message'];
$url = $data['url'] ?? '/';
$icon = $data['icon'] ?? '/assets/image/cup.png';
$badge = $data['badge'] ?? '/logo/cup-badge.png';
$tag = $data['tag'] ?? 'dailycup-notification';
$targetUserId = $data['user_id'] ?? null;
$notificationType = $data['type'] ?? 'general';

// Load VAPID keys from environment or config
$vapidPublicKey = getenv('VAPID_PUBLIC_KEY') ?: '';
$vapidPrivateKey = getenv('VAPID_PRIVATE_KEY') ?: '';
$vapidSubject = getenv('VAPID_SUBJECT') ?: 'mailto:admin@dailycup.com';

if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'VAPID keys not configured',
        'hint' => 'Set VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY in .env file'
    ]);
    exit;
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

try {
    // Build query based on target
    if ($targetUserId) {
        // Send to specific user (only admin or self)
        if (!$isAdmin && $targetUserId != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot send to other users']);
            exit;
        }
        
        $query = "SELECT ps.*, np.push_enabled, np.{$notificationType} as type_enabled
                 FROM push_subscriptions ps
                 LEFT JOIN notification_preferences np ON ps.user_id = np.user_id
                 WHERE ps.user_id = :user_id AND ps.is_active = 1
                 AND (np.push_enabled = 1 OR np.push_enabled IS NULL)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $targetUserId);
    } else {
        // Send to all users (admin only)
        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['error' => 'Only admins can broadcast notifications']);
            exit;
        }
        
        $query = "SELECT ps.*, np.push_enabled, np.{$notificationType} as type_enabled
                 FROM push_subscriptions ps
                 LEFT JOIN notification_preferences np ON ps.user_id = np.user_id
                 WHERE ps.is_active = 1
                 AND (np.push_enabled = 1 OR np.push_enabled IS NULL)";
        
        $stmt = $db->prepare($query);
    }
    
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($subscriptions)) {
        echo json_encode([
            'success' => true,
            'message' => 'No active subscriptions found',
            'sent' => 0,
            'failed' => 0
        ]);
        exit;
    }
    
    // Initialize WebPush
    $auth = [
        'VAPID' => [
            'subject' => $vapidSubject,
            'publicKey' => $vapidPublicKey,
            'privateKey' => $vapidPrivateKey,
        ],
    ];
    
    $webPush = new WebPush($auth);
    
    // Prepare notification payload
    $payload = json_encode([
        'title' => $title,
        'message' => $message,
        'body' => $message,
        'url' => $url,
        'action_url' => $url,
        'icon' => $icon,
        'badge' => $badge,
        'tag' => $tag,
        'timestamp' => time()
    ]);
    
    // Send to all subscriptions
    $sentCount = 0;
    $failedCount = 0;
    $failedEndpoints = [];
    
    foreach ($subscriptions as $sub) {
        // Skip if notification type is disabled
        if (isset($sub['type_enabled']) && $sub['type_enabled'] == 0) {
            continue;
        }
        
        try {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh_key'],
                    'auth' => $sub['auth_key']
                ]
            ]);
            
            $webPush->queueNotification($subscription, $payload);
            $sentCount++;
        } catch (Exception $e) {
            $failedCount++;
            $failedEndpoints[] = [
                'endpoint' => substr($sub['endpoint'], 0, 50) . '...',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Send all queued notifications
    $results = $webPush->flush();
    
    // Process results and update database
    foreach ($results as $result) {
        $subscription = $result['subscription'];
        $endpoint = $subscription->getEndpoint();
        
        if ($result['success']) {
            // Update last_used_at
            $updateQuery = "UPDATE push_subscriptions 
                           SET last_used_at = NOW() 
                           WHERE endpoint = :endpoint";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':endpoint', $endpoint);
            $updateStmt->execute();
        } else {
            // Mark as inactive if permanently failed (410 Gone or 404 Not Found)
            if ($result['statusCode'] == 410 || $result['statusCode'] == 404) {
                $deactivateQuery = "UPDATE push_subscriptions 
                                   SET is_active = 0 
                                   WHERE endpoint = :endpoint";
                $deactivateStmt = $db->prepare($deactivateQuery);
                $deactivateStmt->bindParam(':endpoint', $endpoint);
                $deactivateStmt->execute();
                
                $failedCount++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Push notifications sent',
        'sent' => $sentCount,
        'failed' => $failedCount,
        'total_subscriptions' => count($subscriptions),
        'failed_endpoints' => $failedEndpoints
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to send push notifications',
        'message' => $e->getMessage()
    ]);
}
