-- Create product_variants table
CREATE TABLE IF NOT EXISTS product_variants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  variant_type VARCHAR(100) NOT NULL,
  variant_value VARCHAR(100) NOT NULL,
  price_adjustment DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_product_id (product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample variant data (adjust product_id if your products have different IDs)
INSERT INTO product_variants (product_id, variant_type, variant_value, price_adjustment)
VALUES
(1, 'size', 'Regular', 0),
(1, 'size', 'Large', 5000),
(1, 'temperature', 'Hot', 0),
(2, 'size', 'Regular', 0),
(2, 'size', 'Large', 5000),
(2, 'temperature', 'Hot', 0),
(2, 'temperature', 'Iced', 2000),
(3, 'size', 'Regular', 0),
(3, 'size', 'Large', 5000),
(3, 'temperature', 'Hot', 0),
(3, 'temperature', 'Iced', 2000);
