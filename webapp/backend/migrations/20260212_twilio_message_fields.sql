-- Migration: Add Twilio message tracking fields and retry support (safe for older MySQL)
-- This version avoids `ADD COLUMN IF NOT EXISTS` (not supported on older MySQL)
-- It uses information_schema checks + prepared statements so the migration is idempotent.

-- provider_message_sid
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND column_name = 'provider_message_sid');
SET @s = IF(@cnt = 0, 'ALTER TABLE integration_messages ADD COLUMN provider_message_sid VARCHAR(128) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- retry_count
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND column_name = 'retry_count');
SET @s = IF(@cnt = 0, 'ALTER TABLE integration_messages ADD COLUMN retry_count INT DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- max_retries
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND column_name = 'max_retries');
SET @s = IF(@cnt = 0, 'ALTER TABLE integration_messages ADD COLUMN max_retries INT DEFAULT 3', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- last_attempt_at
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND column_name = 'last_attempt_at');
SET @s = IF(@cnt = 0, 'ALTER TABLE integration_messages ADD COLUMN last_attempt_at TIMESTAMP NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- next_retry_at
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND column_name = 'next_retry_at');
SET @s = IF(@cnt = 0, 'ALTER TABLE integration_messages ADD COLUMN next_retry_at TIMESTAMP NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- metadata (JSON)
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND column_name = 'metadata');
SET @s = IF(@cnt = 0, 'ALTER TABLE integration_messages ADD COLUMN metadata JSON NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Indexes: check information_schema.statistics
SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND index_name = 'idx_status_retry');
SET @s = IF(@idx = 0, 'ALTER TABLE integration_messages ADD INDEX idx_status_retry (status, retry_count)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'integration_messages' AND index_name = 'idx_provider_sid');
SET @s = IF(@idx = 0, 'ALTER TABLE integration_messages ADD INDEX idx_provider_sid (provider_message_sid)', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
