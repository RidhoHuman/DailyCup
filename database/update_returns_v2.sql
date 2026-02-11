-- Update returns table for enhanced refund system
USE dailycup_db;

-- Add new columns (MySQL doesn't support IF NOT EXISTS in ALTER COLUMN)
-- Will show warnings if columns already exist, but won't fail

ALTER TABLE returns ADD COLUMN refund_method ENUM('loyalty_points', 'bank_transfer') DEFAULT 'loyalty_points' AFTER refund_amount;
ALTER TABLE returns ADD COLUMN bank_account_name VARCHAR(255) AFTER refund_method;
ALTER TABLE returns ADD COLUMN bank_account_number VARCHAR(100) AFTER bank_account_name;
ALTER TABLE returns ADD COLUMN bank_name VARCHAR(100) AFTER bank_account_number;
ALTER TABLE returns ADD COLUMN auto_approved TINYINT(1) DEFAULT 0 AFTER bank_name;
ALTER TABLE returns ADD COLUMN refund_processed TINYINT(1) DEFAULT 0 AFTER auto_approved;

-- Update proof_images to allow multiple images (JSON array)
ALTER TABLE returns MODIFY proof_images TEXT;
