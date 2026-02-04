-- Loyalty Points System for CRM
-- Tracks customer points earned and redeemed

-- Add loyalty_points field to users table
-- Note: If columns already exist, you can ignore the error or run each ALTER separately
ALTER TABLE `users` 
ADD COLUMN `loyalty_points` INT DEFAULT 0 COMMENT 'Current loyalty points balance',
ADD COLUMN `total_points_earned` INT DEFAULT 0 COMMENT 'Lifetime points earned',
ADD COLUMN `total_points_redeemed` INT DEFAULT 0 COMMENT 'Lifetime points redeemed',
ADD INDEX `idx_loyalty_points` (`loyalty_points`);

-- Loyalty Points Transaction History
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `order_id` INT NULL COMMENT 'Related order if applicable',
    `transaction_type` ENUM('earn', 'redeem', 'expire', 'adjust') NOT NULL,
    `points` INT NOT NULL COMMENT 'Points amount (positive for earn, negative for redeem)',
    `balance_before` INT NOT NULL,
    `balance_after` INT NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_transaction_type` (`transaction_type`),
    INDEX `idx_created_at` (`created_at`),
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add discount_from_points to orders table
-- Note: If columns already exist, you can ignore the error or run each ALTER separately
ALTER TABLE `orders`
ADD COLUMN `points_used` INT DEFAULT 0 COMMENT 'Points redeemed for this order',
ADD COLUMN `points_earned` INT DEFAULT 0 COMMENT 'Points earned from this order',
ADD COLUMN `discount_from_points` DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Discount amount from points redemption';

-- Loyalty Point Rules (for configuration)
CREATE TABLE IF NOT EXISTS `loyalty_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `rule_name` VARCHAR(100) NOT NULL,
    `rule_type` VARCHAR(50) NOT NULL COMMENT 'earn_rate, redeem_rate, min_points_redeem',
    `rule_value` DECIMAL(10,2) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX `idx_rule_type` (`rule_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default loyalty rules
INSERT INTO `loyalty_rules` (`rule_name`, `rule_type`, `rule_value`, `is_active`) VALUES
('Points Earn Rate', 'earn_rate', 10000.00, 1), -- 1 point per Rp 10,000 spent
('Points Redemption Value', 'redeem_rate', 500.00, 1), -- 1 point = Rp 500 discount
('Minimum Points to Redeem', 'min_points_redeem', 10.00, 1) -- Minimum 10 points to redeem
ON DUPLICATE KEY UPDATE rule_value = VALUES(rule_value);
