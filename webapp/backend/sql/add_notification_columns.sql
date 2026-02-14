-- Add notification-related columns only if missing (idempotent)
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user_notifications' AND column_name = 'data');
SET @s = IF(@cnt = 0, 'ALTER TABLE user_notifications ADD COLUMN data JSON NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user_notifications' AND column_name = 'icon');
SET @s = IF(@cnt = 0, 'ALTER TABLE user_notifications ADD COLUMN icon VARCHAR(50) NOT NULL DEFAULT ''bell''', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user_notifications' AND column_name = 'action_url');
SET @s = IF(@cnt = 0, 'ALTER TABLE user_notifications ADD COLUMN action_url VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user_notifications' AND column_name = 'order_id');
SET @s = IF(@cnt = 0, 'ALTER TABLE user_notifications ADD COLUMN order_id INT NULL AFTER user_id', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'user_notifications' AND column_name = 'read_at');
SET @s = IF(@cnt = 0, 'ALTER TABLE user_notifications ADD COLUMN read_at DATETIME NULL AFTER is_read', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
