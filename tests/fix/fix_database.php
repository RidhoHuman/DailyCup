<?php
require_once __DIR__ . '/../config/database.php';

echo "=== FIXING DATABASE STRUCTURE ===\n\n";

try {
    $db = getDB();
    
    // 1. Create refunds table
    echo "Creating refunds table...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `refunds` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `order_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `reason` text NOT NULL,
      `bank_name` varchar(100) DEFAULT NULL,
      `account_number` varchar(50) DEFAULT NULL,
      `account_holder` varchar(100) DEFAULT NULL,
      `proof_image` varchar(255) DEFAULT NULL,
      `status` enum('pending','approved','rejected','processed') DEFAULT 'pending',
      `admin_notes` text,
      `approved_by` int(11) DEFAULT NULL,
      `approved_at` datetime DEFAULT NULL,
      `processed_at` datetime DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `order_id` (`order_id`),
      KEY `user_id` (`user_id`),
      KEY `status` (`status`),
      CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
      CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Refunds table created\n";
    
    // 2. Add refund_count column
    echo "\nAdding users.refund_count column...\n";
    try {
        $db->exec("ALTER TABLE `users` ADD COLUMN `refund_count` int(11) DEFAULT 0");
        echo "✓ users.refund_count added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ users.refund_count already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Add last_refund_date column
    echo "Adding users.last_refund_date column...\n";
    try {
        $db->exec("ALTER TABLE `users` ADD COLUMN `last_refund_date` date DEFAULT NULL");
        echo "✓ users.last_refund_date added\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ users.last_refund_date already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Create indexes
    echo "\nCreating indexes...\n";
    try {
        $db->exec("CREATE INDEX idx_refunds_created ON `refunds` (`created_at`)");
        echo "✓ Index on refunds.created_at created\n";
    } catch (PDOException $e) {
        echo "ℹ Index on refunds.created_at already exists\n";
    }
    
    try {
        $db->exec("CREATE INDEX idx_users_refund ON `users` (`refund_count`, `last_refund_date`)");
        echo "✓ Index on users refund columns created\n";
    } catch (PDOException $e) {
        echo "ℹ Index on users refund columns already exists\n";
    }
    
    echo "\n=== DATABASE FIX COMPLETE ===\n";
    echo "✓ All structures are ready!\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
