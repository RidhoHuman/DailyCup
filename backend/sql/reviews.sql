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
CREATE OR REPLACE VIEW product_ratings_summary AS
SELECT 
    product_id,
    COUNT(*) as total_reviews,
    ROUND(AVG(rating), 1) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
FROM product_reviews
WHERE status = 'approved'
GROUP BY product_id;

-- Insert sample reviews for testing (OPTIONAL - Update user_id with actual user IDs)
-- INSERT INTO product_reviews (product_id, user_id, rating, review_title, review_text, verified_purchase, status) VALUES
-- (1, 1, 5, 'Perfect Morning Coffee!', 'This espresso is absolutely amazing! Rich flavor and perfect crema every time. My morning routine is now complete.', TRUE, 'approved'),
-- (1, 2, 4, 'Great taste, a bit strong', 'Love the bold flavor but it can be a bit too strong for some. Perfect for espresso lovers though!', TRUE, 'approved');

-- Note: Uncomment and update user IDs after creating real users
