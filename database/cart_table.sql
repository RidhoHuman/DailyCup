-- ============================================
-- PERSISTENT CART TABLE
-- Untuk menyimpan cart items agar tidak hilang saat logout
-- ============================================

-- Buat tabel cart
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    size VARCHAR(50),
    temperature VARCHAR(50),
    quantity INT NOT NULL DEFAULT 1,
    image VARCHAR(255),
    cart_key VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_cart_key (cart_key),
    UNIQUE KEY unique_user_cart_key (user_id, cart_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catatan:
-- 1. cart_key = unique identifier untuk kombinasi product + size + temperature
-- 2. UNIQUE KEY memastikan tidak ada duplicate item untuk user yang sama
-- 3. ON DELETE CASCADE: jika user/product dihapus, cart items juga ikut terhapus
-- 4. updated_at otomatis update saat ada perubahan quantity
