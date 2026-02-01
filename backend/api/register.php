<?php
/**
 * Register API Endpoint
 * 
 * Creates new user account and returns JWT token
 * POST /api/register.php
 * Body: { name: string, email: string, password: string, phone?: string }
 */

// CORS must be first
require_once __DIR__ . '/cors.php';

// require_once __DIR__ . '/config.php';
require_once __DIR__ . '/input_sanitizer.php';
// require_once __DIR__ . '/rate_limiter.php';

header('Content-Type: application/json');

// Rate limiting - temporarily disabled for debugging
// $clientIP = RateLimiter::getClientIP();
// RateLimiter::enforce($clientIP, 'default');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/config/database.php';
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
    $name = InputSanitizer::string($input['name'] ?? '');
    $email = InputSanitizer::email($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $phone = InputSanitizer::phone($input['phone'] ?? '');

    // Validation
    $errors = [];
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['error' => 'Validation failed', 'details' => $errors]);
        exit;
    }

    // Check if email already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Email already registered']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, phone, role, loyalty_points, is_active, created_at)
        VALUES (?, ?, ?, ?, 'customer', 0, 1, NOW())
    ");
    $stmt->execute([$name, $email, $hashedPassword, $phone ?: null]);
    
    $userId = $pdo->lastInsertId();

    // Generate JWT token
    $tokenPayload = [
        'user_id' => $userId,
        'email' => $email,
        'role' => 'customer'
    ];
    $token = JWT::generate($tokenPayload);

    // Return user data
    $userData = [
        'id' => (int) $userId,
        'name' => $name,
        'email' => $email,
        'phone' => $phone ?: null,
        'address' => null,
        'role' => 'customer',
        'loyaltyPoints' => 0,
        'profilePicture' => null,
        'joinDate' => date('Y-m-d H:i:s')
    ];

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => $userData,
        'token' => $token
    ]);

} catch (PDOException $e) {
    error_log("Register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
