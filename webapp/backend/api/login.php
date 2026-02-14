<?php
/**
 * Login API Endpoint
 * 
 * Authenticates user and returns JWT token
 * POST /api/login.php
 * Body: { email: string, password: string }
 */

// CORS must be first - use centralized CORS handler
require_once __DIR__ . '/cors.php';

// CORS handler already exits for OPTIONS, so code below only runs for actual requests

require_once __DIR__ . '/input_sanitizer.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Only accept POST (OPTIONS already handled by cors.php)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    // Validate required fields
    $email = InputSanitizer::email($input['email'] ?? '');
    $password = $input['password'] ?? '';

    // Debug logging: record incoming login attempts (do not log passwords)
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $logMsg = "[LOGIN DEBUG] Attempt from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " - Email: {$email} - Origin: " . ($headers['Origin'] ?? $headers['origin'] ?? 'unknown') . " - UA: " . ($headers['User-Agent'] ?? $headers['user-agent'] ?? 'unknown');
    error_log($logMsg);
    @file_put_contents(__DIR__ . '/../logs/login_debug.log', $logMsg . PHP_EOL, FILE_APPEND);

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        exit;
    }

    // Find user by email (removed profile_picture as it may not exist in all schemas)
    $stmt = $pdo->prepare("
        SELECT id, name, email, password, phone, address, role, 
               loyalty_points, created_at
        FROM users 
        WHERE email = ? AND is_active = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        exit;
    }

    // Generate JWT token
    $tokenPayload = [
        'user_id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    $token = JWT::generate($tokenPayload);

    // Return user data (without password)
    $userData = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? '',
        'address' => $user['address'] ?? '',
        'role' => $user['role'],
        'loyaltyPoints' => (int) ($user['loyalty_points'] ?? 0),
        'profilePicture' => null, // Not in database yet
        'joinDate' => $user['created_at']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $userData,
        'token' => $token
    ]);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'details' => $e->getMessage()]);
}
