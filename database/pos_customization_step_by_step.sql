-- ============================================
-- POS CUSTOMIZATION - STEP BY STEP MIGRATION
-- Run each section separately if you encounter errors
-- Date: 2026-02-07
-- ============================================

-- ===== STEP 1: Add columns to order_items =====
-- Copy and run this section first
USE dailycup_db;

ALTER TABLE order_items ADD COLUMN size VARCHAR(10) DEFAULT NULL COMMENT 'S, M, L';
ALTER TABLE order_items ADD COLUMN temperature VARCHAR(10) DEFAULT NULL COMMENT 'hot, ice';
ALTER TABLE order_items ADD COLUMN addons JSON DEFAULT NULL COMMENT 'Array of addon objects';
ALTER TABLE order_items ADD COLUMN custom_notes TEXT DEFAULT NULL COMMENT 'Customer notes for kitchen/bar';
ALTER TABLE order_items ADD COLUMN base_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Product base price';
ALTER TABLE order_items ADD COLUMN size_price_modifier DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Size price adjustment';
ALTER TABLE order_items ADD COLUMN addons_total DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total add-ons price';

-- ===== STEP 2: Create indexes =====
-- Copy and run this section after Step 1
CREATE INDEX idx_order_items_size ON order_items(size);
CREATE INDEX idx_order_items_temperature ON order_items(temperature);

-- ===== STEP 3: Create product_addons table =====
-- Copy and run this section after Step 2
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

-- ===== STEP 4: Insert add-ons data =====
-- Copy and run this section after Step 3
INSERT INTO product_addons (name, code, price, category) 
VALUES
    ('Extra Espresso Shot', 'extra_espresso', 8000.00, 'shot'),
    ('Extra Sugar', 'extra_sugar', 2000.00, 'sweetener'),
    ('Extra Milk', 'extra_milk', 5000.00, 'dairy'),
    ('Extra Whipped Cream', 'extra_whipped_cream', 7000.00, 'topping');

-- ===== STEP 5: Create product_sizes table =====
-- Copy and run this section after Step 4
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE COMMENT 'S, M, L',
    name VARCHAR(50) NOT NULL COMMENT 'Small, Medium, Large',
    price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Additional price',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===== STEP 6: Insert sizes data =====
-- Copy and run this section after Step 5
INSERT INTO product_sizes (code, name, price_modifier, display_order) 
VALUES
    ('S', 'Small', 0.00, 1),
    ('M', 'Medium', 5000.00, 2),
    ('L', 'Large', 10000.00, 3);

-- ===== STEP 7: Update existing orders (Optional) =====
-- This gives default values to old orders that don't have customization
-- Copy and run this section after Step 6 (optional)
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

-- ===== VERIFICATION QUERIES =====
-- Run these to verify migration succeeded:

-- Check if columns were added:
DESCRIBE order_items;

-- Check add-ons table:
SELECT * FROM product_addons;

-- Check sizes table:
SELECT * FROM product_sizes;

-- ============================================
-- MIGRATION COMPLETE!
-- You can now test the POS customization system
-- ============================================
