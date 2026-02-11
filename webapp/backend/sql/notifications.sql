-- DailyCup Database Schema for Notifications System
-- Run this in phpMyAdmin or MySQL CLI

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('order_created', 'payment_received', 'order_processing', 'order_shipped', 'order_delivered', 'order_cancelled', 'promo', 'system', 'review_reminder') NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `data` JSON DEFAULT NULL COMMENT 'Additional data like order_id, product_id, etc.',
    `icon` VARCHAR(50) DEFAULT 'bell' COMMENT 'Icon identifier for UI',
    `action_url` VARCHAR(255) DEFAULT NULL COMMENT 'URL to navigate when clicked',
    `is_read` TINYINT(1) DEFAULT 0,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_user_unread` (`user_id`, `is_read`, `created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create push_subscriptions table for Web Push
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `endpoint` TEXT NOT NULL,
    `p256dh_key` VARCHAR(500) NOT NULL COMMENT 'Public key for encryption',
    `auth_key` VARCHAR(500) NOT NULL COMMENT 'Auth secret for encryption',
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_active` (`is_active`),
    UNIQUE KEY `unique_endpoint` (`endpoint`(255)),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create notification_preferences table
CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `email_order_updates` TINYINT(1) DEFAULT 1,
    `email_promotions` TINYINT(1) DEFAULT 1,
    `push_order_updates` TINYINT(1) DEFAULT 1,
    `push_promotions` TINYINT(1) DEFAULT 0,
    `inapp_order_updates` TINYINT(1) DEFAULT 1,
    `inapp_promotions` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default notification preferences for existing users
INSERT IGNORE INTO `notification_preferences` (`user_id`)
SELECT `id` FROM `users`;
