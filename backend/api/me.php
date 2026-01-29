<?php
/**
 * Get Current User API Endpoint
 * 
 * Returns current authenticated user data
 * GET /api/me.php
 * Requires: Authorization: Bearer <token>
 */

// CORS handled by .htaccess

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require authentication
$authUser = JWT::requireAuth();

require_once '../config/database.php';

try {
    // Get full user data
    $stmt = $pdo->prepare("
        SELECT id, name, email, phone, address, role, 
               loyalty_points, profile_image, created_at
        FROM users 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$authUser['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Return user data
    $userData = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'address' => $user['address'],
        'role' => $user['role'],
        'loyaltyPoints' => (int) ($user['loyalty_points'] ?? 0),
        'profilePicture' => $user['profile_image'],
        'joinDate' => $user['created_at']
    ];

    echo json_encode([
        'success' => true,
        'user' => $userData
    ]);

} catch (PDOException $e) {
    error_log("Get user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    error_log("Get user error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
