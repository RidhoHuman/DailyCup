<?php
require_once __DIR__ . '/../../../config/database.php';

try {
    $pdo = Database::getInstance()->getPDO();
    
    echo "=== TESTING NOTIFICATIONS ===\n\n";
    
    // Get all users
    echo "1. Available Users:\n";
    $users = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5")->fetchAll();
    foreach ($users as $u) {
        echo "  - ID: {$u['id']}, Name: {$u['name']}, Email: {$u['email']}, Role: {$u['role']}\n";
    }
    
    echo "\n2. Notifications Table Structure:\n";
    $columns = $pdo->query("DESCRIBE notifications")->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']}\n";
    }
    
    echo "\n3. Sample Notifications:\n";
    $notifs = $pdo->query("SELECT id, user_id, type, title, message, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5")->fetchAll();
    if (empty($notifs)) {
        echo "  ❌ No notifications found!\n";
    } else {
        foreach ($notifs as $n) {
            $read = $n['is_read'] ? '✓ Read' : '✗ Unread';
            echo "  - ID: {$n['id']}, User: {$n['user_id']}, Type: {$n['type']}, {$read}\n";
            echo "    Title: {$n['title']}\n";
            echo "    Message: {$n['message']}\n";
            echo "    Created: {$n['created_at']}\n\n";
        }
    }
    
    echo "\n4. Recent Orders (for notification context):\n";
    $orders = $pdo->query("SELECT id, user_id, order_number, status, created_at FROM orders ORDER BY created_at DESC LIMIT 3")->fetchAll();
    foreach ($orders as $o) {
        echo "  - Order: {$o['order_number']}, User: {$o['user_id']}, Status: {$o['status']}, Created: {$o['created_at']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
