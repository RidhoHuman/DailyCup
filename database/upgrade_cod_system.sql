-- ================================================
-- COD PAYMENT SYSTEM & TRUST SCORE UPGRADE
-- DailyCup WebApp Enhancement
-- ================================================

USE dailycup_db;

-- ================================================
-- 1. UPGRADE USERS TABLE - Add Trust Score System
-- ================================================

-- Add trust score and COD management columns (ignore if already exists)
ALTER TABLE users 
ADD COLUMN trust_score INT DEFAULT 0 COMMENT 'User reputation score (0-100)',
ADD COLUMN total_successful_orders INT DEFAULT 0 COMMENT 'Count of completed orders',
ADD COLUMN cod_enabled TINYINT(1) DEFAULT 0 COMMENT 'COD permission flag',
ADD COLUMN cod_blacklisted TINYINT(1) DEFAULT 0 COMMENT 'COD ban flag',
ADD COLUMN blacklist_reason TEXT NULL COMMENT 'Reason for COD ban',
ADD COLUMN blacklist_date DATETIME NULL COMMENT 'When user was blacklisted',
ADD COLUMN is_verified_user TINYINT(1) DEFAULT 0 COMMENT 'Verified user flag';

-- Add indexes for performance (ignore if already exists)
ALTER TABLE users ADD INDEX idx_trust_score (trust_score);
ALTER TABLE users ADD INDEX idx_cod_status (cod_enabled, cod_blacklisted);

-- ================================================
-- 2. UPGRADE ORDERS TABLE - Add COD Features
-- ================================================

-- Add order expiry and distance tracking (ignore if already exists)
ALTER TABLE orders 
ADD COLUMN expires_at DATETIME NULL COMMENT 'Order expiry time (60 min)',
ADD COLUMN delivery_distance DECIMAL(5,2) NULL COMMENT 'Distance in KM',
ADD COLUMN cod_amount_limit DECIMAL(10,2) NULL COMMENT 'COD limit at order time',
ADD COLUMN admin_confirmed_at DATETIME NULL COMMENT 'Admin COD confirmation time',
ADD COLUMN admin_confirmed_by INT NULL COMMENT 'Admin user ID who confirmed',
ADD COLUMN cancellation_reason TEXT NULL COMMENT 'Why order was cancelled';

-- Add foreign key for admin confirmer (ignore if already exists)
ALTER TABLE orders 
ADD CONSTRAINT fk_orders_admin_confirmer 
FOREIGN KEY (admin_confirmed_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add index for expiry checks (ignore if already exists)
ALTER TABLE orders 
ADD INDEX idx_expires_at (expires_at, payment_status, status);

-- ================================================
-- 3. DELIVERY_HISTORY TABLE - Add Photo Proof
-- ================================================

ALTER TABLE delivery_history 
ADD COLUMN photo_proof VARCHAR(255) NULL COMMENT 'Delivered proof photo',
ADD COLUMN delivery_signature TEXT NULL COMMENT 'Customer signature (base64)';

-- ================================================
-- 4. CREATE ORDER_STATUS_LOGS TABLE
-- ================================================

CREATE TABLE IF NOT EXISTS order_status_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NOT NULL,
    changed_by_type ENUM('system', 'admin', 'kurir', 'customer') DEFAULT 'system',
    changed_by_id INT NULL,
    reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_timestamp (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- 5. CREATE COD_VALIDATION_RULES TABLE
-- ================================================

CREATE TABLE IF NOT EXISTS cod_validation_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL UNIQUE,
    rule_type ENUM('amount', 'distance', 'trust_score', 'order_count') NOT NULL,
    rule_value DECIMAL(10,2) NOT NULL,
    rule_operator ENUM('lt', 'lte', 'gt', 'gte', 'eq') DEFAULT 'lte',
    is_active TINYINT(1) DEFAULT 1,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_type (rule_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default COD rules
INSERT INTO cod_validation_rules (rule_name, rule_type, rule_value, rule_operator, description) VALUES
('max_cod_amount_new_user', 'amount', 50000.00, 'lte', 'New users: max COD Rp 50.000'),
('max_cod_amount_verified', 'amount', 100000.00, 'lte', 'Verified users: max COD Rp 100.000'),
('max_delivery_distance', 'distance', 5.00, 'lte', 'Maximum delivery distance: 5 KM'),
('min_trust_score_cod', 'trust_score', 20.00, 'gte', 'Minimum trust score for COD'),
('min_orders_for_verified', 'order_count', 1.00, 'gte', 'Minimum successful orders to be verified')
ON DUPLICATE KEY UPDATE rule_value=VALUES(rule_value);

-- ================================================
-- 6. CREATE USER_FRAUD_LOGS TABLE
-- ================================================

CREATE TABLE IF NOT EXISTS user_fraud_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT NULL,
    fraud_type ENUM('cod_reject', 'fake_order', 'payment_fraud', 'address_fraud', 'other') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NULL,
    admin_action ENUM('none', 'warning', 'cod_ban', 'account_suspend') DEFAULT 'none',
    admin_notes TEXT NULL,
    reported_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_type (fraud_type),
    INDEX idx_severity (severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- 7. UPDATE EXISTING USERS - Initialize Trust Score
-- ================================================

-- Set trust score based on existing successful orders
UPDATE users u
SET 
    trust_score = LEAST(100, (
        SELECT COUNT(*) * 10 
        FROM orders o 
        WHERE o.user_id = u.id 
        AND o.status = 'completed' 
        AND o.payment_status = 'paid'
    )),
    total_successful_orders = (
        SELECT COUNT(*) 
        FROM orders o 
        WHERE o.user_id = u.id 
        AND o.status = 'completed' 
        AND o.payment_status = 'paid'
    );

-- Enable COD for verified users (completed at least 1 order)
UPDATE users 
SET 
    cod_enabled = 1,
    is_verified_user = 1
WHERE total_successful_orders >= 1 
AND cod_blacklisted = 0;

-- ================================================
-- 8. CREATE TRIGGERS FOR AUTO TRUST SCORE UPDATE
-- ================================================

DELIMITER $$

-- Trigger: Update trust score when order completed
DROP TRIGGER IF EXISTS after_order_complete$$
CREATE TRIGGER after_order_complete
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND NEW.payment_status = 'paid' 
       AND (OLD.status != 'completed' OR OLD.payment_status != 'paid') THEN
        
        -- Increment successful orders
        UPDATE users 
        SET 
            total_successful_orders = total_successful_orders + 1,
            trust_score = LEAST(100, trust_score + 10),
            is_verified_user = IF(total_successful_orders >= 1, 1, is_verified_user),
            cod_enabled = IF(total_successful_orders >= 1 AND cod_blacklisted = 0, 1, cod_enabled)
        WHERE id = NEW.user_id;
    END IF;
END$$

-- Trigger: Auto-cancel expired orders
DROP TRIGGER IF EXISTS before_order_status_check$$

DELIMITER ;

-- ================================================
-- 9. CREATE STORED PROCEDURE - Auto Cancel Expired
-- ================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS cancel_expired_orders$$
CREATE PROCEDURE cancel_expired_orders()
BEGIN
    DECLARE affected_count INT DEFAULT 0;
    
    -- Cancel orders that expired and not paid yet
    UPDATE orders 
    SET 
        status = 'cancelled',
        cancellation_reason = 'Order expired - payment not received within 60 minutes',
        updated_at = NOW()
    WHERE 
        expires_at IS NOT NULL 
        AND expires_at < NOW() 
        AND payment_status = 'pending'
        AND status IN ('pending', 'confirmed');
    
    SET affected_count = ROW_COUNT();
    
    -- Insert notifications for cancelled orders
    IF affected_count > 0 THEN
        INSERT INTO notifications (user_id, type, title, message, created_at)
        SELECT 
            user_id,
            'order_cancelled',
            'Pesanan Dibatalkan',
            CONCAT('Pesanan #', order_number, ' dibatalkan karena pembayaran tidak diterima dalam 60 menit.'),
            NOW()
        FROM orders
        WHERE 
            expires_at < NOW() 
            AND status = 'cancelled'
            AND cancellation_reason LIKE '%expired%'
            AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE);
    END IF;
    
    SELECT affected_count AS cancelled_orders;
END$$

DELIMITER ;

-- ================================================
-- 10. CREATE EVENT - Auto Cancel Every 5 Minutes
-- ================================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Drop if exists
DROP EVENT IF EXISTS auto_cancel_expired_orders;

-- Create event
CREATE EVENT auto_cancel_expired_orders
ON SCHEDULE EVERY 5 MINUTE
STARTS CURRENT_TIMESTAMP
DO CALL cancel_expired_orders();

-- ================================================
-- VERIFICATION QUERIES
-- ================================================

-- Check users with trust score
SELECT COUNT(*) AS users_with_trust_score, 
       AVG(trust_score) AS avg_trust_score,
       SUM(cod_enabled) AS cod_enabled_users
FROM users;

-- Check COD rules
SELECT * FROM cod_validation_rules WHERE is_active = 1;

-- Show event status
SHOW EVENTS WHERE Name = 'auto_cancel_expired_orders';

-- ================================================
-- COMPLETE!
-- ================================================
SELECT '✅ COD System Upgrade Completed!' AS status;
