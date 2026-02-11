-- ========================================
-- DailyCup Order Lifecycle System Migration
-- Phase 2: Real-Time Order Tracking
-- ========================================

-- 1. Update orders table: Add new status values and tracking fields
ALTER TABLE `orders` 
MODIFY COLUMN `status` ENUM(
    'pending_payment',      -- Menunggu pembayaran
    'waiting_confirmation', -- Menunggu konfirmasi COD/OTP
    'queueing',            -- Antrian pesanan
    'preparing',           -- Sedang diproses
    'on_delivery',         -- Dalam perjalanan
    'completed',           -- Selesai/delivered
    'cancelled'            -- Dibatalkan
) DEFAULT 'pending_payment';

-- Add courier and tracking fields to orders
ALTER TABLE `orders` 
ADD COLUMN `courier_id` INT DEFAULT NULL AFTER `payment_id`,
ADD COLUMN `courier_photo` VARCHAR(255) DEFAULT NULL AFTER `courier_id`,
ADD COLUMN `estimated_delivery` DATETIME DEFAULT NULL AFTER `courier_photo`,
ADD COLUMN `completed_at` DATETIME DEFAULT NULL AFTER `estimated_delivery`,
ADD COLUMN `cancelled_at` DATETIME DEFAULT NULL AFTER `completed_at`,
ADD COLUMN `cancellation_reason` TEXT DEFAULT NULL AFTER `cancelled_at`,
ADD INDEX `idx_courier_id` (`courier_id`),
ADD INDEX `idx_estimated_delivery` (`estimated_delivery`);

-- 2. Create couriers table
CREATE TABLE IF NOT EXISTS `couriers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `vehicle_type` ENUM('motorcycle', 'car', 'bicycle') DEFAULT 'motorcycle',
    `vehicle_number` VARCHAR(20) DEFAULT NULL,
    `current_location_lat` DECIMAL(10, 8) DEFAULT NULL,
    `current_location_lng` DECIMAL(11, 8) DEFAULT NULL,
    `is_available` BOOLEAN DEFAULT TRUE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `total_deliveries` INT DEFAULT 0,
    `rating` DECIMAL(3, 2) DEFAULT 5.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_is_available` (`is_available`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create order_status_log table for tracking history
CREATE TABLE IF NOT EXISTS `order_status_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `changed_by` INT DEFAULT NULL,  -- user_id or courier_id
    `changed_by_type` ENUM('admin', 'system', 'courier', 'customer') DEFAULT 'system',
    `metadata` JSON DEFAULT NULL,   -- Extra data (location, photo_url, etc.)
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create cod_verifications table for OTP tracking
CREATE TABLE IF NOT EXISTS `cod_verifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `otp_code` VARCHAR(6) NOT NULL,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `is_trusted_user` BOOLEAN DEFAULT FALSE,  -- Auto-approve for trusted users
    `attempts` INT DEFAULT 0,
    `verified_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_otp_code` (`otp_code`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create order_locations table for real-time tracking
CREATE TABLE IF NOT EXISTS `order_locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL,
    `courier_id` INT DEFAULT NULL,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `accuracy` DECIMAL(6, 2) DEFAULT NULL,  -- meters
    `speed` DECIMAL(6, 2) DEFAULT NULL,     -- km/h
    `heading` DECIMAL(5, 2) DEFAULT NULL,   -- degrees
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_courier_id` (`courier_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Insert sample couriers
INSERT INTO `couriers` (`name`, `phone`, `email`, `vehicle_type`, `vehicle_number`, `current_location_lat`, `current_location_lng`, `is_available`) VALUES
('Budi Santoso', '08123456789', 'budi@dailycup.com', 'motorcycle', 'B 1234 XYZ', -6.200000, 106.816666, TRUE),
('Andi Wijaya', '08198765432', 'andi@dailycup.com', 'motorcycle', 'B 5678 ABC', -6.195000, 106.820000, TRUE),
('Siti Nurhaliza', '08567891234', 'siti@dailycup.com', 'car', 'B 9012 DEF', -6.205000, 106.810000, TRUE),
('Rudi Hartono', '08112233445', 'rudi@dailycup.com', 'motorcycle', 'B 3456 GHI', -6.198000, 106.815000, FALSE),
('Dewi Lestari', '08223344556', 'dewi@dailycup.com', 'bicycle', '-', -6.202000, 106.818000, TRUE);

-- 7. Update existing orders to new status format (if needed)
-- Map old statuses to new lifecycle statuses
UPDATE `orders` SET `status` = 'pending_payment' WHERE `status` = 'pending';
UPDATE `orders` SET `status` = 'waiting_confirmation' WHERE `status` = 'paid';
UPDATE `orders` SET `status` = 'preparing' WHERE `status` = 'processing';
UPDATE `orders` SET `status` = 'on_delivery' WHERE `status` = 'shipped';
UPDATE `orders` SET `status` = 'completed' WHERE `status` = 'delivered';
-- 'cancelled' and 'failed' map to 'cancelled'
UPDATE `orders` SET `status` = 'cancelled' WHERE `status` = 'failed';

-- 8. Add foreign key constraint for courier_id (optional, can be removed for compatibility)
-- ALTER TABLE `orders` 
-- ADD CONSTRAINT `fk_orders_courier` 
-- FOREIGN KEY (`courier_id`) REFERENCES `couriers`(`id`) ON DELETE SET NULL;

-- 9. Create view for order tracking summary
CREATE OR REPLACE VIEW `order_tracking_summary` AS
SELECT 
    o.id,
    o.order_id,
    o.customer_name,
    o.customer_phone,
    o.status,
    o.total,
    o.payment_method,
    c.name as courier_name,
    c.phone as courier_phone,
    c.vehicle_type,
    o.courier_photo,
    o.estimated_delivery,
    o.created_at,
    o.updated_at,
    (SELECT COUNT(*) FROM order_status_log WHERE order_id = o.order_id) as status_changes,
    (SELECT created_at FROM order_status_log WHERE order_id = o.order_id ORDER BY created_at DESC LIMIT 1) as last_status_change
FROM orders o
LEFT JOIN couriers c ON o.courier_id = c.id;

-- 10. Mark trusted users (users with > 5 completed orders)
-- This will be used for auto-approving COD orders
-- Run this periodically or trigger after order completion
UPDATE users u
SET u.loyalty_points = u.loyalty_points + 10
WHERE (
    SELECT COUNT(*) 
    FROM orders o 
    WHERE o.user_id = u.id 
    AND o.status = 'completed'
) >= 5;

-- ========================================
-- Migration Complete
-- ========================================
-- Next Steps:
-- 1. Run this SQL in phpMyAdmin or MySQL CLI
-- 2. Verify tables created: couriers, order_status_log, cod_verifications, order_locations
-- 3. Verify orders table updated with new status enum
-- 4. Check sample couriers inserted
-- ========================================
