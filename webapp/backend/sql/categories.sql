-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    slug VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure slug column exists (idempotent)
SET @cnt = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'categories' AND column_name = 'slug');
SET @s = IF(@cnt = 0, 'ALTER TABLE categories ADD COLUMN slug VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Insert sample categories only if they do not already exist
INSERT INTO categories (name, description, slug)
SELECT 'Coffee','All coffee beverages','coffee' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Coffee');
INSERT INTO categories (name, description, slug)
SELECT 'Non-Coffee','Non-coffee drinks','non-coffee' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Non-Coffee');
INSERT INTO categories (name, description, slug)
SELECT 'Snacks','Pastry and snacks','snacks' WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Snacks');
