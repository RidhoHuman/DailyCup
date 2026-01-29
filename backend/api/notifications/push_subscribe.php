<?php
/**
 * Push Subscription Management API
 * Handles subscribing and unsubscribing from push notifications
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Handle different methods
switch ($method) {
    case 'GET':
        // Get user's push subscriptions
        try {
            $query = "SELECT id, endpoint, is_active, created_at, last_used_at 
                     FROM push_subscriptions 
                     WHERE user_id = :user_id AND is_active = 1 
                     ORDER BY created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'subscriptions' => $subscriptions,
                'count' => count($subscriptions)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch subscriptions: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Subscribe to push notifications
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['endpoint']) || !isset($data['keys'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing endpoint or keys']);
            exit;
        }
        
        $endpoint = $data['endpoint'];
        $p256dh = $data['keys']['p256dh'] ?? '';
        $auth = $data['keys']['auth'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (empty($p256dh) || empty($auth)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid subscription keys']);
            exit;
        }
        
        try {
            // Check if subscription already exists
            $checkQuery = "SELECT id FROM push_subscriptions 
                          WHERE user_id = :user_id AND endpoint = :endpoint";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':endpoint', $endpoint);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                // Update existing subscription
                $updateQuery = "UPDATE push_subscriptions 
                               SET p256dh_key = :p256dh, auth_key = :auth, 
                                   is_active = 1, updated_at = NOW()
                               WHERE user_id = :user_id AND endpoint = :endpoint";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':p256dh', $p256dh);
                $updateStmt->bindParam(':auth', $auth);
                $updateStmt->bindParam(':user_id', $userId);
                $updateStmt->bindParam(':endpoint', $endpoint);
                $updateStmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Subscription updated',
                    'action' => 'updated'
                ]);
            } else {
                // Insert new subscription
                $insertQuery = "INSERT INTO push_subscriptions 
                               (user_id, endpoint, p256dh_key, auth_key, user_agent) 
                               VALUES (:user_id, :endpoint, :p256dh, :auth, :user_agent)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->bindParam(':endpoint', $endpoint);
                $insertStmt->bindParam(':p256dh', $p256dh);
                $insertStmt->bindParam(':auth', $auth);
                $insertStmt->bindParam(':user_agent', $userAgent);
                $insertStmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Subscribed to push notifications',
                    'subscription_id' => $db->lastInsertId(),
                    'action' => 'created'
                ]);
            }
            
            // Ensure user has notification preferences
            $prefQuery = "INSERT IGNORE INTO notification_preferences (user_id) VALUES (:user_id)";
            $prefStmt = $db->prepare($prefQuery);
            $prefStmt->bindParam(':user_id', $userId);
            $prefStmt->execute();
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to subscribe: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Unsubscribe from push notifications
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['endpoint'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing endpoint']);
            exit;
        }
        
        $endpoint = $data['endpoint'];
        
        try {
            $query = "UPDATE push_subscriptions 
                     SET is_active = 0 
                     WHERE user_id = :user_id AND endpoint = :endpoint";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':endpoint', $endpoint);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Unsubscribed from push notifications'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to unsubscribe: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
