-- Fix categories table schema to match API requirements
-- Add missing columns: image, is_active, display_order

-- Add missing columns (idempotent checks)
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'image');
SET @s = IF(@cnt = 0, 'ALTER TABLE categories ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER description', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'is_active');
SET @s = IF(@cnt = 0, 'ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER image', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'display_order');
SET @s = IF(@cnt = 0, 'ALTER TABLE categories ADD COLUMN display_order INT DEFAULT 0 AFTER is_active', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Update existing rows with default values (safe to run)
UPDATE categories SET is_active = 1 WHERE COALESCE(is_active, 1) IS NULL;
UPDATE categories SET display_order = id WHERE COALESCE(display_order, 0) = 0;

-- Create index for better performance (if missing)
SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'categories' AND index_name = 'idx_active_order');
SET @s = IF(@idx = 0, 'CREATE INDEX idx_active_order ON categories(is_active, display_order)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
