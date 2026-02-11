<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT id, email, name, role FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'count' => count($users),
        'users' => $users
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
