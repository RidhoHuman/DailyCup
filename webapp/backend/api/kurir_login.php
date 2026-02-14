<?php
/**
 * Kurir Login API
 * Authenticate courier with phone and password
 */

require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $phone = $input['phone'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($phone) || empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Phone and password are required'
        ]);
        exit;
    }
    
    // Get kurir from database
    $stmt = $pdo->prepare("
        SELECT id, name, phone, email, photo, vehicle_type, vehicle_number, 
               status, rating, total_deliveries, is_active, password
        FROM kurir 
        WHERE phone = ?
    ");
    $stmt->execute([$phone]);
    $kurir = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kurir) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid phone number or password'
        ]);
        exit;
    }
    
    // Check if kurir is active
    if (!$kurir['is_active']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Your account has been suspended. Please contact admin.'
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $kurir['password'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid phone number or password'
        ]);
        exit;
    }
    
    // Remove password from response
    unset($kurir['password']);
    
    // Generate JWT token
    $token = JWT::generate([
        'user_id' => $kurir['id'],
        'role' => 'kurir',
        'phone' => $kurir['phone'],
        'name' => $kurir['name']
    ]);
    
    // Update last login
    $updateStmt = $pdo->prepare("
        UPDATE kurir 
        SET updated_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$kurir['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $kurir,
        'token' => $token
    ]);
    
} catch (PDOException $e) {
    error_log("Kurir Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed. Please try again.'
    ]);
}
?>
