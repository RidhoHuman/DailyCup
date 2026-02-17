<?php
// Reset admin password
// Usage: https://api.dailycup.com/reset_admin_password.php

require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';

try {
    // New password: admin123
    $newPassword = 'admin123';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update admin user password
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ?, updated_at = NOW()
        WHERE email = 'admin@dailycup.com'
    ");
    $stmt->execute([$hashedPassword]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Admin password reset successful',
            'email' => 'admin@dailycup.com',
            'new_password' => $newPassword,
            'note' => 'DELETE THIS FILE IMMEDIATELY after testing!'
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['error' => 'Admin user not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
