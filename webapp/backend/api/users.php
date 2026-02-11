<?php
/**
 * Users API - Search and List
 * For admin to find existing customers
 */

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Only GET allowed
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Require authentication
$authUser = JWT::getUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Only admin can search all users
if ($authUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

try {
    // $pdo is already created by config/database.php require above
    global $pdo;
    
    // Search query
    $search = $_GET['search'] ?? '';
    
    if (strlen($search) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    // Search by name or email
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            name, 
            email, 
            phone, 
            address 
        FROM users 
        WHERE (name LIKE ? OR email LIKE ?) 
        AND role = 'customer'
        ORDER BY name ASC 
        LIMIT 10
    ");
    
    $searchParam = '%' . $search . '%';
    $stmt->execute([$searchParam, $searchParam]);
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
    
} catch (PDOException $e) {
    error_log("Users search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to search users'
    ]);
}
