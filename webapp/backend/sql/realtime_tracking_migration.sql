-- Add accuracy and speed columns to kurir_location table for better tracking
-- Run this script to enable real-time location broadcasting with metadata

-- Check if columns exist and add them if missing
ALTER TABLE kurir_location 
ADD COLUMN IF NOT EXISTS accuracy FLOAT NULL COMMENT 'GPS accuracy in meters',
ADD COLUMN IF NOT EXISTS speed FLOAT NULL COMMENT 'Speed in meters per second';

-- Add index for faster queries on updated_at
ALTER TABLE kurir_location 
ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);

-- Verify changes
DESCRIBE kurir_location;
