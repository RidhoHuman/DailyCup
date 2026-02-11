-- Admin Dashboard Setup - Quick Database Check & Setup (InfinityFree Compatible)

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

-- 3. Check orders data (perbaiki query - gunakan order_total atau hapus kolom total)
SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
FROM orders;

-- 4. Check recent orders
SELECT 
    o.id,
    o.order_id,
    u.name as customer,
    o.status,
    o.created_at
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
ORDER BY o.created_at DESC
LIMIT 10;

-- 5. Verify admin can access (test login query)
SELECT id, name, email, role 
FROM users 
WHERE email = 'admin@dailycup.com' 
AND is_active = 1;
