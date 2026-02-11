-- Seasonal Themes Table
CREATE TABLE IF NOT EXISTS `themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL UNIQUE,
  `description` text,
  `is_active` tinyint(1) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `colors` text NOT NULL COMMENT 'JSON: primary, secondary, accent, background, text colors',
  `images` text COMMENT 'JSON: banner, logo, background images',
  `custom_css` text COMMENT 'Additional custom CSS',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Themes
INSERT INTO `themes` (`name`, `slug`, `description`, `is_active`, `start_date`, `end_date`, `colors`, `images`, `custom_css`) VALUES
('Default', 'default', 'Classic DailyCup theme with coffee brown colors', 1, NULL, NULL, 
'{"primary":"#8B4513","secondary":"#D2691E","accent":"#F4A460","background":"#FFFFFF","text":"#333333","navBackground":"#1a1a1a","navText":"#ffffff"}',
'{"banner":null,"logo":null,"background":null}',
NULL),

('Christmas', 'christmas', 'Festive Christmas theme with red and green colors', 0, '2025-12-01', '2025-12-26',
'{"primary":"#C41E3A","secondary":"#0F8A5F","accent":"#FFD700","background":"#FFFFFF","text":"#2C1810","navBackground":"#C41E3A","navText":"#ffffff"}',
'{"banner":"/assets/images/themes/christmas-banner.jpg","logo":null,"background":"/assets/images/themes/christmas-bg.png"}',
'.christmas-snow { animation: snowfall 10s linear infinite; }'),

('Valentine', 'valentine', 'Romantic Valentine theme with pink and red tones', 0, '2026-02-01', '2026-02-15',
'{"primary":"#FF1493","secondary":"#FF69B4","accent":"#FFB6C1","background":"#FFF0F5","text":"#4A0E0E","navBackground":"#FF1493","navText":"#ffffff"}',
'{"banner":"/assets/images/themes/valentine-banner.jpg","logo":null,"background":"/assets/images/themes/hearts-bg.png"}',
'.valentine-hearts { background-image: url(/assets/images/themes/hearts-pattern.png); }'),

('Ramadan', 'ramadan', 'Elegant Ramadan theme with gold and dark blue', 0, '2026-03-10', '2026-04-10',
'{"primary":"#1A237E","secondary":"#FFD700","accent":"#B8860B","background":"#F5F5DC","text":"#1A1A1A","navBackground":"#1A237E","navText":"#FFD700"}',
'{"banner":"/assets/images/themes/ramadan-banner.jpg","logo":null,"background":"/assets/images/themes/islamic-pattern.png"}',
'.ramadan-stars { background: radial-gradient(circle, #FFD700 2px, transparent 2px); }'),

('Summer', 'summer', 'Bright summer theme with tropical colors', 0, '2026-06-01', '2026-08-31',
'{"primary":"#FF6B35","secondary":"#F7931E","accent":"#FDC830","background":"#FFFACD","text":"#2C3E50","navBackground":"#FF6B35","navText":"#ffffff"}',
'{"banner":"/assets/images/themes/summer-banner.jpg","logo":null,"background":"/assets/images/themes/tropical-bg.png"}',
'.summer-gradient { background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); }'),

('Halloween', 'halloween', 'Spooky Halloween theme with orange and purple', 0, '2025-10-20', '2025-11-01',
'{"primary":"#FF6600","secondary":"#663399","accent":"#000000","background":"#1A1A1A","text":"#FFFFFF","navBackground":"#000000","navText":"#FF6600"}',
'{"banner":"/assets/images/themes/halloween-banner.jpg","logo":null,"background":"/assets/images/themes/spooky-bg.png"}',
'.halloween-glow { box-shadow: 0 0 20px #FF6600; }');

-- Settings table for active theme
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL UNIQUE,
  `value` text,
  `description` varchar(255),
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert active theme setting
INSERT INTO `settings` (`key`, `value`, `description`) VALUES
('active_theme', 'default', 'Currently active theme slug')
ON DUPLICATE KEY UPDATE value = value;
