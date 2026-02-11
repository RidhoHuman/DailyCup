-- ============================================
-- CARA PAKAI:
-- Copy-paste dan jalankan SATU QUERY SAJA setiap kalinya.
-- Jika muncul error "Duplicate column name" -> SKIP, lanjut ke query berikutnya.
-- Jika berhasil tanpa error -> Kolom sudah berhasil ditambahkan!
-- ============================================

USE dailycup_db;

-- ===== QUERY 1: temperature =====
ALTER TABLE order_items ADD COLUMN temperature VARCHAR(20) DEFAULT NULL COMMENT 'Hot, Ice, Normal' AFTER size;

-- ===== QUERY 2: addons =====
ALTER TABLE order_items ADD COLUMN addons TEXT DEFAULT NULL COMMENT 'JSON array of selected add-ons' AFTER subtotal;

-- ===== QUERY 3: notes =====
ALTER TABLE order_items ADD COLUMN notes TEXT DEFAULT NULL COMMENT 'Custom order notes' AFTER addons;

-- ===== QUERY 4: base_price =====
ALTER TABLE order_items ADD COLUMN base_price DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Base product price' AFTER notes;

-- ===== QUERY 5: size_price_modifier =====
ALTER TABLE order_items ADD COLUMN size_price_modifier DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Price adjustment for size selection' AFTER base_price;

-- ===== QUERY 6: addons_total =====
ALTER TABLE order_items ADD COLUMN addons_total DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total price of add-ons' AFTER size_price_modifier;

-- ===== CEK HASIL =====
-- Jalankan query ini di akhir untuk memastikan semua kolom sudah ada:
SHOW COLUMNS FROM order_items;

-- ============================================
-- DONE! Customization columns added successfully
-- Now you can test orders with customization
-- ============================================
