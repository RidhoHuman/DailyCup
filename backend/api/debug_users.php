<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

try {
    // Get all active users (without passwords for security)
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, created_at 
        FROM users 
        WHERE is_active = 1
        ORDER BY id
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'total' => count($users),
        'users' => $users
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
