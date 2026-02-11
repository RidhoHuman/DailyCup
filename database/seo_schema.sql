-- =============================================
-- DailyCup - Advanced SEO Schema
-- Created: 2026-02-05
-- Purpose: SEO metadata, sitemap configuration, structured data
-- =============================================

-- Table: seo_metadata
-- Purpose: Store SEO meta tags for pages, products, categories
CREATE TABLE IF NOT EXISTS seo_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('page', 'product', 'category', 'article', 'custom') NOT NULL,
    entity_id INT DEFAULT NULL COMMENT 'Reference ID (product_id, category_id, etc)',
    slug VARCHAR(255) NOT NULL COMMENT 'URL slug for this entity',
    title VARCHAR(255) NOT NULL COMMENT 'SEO title (60 chars recommended)',
    meta_description TEXT COMMENT 'Meta description (160 chars recommended)',
    meta_keywords VARCHAR(500) COMMENT 'Meta keywords (comma-separated)',
    canonical_url VARCHAR(500) COMMENT 'Canonical URL to avoid duplicate content',
    og_title VARCHAR(255) COMMENT 'Open Graph title',
    og_description TEXT COMMENT 'Open Graph description',
    og_image VARCHAR(500) COMMENT 'Open Graph image URL',
    og_type VARCHAR(50) DEFAULT 'website' COMMENT 'Open Graph type (website, product, article)',
    twitter_card VARCHAR(50) DEFAULT 'summary_large_image' COMMENT 'Twitter card type',
    twitter_title VARCHAR(255) COMMENT 'Twitter card title',
    twitter_description TEXT COMMENT 'Twitter card description',
    twitter_image VARCHAR(500) COMMENT 'Twitter card image',
    structured_data JSON COMMENT 'JSON-LD structured data (schema.org)',
    robots VARCHAR(100) DEFAULT 'index, follow' COMMENT 'Robots meta tag',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_entity (entity_type, entity_id),
    INDEX idx_slug (slug),
    INDEX idx_entity_type (entity_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: sitemap_config
-- Purpose: Control sitemap generation settings
CREATE TABLE IF NOT EXISTS sitemap_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('page', 'product', 'category', 'article') NOT NULL,
    include_in_sitemap BOOLEAN DEFAULT TRUE,
    priority DECIMAL(2,1) DEFAULT 0.5 COMMENT 'Sitemap priority (0.0 - 1.0)',
    change_frequency ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') DEFAULT 'weekly',
    last_generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_entity_type (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: seo_redirects
-- Purpose: Manage 301/302 redirects for SEO
CREATE TABLE IF NOT EXISTS seo_redirects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    old_url VARCHAR(500) NOT NULL COMMENT 'Old URL path',
    new_url VARCHAR(500) NOT NULL COMMENT 'New URL path',
    redirect_type ENUM('301', '302', '307') DEFAULT '301' COMMENT 'HTTP redirect type',
    is_active BOOLEAN DEFAULT TRUE,
    hit_count INT DEFAULT 0 COMMENT 'Number of times this redirect was used',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_old_url (old_url),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: seo_analytics
-- Purpose: Track SEO performance metrics
CREATE TABLE IF NOT EXISTS seo_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    search_keyword VARCHAR(255) COMMENT 'Search keyword that led to this page',
    referrer VARCHAR(500) COMMENT 'Referrer URL',
    user_agent TEXT COMMENT 'Browser user agent',
    device_type ENUM('desktop', 'mobile', 'tablet', 'bot') DEFAULT 'desktop',
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_url (page_url),
    INDEX idx_keyword (search_keyword),
    INDEX idx_visited (visited_at),
    INDEX idx_device (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Initial Data: Default SEO Metadata
-- =============================================

-- Homepage SEO
INSERT INTO seo_metadata (entity_type, entity_id, slug, title, meta_description, meta_keywords, og_title, og_description, og_type, structured_data) VALUES
('page', NULL, 'home', 
 'DailyCup - Premium Coffee Shop | Fresh Brewed Daily', 
 'Welcome to DailyCup, your neighborhood coffee shop serving premium coffee, fresh pastries, and delicious meals. Order online for delivery or pickup.',
 'coffee shop, premium coffee, fresh pastries, coffee delivery, daily cup, kopi',
 'DailyCup - Premium Coffee Delivered Fresh Daily',
 'Experience the finest coffee in town. Fresh brewed daily with locally sourced beans. Order now for fast delivery!',
 'website',
 '{"@context":"https://schema.org","@type":"CafeOrCoffeeShop","name":"DailyCup Coffee Shop","image":"https://dailycup.com/assets/images/logo.png","address":{"@type":"PostalAddress","addressCountry":"ID"},"priceRange":"$$","servesCuisine":"Coffee & Beverages","telephone":"+62-xxx-xxxx"}'),

('page', NULL, 'menu',
 'Our Menu - Fresh Coffee & Delicious Food | DailyCup',
 'Browse our extensive menu of premium coffee drinks, teas, smoothies, and fresh food. Made with quality ingredients and love.',
 'coffee menu, drink menu, food menu, coffee drinks, beverages',
 'DailyCup Full Menu - Coffee, Food & Beverages',
 'Explore our delicious menu featuring specialty coffee, fresh pastries, and more. Something for everyone!',
 'website',
 '{"@context":"https://schema.org","@type":"Menu","name":"DailyCup Menu","hasMenuSection":[{"@type":"MenuSection","name":"Coffee & Beverages"},{"@type":"MenuSection","name":"Food & Snacks"}]}'),

('page', NULL, 'about',
 'About Us - Our Story & Mission | DailyCup Coffee',
 'Learn about DailyCup\'s journey to bring premium coffee to your neighborhood. Our commitment to quality and community.',
 'about us, coffee story, our mission, coffee shop history',
 'About DailyCup - Quality Coffee Since Day One',
 'Passionate about coffee, dedicated to quality. Learn our story and what makes DailyCup special.',
 'website',
 NULL);

-- =============================================
-- Sitemap Configuration Defaults
-- =============================================

INSERT INTO sitemap_config (entity_type, include_in_sitemap, priority, change_frequency) VALUES
('page', TRUE, 0.8, 'weekly'),
('product', TRUE, 0.6, 'daily'),
('category', TRUE, 0.7, 'weekly'),
('article', TRUE, 0.5, 'monthly');

-- =============================================
-- Sample SEO Redirects
-- =============================================

INSERT INTO seo_redirects (old_url, new_url, redirect_type, is_active) VALUES
('/old-menu', '/menu', '301', TRUE),
('/products', '/menu', '301', TRUE),
('/contact-us', '/customer/contact', '301', TRUE);

-- =============================================
-- Indexes for Performance
-- =============================================

-- Additional indexes for common queries
ALTER TABLE seo_metadata ADD INDEX idx_title (title(100));
ALTER TABLE seo_metadata ADD FULLTEXT INDEX ft_description (meta_description);

-- =============================================
-- END OF SEO SCHEMA
-- =============================================
