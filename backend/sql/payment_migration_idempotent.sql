-- Idempotent Payment Migration for DailyCup
-- Safe to run multiple times (phpMyAdmin / MySQL CLI)
-- Usage: Select your database in phpMyAdmin and paste entire file into SQL tab, then Execute.

-- 1) Add payment_status enum column if it does not exist
SELECT COUNT(*) INTO @col_exists FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_status';
SET @sql = IF(@col_exists = 0,
  "ALTER TABLE `orders` ADD COLUMN `payment_status` ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER `status`",
  "SELECT 'skip_payment_status' as msg");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Add midtrans_response TEXT column if missing
SELECT COUNT(*) INTO @col_mid FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'midtrans_response';
SET @sql = IF(@col_mid = 0,
  "ALTER TABLE `orders` ADD COLUMN `midtrans_response` TEXT NULL AFTER `payment_status`",
  "SELECT 'skip_midtrans_response' as msg");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Add xendit_response TEXT column if missing
SELECT COUNT(*) INTO @col_xen FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'xendit_response';
SET @sql = IF(@col_xen = 0,
  "ALTER TABLE `orders` ADD COLUMN `xendit_response` TEXT NULL AFTER `midtrans_response`",
  "SELECT 'skip_xendit_response' as msg");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Add index idx_payment_status if missing
SELECT COUNT(*) INTO @idx_pay FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_payment_status';
SET @sql = IF(@idx_pay = 0,
  "ALTER TABLE `orders` ADD INDEX `idx_payment_status` (`payment_status`)",
  "SELECT 'skip_idx_payment_status' as msg");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Add index idx_order_number if missing (index on order_id or order_number depending on schema)
SELECT COUNT(*) INTO @idx_ord FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_order_number';
SET @sql = IF(@idx_ord = 0,
  "ALTER TABLE `orders` ADD INDEX `idx_order_number` (`order_id`)",
  "SELECT 'skip_idx_order_number' as msg");
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Notes:
-- - This script uses dynamic SQL to conditionally run ALTER statements. It is safe to run multiple times.
-- - If your orders table uses a different column name (e.g. `order_number`), edit the last ALTER to use the correct column.
-- - If you don't have permission to PREPARE/EXECUTE (rare on some shared hosts), run the checked SELECT queries first to see which operations are required, then run the corresponding ALTER statements manually.

-- Quick verification queries (run after migration):
-- SHOW COLUMNS FROM `orders` LIKE 'payment_status';
-- SHOW INDEX FROM `orders` WHERE Key_name IN ('idx_payment_status','idx_order_number');

-- End of migration
