-- Fix user_notifications table structure
-- Add missing columns: data, icon, action_url, order_id, read_at

USE dailycup_db;

-- Check and add 'data' column (JSON for storing extra notification data)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'dailycup_db' 
    AND TABLE_NAME = 'user_notifications' 
    AND COLUMN_NAME = 'data'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE user_notifications ADD COLUMN data JSON NULL AFTER message',
    'SELECT "Column data already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add 'icon' column (for notification icon type)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'dailycup_db' 
    AND TABLE_NAME = 'user_notifications' 
    AND COLUMN_NAME = 'icon'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE user_notifications ADD COLUMN icon VARCHAR(50) NOT NULL DEFAULT "bell" AFTER data',
    'SELECT "Column icon already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add 'action_url' column (for notification action link)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'dailycup_db' 
    AND TABLE_NAME = 'user_notifications' 
    AND COLUMN_NAME = 'action_url'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE user_notifications ADD COLUMN action_url VARCHAR(255) NULL AFTER icon',
    'SELECT "Column action_url already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add 'order_id' column (reference to orders table)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'dailycup_db' 
    AND TABLE_NAME = 'user_notifications' 
    AND COLUMN_NAME = 'order_id'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE user_notifications ADD COLUMN order_id INT NULL AFTER user_id',
    'SELECT "Column order_id already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add 'read_at' column (timestamp when notification was read)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = 'dailycup_db' 
    AND TABLE_NAME = 'user_notifications' 
    AND COLUMN_NAME = 'read_at'
);

SET @query = IF(@col_exists = 0,
    'ALTER TABLE user_notifications ADD COLUMN read_at DATETIME NULL AFTER is_read',
    'SELECT "Column read_at already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key for order_id if not exists
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = 'dailycup_db' 
    AND TABLE_NAME = 'user_notifications' 
    AND CONSTRAINT_NAME = 'fk_user_notif_order'
);

SET @query = IF(@fk_exists = 0,
    'ALTER TABLE user_notifications ADD CONSTRAINT fk_user_notif_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE',
    'SELECT "Foreign key fk_user_notif_order already exists" AS message'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show final structure
SELECT 'user_notifications table structure updated successfully' AS status;
DESCRIBE user_notifications;
