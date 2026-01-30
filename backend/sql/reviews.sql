-- ============================================
-- DailyCup - Product Reviews System
-- Created: 2026-01-29
-- Purpose: Store product reviews and ratings
-- ============================================

-- Create reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(255) NOT NULL,
    review_text TEXT NOT NULL,
    helpful_count INT DEFAULT 0,
    verified_purchase BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create review helpful tracking table (for "Was this review helpful?" feature)
CREATE TABLE IF NOT EXISTS review_helpful (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    is_helpful BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_review (review_id, user_id),
    FOREIGN KEY (review_id) REFERENCES product_reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create view for product ratings summary
-- NOTE: CREATE VIEW is not permitted on some free hosts (e.g., InfinityFree).
-- The original view is intentionally omitted to avoid import errors.
-- Products API uses an inline aggregated subquery instead, see backend/api/products.php.

-- Optional: If your MySQL user *does* support CREATE VIEW, you can manually run the following in phpMyAdmin:
-- CREATE OR REPLACE VIEW product_ratings_summary AS
-- SELECT 
--     product_id,
--     COUNT(*) as total_reviews,
--     ROUND(AVG(rating), 1) as average_rating,
--     SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
--     SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
--     SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
--     SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
--     SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
-- FROM product_reviews
-- WHERE status = 'approved'
-- GROUP BY product_id;

-- Alternative (works on all hosts): create a physical summary table and populate it via a script or scheduled job.
-- Example to create summary table:
-- CREATE TABLE IF NOT EXISTS product_ratings_summary (
--   product_id INT PRIMARY KEY,
--   total_reviews INT DEFAULT 0,
--   average_rating DECIMAL(3,1) DEFAULT NULL,
--   five_star INT DEFAULT 0,
--   four_star INT DEFAULT 0,
--   three_star INT DEFAULT 0,
--   two_star INT DEFAULT 0,
--   one_star INT DEFAULT 0
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- To populate (run manually or via update script):
-- INSERT INTO product_ratings_summary (product_id, total_reviews, average_rating, five_star, four_star, three_star, two_star, one_star)
-- SELECT product_id, COUNT(*), ROUND(AVG(rating),1),
--     SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END),
--     SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END),
--     SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END),
--     SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END),
--     SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END)
-- FROM product_reviews WHERE status = 'approved' GROUP BY product_id
-- ON DUPLICATE KEY UPDATE
--   total_reviews = VALUES(total_reviews),
--   average_rating = VALUES(average_rating),
--   five_star = VALUES(five_star),
--   four_star = VALUES(four_star),
--   three_star = VALUES(three_star),
--   two_star = VALUES(two_star),
--   one_star = VALUES(one_star);


-- Insert sample reviews for testing (OPTIONAL - Update user_id with actual user IDs)
-- INSERT INTO product_reviews (product_id, user_id, rating, review_title, review_text, verified_purchase, status) VALUES
-- (1, 1, 5, 'Perfect Morning Coffee!', 'This espresso is absolutely amazing! Rich flavor and perfect crema every time. My morning routine is now complete.', TRUE, 'approved'),
-- (1, 2, 4, 'Great taste, a bit strong', 'Love the bold flavor but it can be a bit too strong for some. Perfect for espresso lovers though!', TRUE, 'approved');

-- Note: Uncomment and update user IDs after creating real users
