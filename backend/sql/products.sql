-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    base_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    stock INT DEFAULT 0,
    category_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample products
INSERT INTO products (name, description, base_price, image, is_featured, stock, category_id)
VALUES
('Espresso', 'Rich and bold espresso shot', 25000, 'products/prod_espresso.jfif', 1, 100, 1),
('Cappuccino', 'Classic cappuccino with perfect foam', 35000, 'products/prod_cappuccino.jfif', 1, 100, 1),
('Latte', 'Smooth and creamy latte', 38000, 'products/prod_latte.jfif', 1, 100, 1);
