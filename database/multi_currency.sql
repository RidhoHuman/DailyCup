-- Multi-Currency Support Tables

-- Currencies table
CREATE TABLE IF NOT EXISTS currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    decimal_places INT DEFAULT 2,
    is_active BOOLEAN DEFAULT TRUE,
    is_base_currency BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    INDEX idx_base (is_base_currency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exchange rates table
CREATE TABLE IF NOT EXISTS exchange_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(20, 6) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    source VARCHAR(50) DEFAULT 'manual',
    UNIQUE KEY unique_pair (from_currency, to_currency),
    FOREIGN KEY (from_currency) REFERENCES currencies(code) ON DELETE CASCADE,
    FOREIGN KEY (to_currency) REFERENCES currencies(code) ON DELETE CASCADE,
    INDEX idx_from (from_currency),
    INDEX idx_to (to_currency),
    INDEX idx_updated (last_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Currency settings table
CREATE TABLE IF NOT EXISTS currency_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default currencies
INSERT INTO currencies (code, name, symbol, decimal_places, is_active, is_base_currency, display_order) VALUES
('IDR', 'Indonesian Rupiah', 'Rp', 0, TRUE, TRUE, 1),
('USD', 'US Dollar', '$', 2, TRUE, FALSE, 2),
('EUR', 'Euro', '€', 2, TRUE, FALSE, 3),
('GBP', 'British Pound', '£', 2, TRUE, FALSE, 4),
('SGD', 'Singapore Dollar', 'S$', 2, TRUE, FALSE, 5),
('MYR', 'Malaysian Ringgit', 'RM', 2, TRUE, FALSE, 6),
('JPY', 'Japanese Yen', '¥', 0, TRUE, FALSE, 7),
('CNY', 'Chinese Yuan', '¥', 2, TRUE, FALSE, 8),
('AUD', 'Australian Dollar', 'A$', 2, TRUE, FALSE, 9),
('THB', 'Thai Baht', '฿', 2, TRUE, FALSE, 10);

-- Insert base exchange rates (1 IDR to other currencies)
-- These are approximate rates as of 2024, should be updated via API
INSERT INTO exchange_rates (from_currency, to_currency, rate, source) VALUES
('IDR', 'IDR', 1.000000, 'base'),
('IDR', 'USD', 0.000064, 'initial'),
('IDR', 'EUR', 0.000059, 'initial'),
('IDR', 'GBP', 0.000051, 'initial'),
('IDR', 'SGD', 0.000086, 'initial'),
('IDR', 'MYR', 0.000298, 'initial'),
('IDR', 'JPY', 0.009460, 'initial'),
('IDR', 'CNY', 0.000462, 'initial'),
('IDR', 'AUD', 0.000098, 'initial'),
('IDR', 'THB', 0.002277, 'initial');

-- Insert reverse rates (other currencies to IDR)
INSERT INTO exchange_rates (from_currency, to_currency, rate, source) VALUES
('USD', 'IDR', 15625.000000, 'initial'),
('EUR', 'IDR', 16949.152542, 'initial'),
('GBP', 'IDR', 19607.843137, 'initial'),
('SGD', 'IDR', 11627.906977, 'initial'),
('MYR', 'IDR', 3355.704698, 'initial'),
('JPY', 'IDR', 105.708245, 'initial'),
('CNY', 'IDR', 2164.502165, 'initial'),
('AUD', 'IDR', 10204.081633, 'initial'),
('THB', 'IDR', 439.224011, 'initial');

-- Insert currency settings
INSERT INTO currency_settings (setting_key, setting_value) VALUES
('auto_update_rates', 'true'),
('rate_update_interval', '86400'),
('default_display_currency', 'IDR'),
('show_currency_selector', 'true'),
('exchange_rate_api_key', NULL),
('rate_api_provider', 'exchangerate-api');
