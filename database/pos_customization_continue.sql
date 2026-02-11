-- ============================================
-- POS CUSTOMIZATION - FINAL MIGRATION
-- Just create tables and insert data
-- Date: 2026-02-07
-- ============================================

-- Select database
USE dailycup_db;

-- ============================================
-- Create product_addons table
-- ============================================
DROP TABLE IF EXISTS product_addons;
CREATE TABLE product_addons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert add-ons
INSERT INTO product_addons (name, code, price, category) VALUES
    ('Extra Espresso Shot', 'extra_espresso', 8000.00, 'shot'),
    ('Extra Sugar', 'extra_sugar', 2000.00, 'sweetener'),
    ('Extra Milk', 'extra_milk', 5000.00, 'dairy'),
    ('Extra Whipped Cream', 'extra_whipped_cream', 7000.00, 'topping');

-- ============================================
-- Create product_sizes table
-- ============================================
DROP TABLE IF EXISTS product_sizes;
CREATE TABLE product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sizes
INSERT INTO product_sizes (code, name, price_modifier, display_order) VALUES
    ('S', 'Small', 0.00, 1),
    ('M', 'Medium', 5000.00, 2),
    ('L', 'Large', 10000.00, 3);

-- ============================================
-- DONE! Tables created successfully
-- Now test the POS system at:
-- http://localhost:3000/admin/orders/create
-- ============================================
