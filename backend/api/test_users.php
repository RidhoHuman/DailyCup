<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $conn = getDbConnection();
    
    // Check if users table exists and has data
    $result = $conn->query("SELECT id, email, name, role FROM users LIMIT 5");
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Don't show password hash
            $users[] = [
                'id' => $row['id'],
                'email' => $row['email'],
                'name' => $row['name'],
                'role' => $row['role']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'total_users' => count($users),
        'users' => $users
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
