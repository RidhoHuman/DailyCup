-- ============================================
-- Admin Settings System
-- ============================================
-- Purpose: Centralize all hardcoded configurations (emails, phones, addresses, fees)
-- Benefit: Admin can update business info without code changes

USE dailycup_db;

-- Create admin_settings table
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL COMMENT 'Unique key identifier',
    setting_value TEXT NULL COMMENT 'Setting value (can be JSON for complex data)',
    setting_type ENUM('text', 'number', 'email', 'phone', 'url', 'textarea', 'json') DEFAULT 'text',
    setting_category ENUM('contact', 'business', 'payment', 'loyalty', 'delivery', 'system') DEFAULT 'system',
    setting_label VARCHAR(255) NULL COMMENT 'Human-readable label for admin UI',
    setting_description TEXT NULL COMMENT 'Help text for admin',
    is_public TINYINT(1) DEFAULT 1 COMMENT '1 = visible in public API, 0 = admin only',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (setting_category),
    INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Centralized configuration management';

-- Insert default settings only if admin_settings has the expected schema
SET @has_setting_key = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'admin_settings' AND column_name = 'setting_key');
SET @s = IF(@has_setting_key = 1, 
  'INSERT INTO admin_settings (setting_key, setting_value, setting_type, setting_category, setting_label, setting_description, is_public) VALUES\n  (''support_email'', ''support@dailycup.com'', ''email'', ''contact'', ''Support Email'', ''Customer support email address'', 1),\n  (''info_email'', ''info@dailycup.com'', ''email'', ''contact'', ''General Email'', ''General inquiry email address'', 1),\n  (''admin_email'', ''admin@dailycup.com'', ''email'', ''contact'', ''Admin Email'', ''Admin notification email'', 0),\n  (''support_phone'', ''+62 812-3456-7890'', ''phone'', ''contact'', ''Support Phone'', ''Customer support phone number'', 1),\n  (''whatsapp_number'', ''628123456789'', ''phone'', ''contact'', ''WhatsApp Number'', ''WhatsApp contact (without +)'', 1),\n  (''business_name'', ''DailyCup Coffee Shop'', ''text'', ''business'', ''Business Name'', ''Official business name'', 1),\n  (''store_address'', ''Jl. Sudirman No. 123, Jakarta Pusat'', ''textarea'', ''business'', ''Store Address'', ''Physical store address'', 1),\n  (''store_city'', ''Jakarta'', ''text'', ''business'', ''City'', ''Store city'', 1),\n  (''store_province'', ''DKI Jakarta'', ''text'', ''business'', ''Province'', ''Store province'', 1),\n  (''store_postal_code'', ''10110'', ''text'', ''business'', ''Postal Code'', ''Store postal code'', 1),\n  (''store_latitude'', ''-6.2088'', ''number'', ''business'', ''Latitude'', ''Store GPS latitude'', 1),\n  (''store_longitude'', ''106.8456'', ''number'', ''business'', ''Longitude'', ''Store GPS longitude'', 1),\n  (''business_hours'', ''Mon-Fri: 07:00-22:00, Sat-Sun: 08:00-23:00'', ''textarea'', ''business'', ''Business Hours'', ''Operating hours'', 1),\n  (''delivery_fee_flat'', ''10000'', ''number'', ''payment'', ''Delivery Fee'', ''Flat delivery fee (Rp)'', 1),\n  (''cod_max_amount'', ''500000'', ''number'', ''payment'', ''COD Limit'', ''Maximum COD order amount (Rp)'', 1),\n  (''tax_rate'', ''11'', ''number'', ''payment'', ''Tax Rate'', ''PPN tax percentage (%)'', 1),\n  (''service_charge_rate'', ''0'', ''number'', ''payment'', ''Service Charge'', ''Service charge percentage (%)'', 1),\n  (''loyalty_points_ratio'', ''10000'', ''number'', ''loyalty'', ''Points Earning Rate'', ''1 point per Rp spent'', 1),\n  (''loyalty_points_value'', ''500'', ''number'', ''loyalty'', ''Points Value'', ''1 point = Rp value'', 1),\n  (''loyalty_min_redeem'', ''100'', ''number'', ''loyalty'', ''Min Redeem Points'', ''Minimum points to redeem'', 1),\n  (''tier_bronze_threshold'', ''0'', ''number'', ''loyalty'', ''Bronze Tier'', ''Minimum spend for Bronze tier (Rp)'', 1),\n  (''tier_silver_threshold'', ''250000'', ''number'', ''loyalty'', ''Silver Tier'', ''Minimum spend for Silver tier (Rp)'', 1),\n  (''tier_gold_threshold'', ''500000'', ''number'', ''loyalty'', ''Gold Tier'', ''Minimum spend for Gold tier (Rp)'', 1),\n  (''delivery_radius_km'', ''10'', ''number'', ''delivery'', ''Delivery Radius'', ''Maximum delivery distance (km)'', 1),\n  (''free_delivery_threshold'', ''100000'', ''number'', ''delivery'', ''Free Delivery Above'', ''Free delivery minimum order (Rp)'', 1),\n  (''delivery_estimated_time'', ''30-45'', ''text'', ''delivery'', ''Delivery Time'', ''Estimated delivery time (minutes)'', 1),\n  (''site_url'', ''https://dailycup.vercel.app'', ''url'', ''system'', ''Website URL'', ''Production website URL'', 0),\n  (''api_url'', ''https://api.dailycup.com'', ''url'', ''system'', ''API URL'', ''Backend API URL'', 0),\n  (''app_version'', ''1.0.0'', ''text'', ''system'', ''App Version'', ''Current application version'', 0),\n  (''maintenance_mode'', ''0'', ''number'', ''system'', ''Maintenance Mode'', ''1 = maintenance, 0 = active'', 0)\n  ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Show inserted settings
SELECT 'Admin settings table created and populated!' AS status;

SELECT 
    setting_category,
    COUNT(*) AS total_settings,
    SUM(CASE WHEN is_public = 1 THEN 1 ELSE 0 END) AS public_settings
FROM admin_settings
GROUP BY setting_category
ORDER BY setting_category;
