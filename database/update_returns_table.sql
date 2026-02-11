-- Update returns table for enhanced refund system
-- Run this SQL to add new columns

USE dailycup_db;

-- Add new columns if they don't exist
ALTER TABLE returns 
ADD COLUMN IF NOT EXISTS refund_method ENUM('loyalty_points', 'bank_transfer') DEFAULT 'loyalty_points' AFTER refund_amount,
ADD COLUMN IF NOT EXISTS bank_account_name VARCHAR(255) AFTER refund_method,
ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(100) AFTER bank_account_name,
ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) AFTER bank_account_number,
ADD COLUMN IF NOT EXISTS auto_approved TINYINT(1) DEFAULT 0 AFTER bank_name,
ADD COLUMN IF NOT EXISTS refund_processed TINYINT(1) DEFAULT 0 AFTER auto_approved;

-- Update proof_images to allow multiple images (JSON array)
ALTER TABLE returns MODIFY proof_images TEXT;

-- Add index for faster queries
ALTER TABLE returns ADD INDEX IF NOT EXISTS idx_created_at (created_at);
ALTER TABLE returns ADD INDEX IF NOT EXISTS idx_refund_method (refund_method);

-- Add constraint for 3-day refund window (handled in application logic, but good to document)
-- Customer can only request refund within 3 days after order completed
