-- DailyCup Database Schema for Orders Table
-- Run this in phpMyAdmin or MySQL CLI to create the orders table

-- Create orders table if not exists
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(20) DEFAULT NULL,
    `customer_address` TEXT DEFAULT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `delivery_fee` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(12,2) NOT NULL,
    `status` ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'failed') DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `payment_id` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create order_items table for order line items
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL,
    `product_id` INT DEFAULT NULL,
    `product_name` VARCHAR(200) NOT NULL,
    `variant` VARCHAR(100) DEFAULT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(12,2) NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_product_id` (`product_id`)
    -- Note: foreign key on order_id (VARCHAR) removed for compatibility with some hosts (e.g., InfinityFree)
    -- To enforce referential integrity, consider referencing orders.id (INT) or add application-level checks
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
