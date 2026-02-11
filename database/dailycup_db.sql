-- DailyCup Coffee Shop Database Schema
-- Created for Laragon MySQL

-- Create database
CREATE DATABASE IF NOT EXISTS dailycup_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dailycup_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin', 'super_admin') DEFAULT 'customer',
    oauth_provider VARCHAR(50),
    oauth_id VARCHAR(255),
    profile_image VARCHAR(255),
    loyalty_points INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_oauth (oauth_provider, oauth_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product variants table (size, temperature)
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_type ENUM('size', 'temperature') NOT NULL,
    variant_value VARCHAR(50) NOT NULL,
    price_adjustment DECIMAL(10,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discounts table
CREATE TABLE discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2),
    usage_limit INT,
    usage_count INT DEFAULT 0,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner discounts table
CREATE TABLE partner_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(255) NOT NULL,
    partner_logo VARCHAR(255),
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    description TEXT,
    terms_conditions TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    points_used INT DEFAULT 0,
    points_value DECIMAL(10,2) DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    delivery_method ENUM('dine-in', 'takeaway', 'delivery') NOT NULL,
    delivery_address TEXT,
    customer_notes TEXT,
    status ENUM('pending', 'confirmed', 'processing', 'ready', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_proof VARCHAR(255),
    paid_at DATETIME,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    size VARCHAR(50),
    temperature VARCHAR(50),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    review_images TEXT,
    admin_reply TEXT,
    replied_at DATETIME,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    UNIQUE KEY unique_review (user_id, product_id, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Favorites table
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loyalty transactions table
CREATE TABLE loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT,
    points INT NOT NULL,
    transaction_type ENUM('earned', 'redeemed', 'expired', 'adjusted') NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loyalty settings table
CREATE TABLE loyalty_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    points_per_rupiah DECIMAL(10,4) DEFAULT 0.01,
    rupiah_per_point DECIMAL(10,2) DEFAULT 100,
    min_points_redeem INT DEFAULT 100,
    points_expiry_days INT DEFAULT 365,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Returns table
CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    reason ENUM('wrong_order', 'damaged', 'quality_issue', 'missing_items', 'other') NOT NULL,
    description TEXT NOT NULL,
    proof_images TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    refund_amount DECIMAL(10,2),
    processed_by INT,
    processed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment methods table
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_type ENUM('bank_transfer', 'qris', 'e_wallet') NOT NULL,
    method_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(100),
    account_name VARCHAR(255),
    qr_code_image VARCHAR(255),
    instructions TEXT,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default data
-- Insert default super admin
INSERT INTO users (name, email, password, role) VALUES 
('Super Admin', 'admin@dailycup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');
-- Password: admin123

-- Insert default categories
INSERT INTO categories (name, description, display_order, is_active) VALUES
('Coffee', 'Premium coffee beverages', 1, 1),
('Non-Coffee', 'Refreshing non-coffee drinks', 2, 1),
('Snacks', 'Delicious snacks and pastries', 3, 1),
('Desserts', 'Sweet treats and desserts', 4, 1);

-- Insert sample products
INSERT INTO products (category_id, name, description, base_price, is_featured, stock) VALUES
(1, 'Espresso', 'Rich and bold espresso shot', 25000.00, 1, 100),
(1, 'Cappuccino', 'Classic cappuccino with perfect foam', 35000.00, 1, 100),
(1, 'Latte', 'Smooth and creamy latte', 38000.00, 1, 100),
(1, 'Americano', 'Classic black coffee', 30000.00, 0, 100),
(1, 'Mocha', 'Chocolate coffee blend', 40000.00, 1, 100),
(2, 'Matcha Latte', 'Premium Japanese matcha', 42000.00, 1, 100),
(2, 'Chocolate', 'Rich hot chocolate', 35000.00, 0, 100),
(2, 'Fresh Orange Juice', 'Freshly squeezed orange juice', 28000.00, 0, 100),
(3, 'Croissant', 'Buttery French croissant', 25000.00, 0, 100),
(3, 'Sandwich', 'Fresh sandwich with various fillings', 45000.00, 0, 100),
(4, 'Tiramisu', 'Classic Italian tiramisu', 35000.00, 1, 50),
(4, 'Cheesecake', 'Creamy New York style cheesecake', 38000.00, 1, 50);

-- Insert product variants
INSERT INTO product_variants (product_id, variant_type, variant_value, price_adjustment) VALUES
-- Size variants for coffee products
(1, 'size', 'Regular', 0.00),
(1, 'size', 'Large', 5000.00),
(2, 'size', 'Regular', 0.00),
(2, 'size', 'Large', 5000.00),
(3, 'size', 'Regular', 0.00),
(3, 'size', 'Large', 5000.00),
(4, 'size', 'Regular', 0.00),
(4, 'size', 'Large', 5000.00),
(5, 'size', 'Regular', 0.00),
(5, 'size', 'Large', 5000.00),
-- Temperature variants for coffee products
(1, 'temperature', 'Hot', 0.00),
(2, 'temperature', 'Hot', 0.00),
(2, 'temperature', 'Iced', 2000.00),
(3, 'temperature', 'Hot', 0.00),
(3, 'temperature', 'Iced', 2000.00),
(4, 'temperature', 'Hot', 0.00),
(4, 'temperature', 'Iced', 2000.00),
(5, 'temperature', 'Hot', 0.00),
(5, 'temperature', 'Iced', 2000.00),
-- Non-coffee variants
(6, 'size', 'Regular', 0.00),
(6, 'size', 'Large', 5000.00),
(6, 'temperature', 'Hot', 0.00),
(6, 'temperature', 'Iced', 2000.00),
(7, 'size', 'Regular', 0.00),
(7, 'size', 'Large', 5000.00),
(7, 'temperature', 'Hot', 0.00),
(8, 'size', 'Regular', 0.00),
(8, 'size', 'Large', 5000.00);

-- Insert sample discount codes
INSERT INTO discounts (code, name, description, discount_type, discount_value, min_purchase, start_date, end_date, usage_limit) VALUES
('WELCOME10', 'Welcome Discount', 'Get 10% off on your first order', 'percentage', 10.00, 50000.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 1000),
('COFFEE20', 'Coffee Lover', 'Get Rp 20.000 off on coffee orders', 'fixed', 20000.00, 100000.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59', NULL),
('DAILYCUP50', 'Big Discount', 'Get 50% off (max Rp 50.000)', 'percentage', 50.00, 150000.00, '2024-01-01 00:00:00', '2025-12-31 23:59:59', 100);

-- Insert partner discounts
INSERT INTO partner_discounts (partner_name, discount_type, discount_value, description, start_date, end_date) VALUES
('Bank BCA', 'percentage', 15.00, 'Get 15% discount for BCA credit card holders', '2024-01-01 00:00:00', '2025-12-31 23:59:59'),
('GoPay', 'fixed', 10000.00, 'Get Rp 10.000 cashback for GoPay users', '2024-01-01 00:00:00', '2025-12-31 23:59:59'),
('Grab', 'percentage', 20.00, 'Get 20% discount for Grab users', '2024-01-01 00:00:00', '2025-12-31 23:59:59');

-- Insert payment methods
INSERT INTO payment_methods (method_type, method_name, account_number, account_name, instructions, display_order) VALUES
('bank_transfer', 'BCA', '1234567890', 'DailyCup Coffee Shop', 'Transfer ke rekening BCA dan upload bukti pembayaran', 1),
('bank_transfer', 'Mandiri', '9876543210', 'DailyCup Coffee Shop', 'Transfer ke rekening Mandiri dan upload bukti pembayaran', 2),
('bank_transfer', 'BNI', '5555666677', 'DailyCup Coffee Shop', 'Transfer ke rekening BNI dan upload bukti pembayaran', 3),
('qris', 'QRIS', NULL, NULL, 'Scan QR Code dan upload bukti pembayaran', 4),
('e_wallet', 'GoPay', '081234567890', 'DailyCup', 'Transfer ke nomor GoPay dan upload bukti pembayaran', 5),
('e_wallet', 'OVO', '081234567890', 'DailyCup', 'Transfer ke nomor OVO dan upload bukti pembayaran', 6),
('e_wallet', 'Dana', '081234567890', 'DailyCup', 'Transfer ke nomor Dana dan upload bukti pembayaran', 7),
('e_wallet', 'ShopeePay', '081234567890', 'DailyCup', 'Transfer ke nomor ShopeePay dan upload bukti pembayaran', 8);

-- Insert loyalty settings
INSERT INTO loyalty_settings (points_per_rupiah, rupiah_per_point, min_points_redeem) VALUES
(0.01, 100.00, 100);
