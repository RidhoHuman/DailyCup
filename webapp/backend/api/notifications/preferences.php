<?php
/**
 * Notification Preferences API
 * Manage user notification settings
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Verify JWT token
$headers = getallheaders();
$token = null;

// Check for Authorization header (case-insensitive)
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $token = str_replace('Bearer ', '', $value);
        break;
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

// Verify JWT token using JWT::verify()
$decoded = JWT::verify($token);
if (!$decoded || !isset($decoded['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}
$userId = $decoded['user_id'];

// Use $pdo from database.php (already connected)
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available']);
    exit;
}
$db = $pdo; // Alias for compatibility with existing code

// Handle different methods
switch ($method) {
    case 'GET':
        // Get user's notification preferences
        try {
            $query = "SELECT * FROM notification_preferences WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$preferences) {
                // Create default preferences
                $insertQuery = "INSERT INTO notification_preferences (user_id) VALUES (:user_id)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->bindParam(':user_id', $userId);
                $insertStmt->execute();
                
                // Fetch again
                $stmt->execute();
                $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Convert boolean fields
            $boolFields = [
                'push_enabled', 'email_enabled', 'order_updates', 'payment_updates',
                'promotions', 'new_products', 'reviews', 'admin_messages', 'quiet_hours_enabled'
            ];
            
            foreach ($boolFields as $field) {
                if (isset($preferences[$field])) {
                    $preferences[$field] = (bool) $preferences[$field];
                }
            }
            
            echo json_encode([
                'success' => true,
                'preferences' => $preferences
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch preferences: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
    case 'PATCH':
        // Update notification preferences
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'No data provided']);
            exit;
        }
        
        try {
            // Build update query
            $allowedFields = [
                'push_enabled', 'email_enabled', 'order_updates', 'payment_updates',
                'promotions', 'new_products', 'reviews', 'admin_messages',
                'quiet_hours_enabled', 'quiet_hours_start', 'quiet_hours_end'
            ];
            
            $updates = [];
            $params = [':user_id' => $userId];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updates[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                exit;
            }
            
            $query = "UPDATE notification_preferences 
                     SET " . implode(', ', $updates) . ", updated_at = NOW() 
                     WHERE user_id = :user_id";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences updated',
                'updated_fields' => array_keys($data)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update preferences: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
