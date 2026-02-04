-- ============================================
-- Happy Hour System Database Schema
-- ============================================
-- Purpose: Enable time-based promotional discounts with admin management
-- Features: Custom schedules, product selection, analytics tracking
-- Author: DailyCup Development Team
-- Date: 2026-02-04

USE dailycup_db;

-- ============================================
-- Table 1: happy_hour_schedules
-- ============================================
-- Stores Happy Hour schedule configurations
CREATE TABLE IF NOT EXISTS happy_hour_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT 'Display name (e.g., "Morning Rush", "Afternoon Break")',
    start_time TIME NOT NULL COMMENT 'Start time (e.g., 07:00:00)',
    end_time TIME NOT NULL COMMENT 'End time (e.g., 09:00:00)',
    days_of_week JSON NOT NULL COMMENT 'Array of active days: ["monday", "tuesday", ...]',
    discount_percentage DECIMAL(5,2) NOT NULL COMMENT 'Discount percentage (e.g., 20.00 for 20%)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = active, 0 = paused',
    created_by INT NULL COMMENT 'Admin user ID who created this schedule',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    CHECK (discount_percentage > 0 AND discount_percentage <= 100),
    CHECK (start_time < end_time),
    
    -- Indexes
    INDEX idx_active (is_active),
    INDEX idx_time_range (start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Happy Hour promotional schedules managed by admin';

-- ============================================
-- Table 2: happy_hour_products
-- ============================================
-- Many-to-many relationship: schedules <-> products
CREATE TABLE IF NOT EXISTS happy_hour_products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    happy_hour_id INT NOT NULL COMMENT 'Reference to happy_hour_schedules',
    product_id INT NOT NULL COMMENT 'Reference to products',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (happy_hour_id) REFERENCES happy_hour_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Prevent duplicate assignments
    UNIQUE KEY unique_hour_product (happy_hour_id, product_id),
    
    -- Indexes for fast lookups
    INDEX idx_happy_hour (happy_hour_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Products assigned to Happy Hour schedules';

-- ============================================
-- Table 3: happy_hour_analytics
-- ============================================
-- Track every transaction during Happy Hour for CRM analytics
CREATE TABLE IF NOT EXISTS happy_hour_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    happy_hour_id INT NOT NULL COMMENT 'Which Happy Hour schedule was applied',
    product_id INT NOT NULL COMMENT 'Product purchased',
    order_id INT NOT NULL COMMENT 'Reference to orders table',
    order_item_id INT NULL COMMENT 'Reference to order_items table',
    user_id INT NOT NULL COMMENT 'Customer who made purchase',
    
    -- Pricing breakdown for analytics
    original_price DECIMAL(10,2) NOT NULL COMMENT 'Price before discount',
    discount_percentage DECIMAL(5,2) NOT NULL COMMENT 'Discount applied',
    discount_amount DECIMAL(10,2) NOT NULL COMMENT 'Actual discount in Rupiah',
    final_price DECIMAL(10,2) NOT NULL COMMENT 'Price after discount',
    quantity INT DEFAULT 1 COMMENT 'Number of items purchased',
    
    -- Timestamps for reporting
    order_date DATETIME NOT NULL COMMENT 'When the order was placed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (happy_hour_id) REFERENCES happy_hour_schedules(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for fast analytics queries
    INDEX idx_date (order_date),
    INDEX idx_happy_hour (happy_hour_id),
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_date_range (order_date, happy_hour_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Transaction analytics for Happy Hour promotions';

-- ============================================
-- Sample Data for Testing
-- ============================================
-- Clean existing sample data to avoid duplicates
DELETE FROM happy_hour_products WHERE happy_hour_id IN (1, 2, 3);
DELETE FROM happy_hour_schedules WHERE id IN (1, 2, 3);

-- Insert default Happy Hour schedules
INSERT INTO happy_hour_schedules 
(name, start_time, end_time, days_of_week, discount_percentage, is_active) 
VALUES 
(
    'Morning Rush', 
    '07:00:00', 
    '09:00:00', 
    JSON_ARRAY('monday', 'tuesday', 'wednesday', 'thursday', 'friday'),
    15.00,
    1
),
(
    'Afternoon Break', 
    '14:00:00', 
    '16:00:00', 
    JSON_ARRAY('monday', 'tuesday', 'wednesday', 'thursday', 'friday'),
    20.00,
    1
),
(
    'Weekend Special', 
    '10:00:00', 
    '12:00:00', 
    JSON_ARRAY('saturday', 'sunday'),
    25.00,
    1
);

-- Assign products to Morning Rush (first 3 coffee products)
INSERT IGNORE INTO happy_hour_products (happy_hour_id, product_id)
SELECT 1, id FROM products WHERE category_id IN (SELECT id FROM categories WHERE name LIKE '%Coffee%') LIMIT 3;

-- Assign products to Afternoon Break (different coffee products)
INSERT IGNORE INTO happy_hour_products (happy_hour_id, product_id)
SELECT 2, id FROM products WHERE category_id IN (SELECT id FROM categories WHERE name LIKE '%Coffee%') LIMIT 2 OFFSET 1;

-- ============================================
-- Verification Queries
-- ============================================
SELECT 'Happy Hour System tables created successfully!' AS status;

-- Show created schedules
SELECT 
    id,
    name,
    start_time,
    end_time,
    discount_percentage,
    is_active,
    (SELECT COUNT(*) FROM happy_hour_products WHERE happy_hour_id = happy_hour_schedules.id) AS product_count
FROM happy_hour_schedules
ORDER BY id;
