-- DailyCup Database Schema for Users Table
-- Run this in phpMyAdmin or MySQL CLI to create the users table

-- Create users table if not exists
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `loyalty_points` INT DEFAULT 0,
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert demo users for testing
-- Password for both: "password123"
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `loyalty_points`) VALUES
('Demo User', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'customer', 100),
('Admin User', 'admin@dailycup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '089876543210', 'admin', 0)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Note: The password hash above is for "password123" using bcrypt
-- You can generate new hashes with: password_hash('your_password', PASSWORD_DEFAULT)
