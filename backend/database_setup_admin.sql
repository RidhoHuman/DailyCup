    -- Admin Dashboard Setup - Quick Database Check & Setup
-- Run this in your MySQL client (Laragon > MySQL > HeidiSQL or phpMyAdmin)

USE dailycup_db;

-- 1. Check if admin user exists
SELECT id, name, email, role, created_at 
FROM users 
WHERE role = 'admin';

-- 2. If no admin exists, create one
-- Password: 'password' (bcrypt hashed)
INSERT INTO users (name, email, password, role, loyalty_points, created_at) 
SELECT 'Admin DailyCup', 'admin@dailycup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 0, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@dailycup.com'
);

-- 3. Check orders data
SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as total_revenue
FROM orders;

-- 4. Check recent orders
SELECT 
    o.id,
    o.order_id,
    u.name as customer,
    o.total,
    o.status,
    o.created_at
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
ORDER BY o.created_at DESC
LIMIT 10;

-- 5. Check top products
SELECT 
    p.id,
    p.name,
    COALESCE(SUM(oi.quantity), 0) as total_sold,
    COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'paid'
GROUP BY p.id, p.name
HAVING total_sold > 0
ORDER BY total_sold DESC
LIMIT 5;

-- 6. Verify admin can access (test login query)
SELECT id, name, email, role 
FROM users 
WHERE email = 'admin@dailycup.com' 
AND is_active = 1;

-- OPTIONAL: Create sample data if database is empty
-- Uncomment below if you need test data

/*
-- Create sample customer if none exists
INSERT INTO users (name, email, password, role, created_at)
SELECT 'Test Customer', 'customer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'customer@test.com');

-- Get sample customer ID and product IDs
SET @customer_id = (SELECT id FROM users WHERE email = 'customer@test.com' LIMIT 1);
SET @product_id = (SELECT id FROM products LIMIT 1);

-- Create sample orders
INSERT INTO orders (order_id, user_id, total, status, created_at)
VALUES 
    (CONCAT('ORD-', UNIX_TIMESTAMP(), '-', FLOOR(RAND() * 10000)), @customer_id, 50000, 'paid', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (CONCAT('ORD-', UNIX_TIMESTAMP()+1, '-', FLOOR(RAND() * 10000)), @customer_id, 75000, 'paid', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (CONCAT('ORD-', UNIX_TIMESTAMP()+2, '-', FLOOR(RAND() * 10000)), @customer_id, 40000, 'pending', NOW());

-- Create sample order items
INSERT INTO order_items (order_id, product_id, quantity, price)
SELECT id, @product_id, 2, 25000 FROM orders WHERE status = 'paid' LIMIT 2;
*/
