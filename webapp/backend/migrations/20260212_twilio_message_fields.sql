-- Migration: Add Twilio message tracking fields and retry support

ALTER TABLE integration_messages
  ADD COLUMN provider_message_sid VARCHAR(128) DEFAULT NULL,
  ADD COLUMN retry_count INT DEFAULT 0,
  ADD COLUMN max_retries INT DEFAULT 3,
  ADD COLUMN last_attempt_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN next_retry_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN metadata JSON NULL,
  ADD INDEX idx_status_retry (status, retry_count),
  ADD INDEX idx_provider_sid (provider_message_sid);
