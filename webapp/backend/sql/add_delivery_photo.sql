-- Migration: Add delivery photo confirmation system
-- Created: 2026-02-10
-- Description: Add photo proof of delivery feature for kurir

-- Add delivery_photo column to orders table (idempotent)
-- Add column only if it does not already exist
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'delivery_photo');
SET @s = IF(@cnt = 0, 'ALTER TABLE orders ADD COLUMN delivery_photo VARCHAR(500) NULL COMMENT ''Path to delivery confirmation photo'' AFTER delivery_time', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add index for faster queries (only if missing)
SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'idx_orders_delivery_photo');
SET @s = IF(@idx = 0, 'CREATE INDEX idx_orders_delivery_photo ON orders(delivery_photo)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Update existing completed orders (optional - set NULL explicitly)
-- UPDATE orders SET delivery_photo = NULL WHERE status = 'completed' AND delivery_photo IS NULL;
