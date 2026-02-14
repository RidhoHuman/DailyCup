-- Add accuracy and speed columns to kurir_location table for better tracking
-- Run this script to enable real-time location broadcasting with metadata

-- Add accuracy and speed columns to kurir_location (idempotent)
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'kurir_location' AND column_name = 'accuracy');
SET @s = IF(@cnt = 0, 'ALTER TABLE kurir_location ADD COLUMN accuracy FLOAT NULL COMMENT ''GPS accuracy in meters''', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'kurir_location' AND column_name = 'speed');
SET @s = IF(@cnt = 0, 'ALTER TABLE kurir_location ADD COLUMN speed FLOAT NULL COMMENT ''Speed in meters per second''', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add index for faster queries on updated_at if missing
SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'kurir_location' AND index_name = 'idx_updated_at');
SET @s = IF(@idx = 0, 'ALTER TABLE kurir_location ADD INDEX idx_updated_at (updated_at)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verify changes (harmless)
DESCRIBE kurir_location;
