<?php
require_once __DIR__ . '/../../cors.php';
/**
 * Admin Profile API
 * 
 * NOTE: CORS is handled by .htaccess in /backend/api/
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

// Create admin_profile table if not exists (auto-migration)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_profile (
        user_id INT PRIMARY KEY,
        phone VARCHAR(20),
        bio TEXT,
        avatar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (Exception $e) {
    // Ignore if table already exists
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get admin profile from users table
            // TODO: Get user_id from JWT token, for now use first admin
            $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE role = 'admin' LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Admin user not found']);
                exit;
            }
            
            // Get additional profile data (phone, bio) from admin_profile if exists
            $stmt = $pdo->prepare("SELECT phone, bio, avatar FROM admin_profile WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $extraData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $profile = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $extraData['phone'] ?? '+62 812-3456-7890',
                'role' => ucwords($user['role']),
                'bio' => $extraData['bio'] ?? 'Managing DailyCup Coffee Shop',
                'avatar' => $extraData['avatar'] ?? null
            ];
            
            echo json_encode(['success' => true, 'data' => $profile]);
            break;

        case 'PUT':
            // Update profile
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                exit;
            }
            
            // Get admin user
            $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Admin user not found']);
                exit;
            }
            
            $userId = $user['id'];
            
            // Update users table (name, email)
            $updateFields = [];
            $params = [':user_id' => $userId];
            
            if (isset($data['name'])) {
                $updateFields[] = "name = :name";
                $params[':name'] = $data['name'];
            }
            
            if (isset($data['email'])) {
                $updateFields[] = "email = :email";
                $params[':email'] = $data['email'];
            }
            
            if (!empty($updateFields)) {
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            // Update or insert admin_profile (phone, bio)
            if (isset($data['phone']) || isset($data['bio'])) {
                $stmt = $pdo->prepare("INSERT INTO admin_profile (user_id, phone, bio) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE phone = VALUES(phone), bio = VALUES(bio), updated_at = NOW()");
                $stmt->execute([
                    $userId,
                    $data['phone'] ?? null,
                    $data['bio'] ?? null
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            break;

        case 'POST':
            // Change password endpoint
            if (isset($_GET['action']) && $_GET['action'] === 'change-password') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate input
                if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                    exit;
                }
                
                // Get admin user with password
                $stmt = $pdo->query("SELECT id, password FROM users WHERE role = 'admin' LIMIT 1");
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
                    exit;
                }
                
                // Verify current password
                if (!password_verify($data['currentPassword'], $user['password'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                    exit;
                }
                
                // Validate new password length
                if (strlen($data['newPassword']) < 6) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
                    exit;
                }
                
                // Hash and update new password
                $newPasswordHash = password_hash($data['newPassword'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newPasswordHash, $user['id']]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>