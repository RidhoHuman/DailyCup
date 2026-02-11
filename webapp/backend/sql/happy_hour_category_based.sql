-- ============================================
-- Happy Hour System - Category-Based Update
-- ============================================
-- Purpose: Enable Happy Hour to apply automatically to all products in a category
-- Use Case: Admin selects "Coffee" category → All coffee products get discount
-- Benefit: No need to manually select products one-by-one

USE dailycup_db;

-- Add category-based column to happy_hour_schedules (only if not exists)
SET @dbname = DATABASE();
SET @tablename = 'happy_hour_schedules';
SET @columnname = 'apply_to_category';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE 
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT ''Column already exists'' AS status;',
  'ALTER TABLE happy_hour_schedules ADD COLUMN apply_to_category VARCHAR(50) NULL COMMENT ''If set, apply discount to all products in this category (e.g., "Coffee")'' AFTER discount_percentage;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index for faster category lookups (only if not exists)
SET @indexname = 'idx_category';
SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE 
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND INDEX_NAME = @indexname
  ) > 0,
  'SELECT ''Index already exists'' AS status;',
  'ALTER TABLE happy_hour_schedules ADD INDEX idx_category (apply_to_category);'
));
PREPARE indexIfNotExists FROM @preparedStatement2;
EXECUTE indexIfNotExists;
DEALLOCATE PREPARE indexIfNotExists;

-- Update existing schedules to use Coffee category instead of manual selection
-- Get the Coffee category ID first
SET @coffee_category_id = (SELECT id FROM categories WHERE name LIKE '%Coffee%' OR name LIKE '%Kopi%' LIMIT 1);

-- Update Morning Rush to apply to Coffee category
UPDATE happy_hour_schedules 
SET apply_to_category = 'Coffee' 
WHERE name = 'Morning Rush';

-- Update Afternoon Break to apply to Coffee category
UPDATE happy_hour_schedules 
SET apply_to_category = 'Coffee' 
WHERE name = 'Afternoon Break';

-- Weekend Special remains Coffee category
UPDATE happy_hour_schedules 
SET apply_to_category = 'Coffee' 
WHERE name = 'Weekend Special';

-- Show updated structure
SELECT 'Happy Hour schedules updated to category-based system!' AS status;

-- Verify the update
SELECT 
    id,
    name,
    apply_to_category,
    discount_percentage,
    start_time,
    end_time,
    is_active
FROM happy_hour_schedules;

-- Show which categories are available
SELECT 
    id,
    name,
    (SELECT COUNT(*) FROM products WHERE category_id = categories.id) AS product_count
FROM categories
ORDER BY name;
