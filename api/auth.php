<?php
/**
 * API Authentication Endpoint (JWT)
 * Phase 3: Enhanced Functionality
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Log access for debugging
error_log("API Auth accessed: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit;
}

// 1. Rate limiting - Prevent brute force
checkRateLimit($email . '_api_login', 10, 900);

try {
    // Get PDO connection
    $db = Database::getInstance()->getPDO();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // 2. Check if user is active
        if (!$user['is_active']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Account is inactive']);
            exit;
        }

        // 3. Check if email verified (Security Requirement Phase 2)
        if (!$user['email_verified']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Email not verified. Please check your inbox.']);
            exit;
        }

        // 4. Generate JWT Token
        $token = generateJWT($user['id'], [
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['name']
        ]);

        // 5. Log Activity
        logActivity('api_login_success', 'user', $user['id'], ['ip' => $_SERVER['REMOTE_ADDR']]);
        
        // Also log to login history
        logLoginAttempt($user['id'], true);

        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Authentication successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'loyalty_points' => $user['loyalty_points']
            ]
        ]);
    } else {
        // Log failure
        logActivity('api_login_failed', null, null, ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
        
        if ($user) {
            logLoginAttempt($user['id'], false, 'Invalid password');
        }

        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    }
} catch (Exception $e) {
    error_log("API Auth Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error occurred']);
}
