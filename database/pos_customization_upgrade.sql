-- ============================================
-- POS CUSTOMIZATION SYSTEM UPGRADE
-- Database Migration for Order Items Enhancement
-- Date: 2026-02-07
-- ============================================

USE dailycup_db;

-- ============================================
-- IMPORTANT: If you get "Duplicate column" errors,
-- the columns already exist. This is NORMAL.
-- Just skip to the next section (product_addons table)
-- ============================================

-- Check first if columns already exist:
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'dailycup_db' 
  AND TABLE_NAME = 'order_items' 
  AND COLUMN_NAME IN ('size', 'temperature', 'addons', 'custom_notes', 'base_price', 'size_price_modifier', 'addons_total');

-- If the query above returns empty (no results), run the ALTER TABLE below.
-- If it returns column names, SKIP the ALTER TABLE and go to product_addons section.

-- Add new columns to order_items table (SKIP if error "Duplicate column"):
-- ALTER TABLE order_items 
--     ADD COLUMN size VARCHAR(10) DEFAULT NULL COMMENT 'S, M, L',
--     ADD COLUMN temperature VARCHAR(10) DEFAULT NULL COMMENT 'hot, ice',
--     ADD COLUMN addons JSON DEFAULT NULL COMMENT 'Array of addon objects',
--     ADD COLUMN custom_notes TEXT DEFAULT NULL COMMENT 'Customer notes for kitchen/bar',
--     ADD COLUMN base_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Product base price',
--     ADD COLUMN size_price_modifier DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Size price adjustment',
--     ADD COLUMN addons_total DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total add-ons price';

-- ============================================
-- ADDON CONFIGURATION TABLE (Optional)
-- Store available add-ons with prices
-- ============================================
CREATE TABLE IF NOT EXISTS product_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'e.g., Extra Espresso Shot',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., extra_espresso',
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(50) DEFAULT NULL COMMENT 'e.g., shot, topping, sweetener',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default add-ons
INSERT INTO product_addons (name, code, price, category) VALUES
    ('Extra Espresso Shot', 'extra_espresso', 8000.00, 'shot'),
    ('Extra Sugar', 'extra_sugar', 2000.00, 'sweetener'),
    ('Extra Milk', 'extra_milk', 5000.00, 'dairy'),
    ('Extra Whipped Cream', 'extra_whipped_cream', 7000.00, 'topping'),
    ('Less Sugar', 'less_sugar', 0.00, 'sweetener'),
    ('No Sugar', 'no_sugar', 0.00, 'sweetener')
AS new_values
ON DUPLICATE KEY UPDATE 
    name = new_values.name,
    price = new_values.price,
    category = new_values.category;

-- ============================================
-- SIZE PRICE MODIFIERS (Optional)
-- Store size price adjustments
-- ============================================
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE COMMENT 'S, M, L',
    name VARCHAR(50) NOT NULL COMMENT 'Small, Medium, Large',
    price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Additional price',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default sizes
INSERT INTO product_sizes (code, name, price_modifier, display_order) VALUES
    ('S', 'Small', 0.00, 1),
    ('M', 'Medium', 5000.00, 2),
    ('L', 'Large', 10000.00, 3)
AS new_values
ON DUPLICATE KEY UPDATE 
    name = new_values.name,
    price_modifier = new_values.price_modifier,
    display_order = new_values.display_order;

-- ============================================
-- TEST DATA: Update existing order_items with default values
-- (Only for orders that don't have customization yet)
-- ============================================
UPDATE order_items 
SET 
    size = 'M',
    temperature = 'hot',
    base_price = unit_price,
    size_price_modifier = 0.00,
    addons_total = 0.00
WHERE 
    size IS NULL 
    AND created_at < NOW();

-- ============================================
-- VIEW: Order Items with Full Details
-- ============================================
CREATE OR REPLACE VIEW v_order_items_full AS
SELECT 
    oi.id,
    oi.order_id,
    oi.product_id,
    oi.product_name,
    oi.quantity,
    oi.unit_price,
    oi.subtotal,
    oi.size,
    oi.temperature,
    oi.addons,
    oi.custom_notes,
    oi.base_price,
    oi.size_price_modifier,
    oi.addons_total,
    ps.name as size_name,
    -- Calculate actual item price
    (COALESCE(oi.base_price, oi.unit_price) + 
     COALESCE(oi.size_price_modifier, 0) + 
     COALESCE(oi.addons_total, 0)) as calculated_unit_price,
    -- Verify subtotal matches
    ((COALESCE(oi.base_price, oi.unit_price) + 
      COALESCE(oi.size_price_modifier, 0) + 
      COALESCE(oi.addons_total, 0)) * oi.quantity) as calculated_subtotal,
    o.order_number,
    o.status as order_status
FROM order_items oi
LEFT JOIN product_sizes ps ON oi.size = ps.code
LEFT JOIN orders o ON oi.order_id = o.id;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================
-- Verification Query:
-- SELECT * FROM order_items LIMIT 5;
-- SELECT * FROM product_addons;
-- SELECT * FROM product_sizes;
-- SELECT * FROM v_order_items_full LIMIT 10;
