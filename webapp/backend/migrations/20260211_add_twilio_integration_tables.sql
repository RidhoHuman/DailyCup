-- Migration: Add Twilio integration tables

-- Table: integration_settings
CREATE TABLE IF NOT EXISTS integration_settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  `key` VARCHAR(128) NOT NULL UNIQUE,
  `value` TEXT NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: integration_messages (log outbound/inbound messages)
CREATE TABLE IF NOT EXISTS integration_messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  provider VARCHAR(50) NOT NULL,
  channel VARCHAR(50) NOT NULL, -- sms, whatsapp
  direction ENUM('outbound','inbound') NOT NULL,
  to_number VARCHAR(64) NOT NULL,
  from_number VARCHAR(64) NOT NULL,
  body TEXT,
  provider_payload JSON NULL,
  status VARCHAR(50) DEFAULT 'queued',
  error_message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_provider_channel (provider, channel),
  INDEX idx_to_number (to_number)
);
