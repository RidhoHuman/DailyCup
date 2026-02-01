-- Fix categories table schema to match API requirements
-- Add missing columns: image, is_active, display_order

ALTER TABLE categories 
ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL AFTER description,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER image,
ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0 AFTER is_active;

-- Update existing rows with default values
UPDATE categories SET is_active = 1 WHERE is_active IS NULL;
UPDATE categories SET display_order = id WHERE display_order = 0 OR display_order IS NULL;

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_active_order ON categories(is_active, display_order);
