<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../api/notifications/NotificationService.php';

try {
    $pdo = Database::getInstance()->getPDO();
    $notificationService = new NotificationService($pdo);
    
    echo "=== CREATE TEST NOTIFICATION ===\n\n";
    
    // Get admin user
    $admin = $pdo->query("SELECT id, name, email FROM users WHERE role='admin' LIMIT 1")->fetch();
    
    if (!$admin) {
        echo "❌ No admin user found!\n";
        exit;
    }
    
    echo "Creating notification for: {$admin['name']} (ID: {$admin['id']})\n\n";
    
    // Create test notification
    $notifId = $notificationService->create(
        $admin['id'],
        'system',
        'Test notification from POS system - Sistem notifikasi berhasil diperbaiki!',
        ['test' => true, 'timestamp' => time()],
        '/admin/orders'
    );
    
    if ($notifId) {
        echo "✅ Notification created with ID: {$notifId}\n";
        echo "\n📱 Refresh your browser to see the notification!\n";
        echo "   Badge should show '1' and notification should appear in dropdown.\n";
    } else {
        echo "❌ Failed to create notification\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
