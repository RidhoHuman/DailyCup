-- ========================================
-- Quick Test Data Setup
-- Run this to create test order for tracking
-- ========================================

-- Create test order with ON_DELIVERY status
INSERT INTO `orders` (
    `order_id`, 
    `user_id`, 
    `customer_name`, 
    `customer_email`, 
    `customer_phone`, 
    `customer_address`,
    `subtotal`, 
    `discount`, 
    `delivery_fee`, 
    `total`, 
    `status`, 
    `payment_method`,
    `courier_id`,
    `estimated_delivery`,
    `created_at`
) VALUES (
    'ORD-TEST-001',
    1,
    'Test Customer',
    'test@dailycup.com',
    '08123456789',
    'Jl. Sudirman No. 123, Jakarta Pusat',
    50000,
    0,
    10000,
    60000,
    'on_delivery',
    'cod',
    1,  -- Budi Santoso (courier)
    DATE_ADD(NOW(), INTERVAL 30 MINUTE),
    NOW()
);

-- Add order items
INSERT INTO `order_items` (
    `order_id`,
    `product_id`,
    `product_name`,
    `variant`,
    `quantity`,
    `price`,
    `subtotal`
) VALUES 
(
    'ORD-TEST-001',
    1,
    'Espresso',
    'Hot - Large',
    2,
    25000,
    50000
);

-- Add status history
INSERT INTO `order_status_log` (
    `order_id`,
    `status`,
    `message`,
    `changed_by`,
    `changed_by_type`,
    `created_at`
) VALUES 
(
    'ORD-TEST-001',
    'pending_payment',
    'Order created',
    1,
    'customer',
    DATE_SUB(NOW(), INTERVAL 30 MINUTE)
),
(
    'ORD-TEST-001',
    'waiting_confirmation',
    'Payment received',
    1,
    'system',
    DATE_SUB(NOW(), INTERVAL 25 MINUTE)
),
(
    'ORD-TEST-001',
    'queueing',
    'COD verified',
    1,
    'customer',
    DATE_SUB(NOW(), INTERVAL 20 MINUTE)
),
(
    'ORD-TEST-001',
    'preparing',
    'Order is being prepared',
    1,
    'admin',
    DATE_SUB(NOW(), INTERVAL 15 MINUTE)
),
(
    'ORD-TEST-001',
    'on_delivery',
    'Courier Budi Santoso assigned',
    1,
    'admin',
    DATE_SUB(NOW(), INTERVAL 10 MINUTE)
);

-- Update courier location (Jakarta area)
UPDATE `couriers` 
SET 
    `current_location_lat` = -6.200000,
    `current_location_lng` = 106.816666,
    `is_available` = FALSE
WHERE `id` = 1;

-- Verify data
SELECT 
    o.order_id,
    o.customer_name,
    o.status,
    o.payment_method,
    c.name as courier_name,
    o.estimated_delivery,
    o.created_at
FROM orders o
LEFT JOIN couriers c ON o.courier_id = c.id
WHERE o.order_id = 'ORD-TEST-001';

-- Check status history
SELECT * FROM order_status_log 
WHERE order_id = 'ORD-TEST-001' 
ORDER BY created_at ASC;

-- ========================================
-- Expected Result:
-- 1 order found: ORD-TEST-001
-- Status: on_delivery
-- Courier: Budi Santoso
-- 5 status logs
-- ========================================
