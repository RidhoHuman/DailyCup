-- Product Modifiers/Variants System
-- This supports customization options for F&B products (Sugar Level, Ice Level, etc.)

CREATE TABLE IF NOT EXISTS `product_modifiers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `modifier_type` VARCHAR(50) NOT NULL COMMENT 'Type of modifier: sugar_level, ice_level, milk_type, size, etc.',
    `modifier_name` VARCHAR(100) NOT NULL COMMENT 'Display name: Sugar Level, Ice Level, etc.',
    `is_required` TINYINT(1) DEFAULT 0 COMMENT 'Is this modifier required for the product?',
    `display_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_modifier_type` (`modifier_type`),
    
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `modifier_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `modifier_id` INT NOT NULL,
    `option_value` VARCHAR(50) NOT NULL COMMENT 'Value: 0%, 50%, 100%, Less, Normal, Extra',
    `option_label` VARCHAR(100) NOT NULL COMMENT 'Display label',
    `price_adjustment` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Additional price for this option',
    `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Is this the default selection?',
    `display_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_modifier_id` (`modifier_id`),
    
    FOREIGN KEY (`modifier_id`) REFERENCES `product_modifiers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default modifiers for coffee products
-- Sugar Level modifier
INSERT INTO `product_modifiers` (`product_id`, `modifier_type`, `modifier_name`, `is_required`, `display_order`) 
SELECT id, 'sugar_level', 'Sugar Level', 1, 1 
FROM products 
WHERE category_id IN (SELECT id FROM categories WHERE name LIKE '%Coffee%')
ON DUPLICATE KEY UPDATE modifier_name = modifier_name;

-- Ice Level modifier
INSERT INTO `product_modifiers` (`product_id`, `modifier_type`, `modifier_name`, `is_required`, `display_order`) 
SELECT id, 'ice_level', 'Ice Level', 1, 2 
FROM products 
WHERE category_id IN (SELECT id FROM categories WHERE name LIKE '%Coffee%')
ON DUPLICATE KEY UPDATE modifier_name = modifier_name;

-- Insert modifier options for Sugar Level
INSERT INTO `modifier_options` (`modifier_id`, `option_value`, `option_label`, `price_adjustment`, `is_default`, `display_order`)
SELECT 
    pm.id,
    '0%',
    'No Sugar (0%)',
    0.00,
    0,
    1
FROM product_modifiers pm
WHERE pm.modifier_type = 'sugar_level'
ON DUPLICATE KEY UPDATE option_label = option_label;

INSERT INTO `modifier_options` (`modifier_id`, `option_value`, `option_label`, `price_adjustment`, `is_default`, `display_order`)
SELECT 
    pm.id,
    '50%',
    'Half Sweet (50%)',
    0.00,
    1,
    2
FROM product_modifiers pm
WHERE pm.modifier_type = 'sugar_level'
ON DUPLICATE KEY UPDATE option_label = option_label;

INSERT INTO `modifier_options` (`modifier_id`, `option_value`, `option_label`, `price_adjustment`, `is_default`, `display_order`)
SELECT 
    pm.id,
    '100%',
    'Full Sweet (100%)',
    0.00,
    0,
    3
FROM product_modifiers pm
WHERE pm.modifier_type = 'sugar_level'
ON DUPLICATE KEY UPDATE option_label = option_label;

-- Insert modifier options for Ice Level
INSERT INTO `modifier_options` (`modifier_id`, `option_value`, `option_label`, `price_adjustment`, `is_default`, `display_order`)
SELECT 
    pm.id,
    'less',
    'Less Ice',
    0.00,
    0,
    1
FROM product_modifiers pm
WHERE pm.modifier_type = 'ice_level'
ON DUPLICATE KEY UPDATE option_label = option_label;

INSERT INTO `modifier_options` (`modifier_id`, `option_value`, `option_label`, `price_adjustment`, `is_default`, `display_order`)
SELECT 
    pm.id,
    'normal',
    'Normal Ice',
    0.00,
    1,
    2
FROM product_modifiers pm
WHERE pm.modifier_type = 'ice_level'
ON DUPLICATE KEY UPDATE option_label = option_label;

INSERT INTO `modifier_options` (`modifier_id`, `option_value`, `option_label`, `price_adjustment`, `is_default`, `display_order`)
SELECT 
    pm.id,
    'extra',
    'Extra Ice',
    0.00,
    0,
    3
FROM product_modifiers pm
WHERE pm.modifier_type = 'ice_level'
ON DUPLICATE KEY UPDATE option_label = option_label;
