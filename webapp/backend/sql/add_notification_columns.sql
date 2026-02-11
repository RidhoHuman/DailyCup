USE dailycup_db;
ALTER TABLE user_notifications ADD COLUMN data JSON NULL;
ALTER TABLE user_notifications ADD COLUMN icon VARCHAR(50) NOT NULL DEFAULT 'bell';
ALTER TABLE user_notifications ADD COLUMN action_url VARCHAR(255) NULL;
ALTER TABLE user_notifications ADD COLUMN order_id INT NULL AFTER user_id;
ALTER TABLE user_notifications ADD COLUMN read_at DATETIME NULL AFTER is_read;
