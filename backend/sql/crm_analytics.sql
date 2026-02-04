-- CRM Analytics: RFM (Recency, Frequency, Monetary) Segmentation
-- This view helps identify customer segments for targeted marketing

-- Customer RFM Analysis View
CREATE OR REPLACE VIEW `customer_rfm_analysis` AS
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    u.phone,
    u.loyalty_points,
    
    -- Recency: Days since last order
    DATEDIFF(NOW(), MAX(o.created_at)) as days_since_last_order,
    MAX(o.created_at) as last_order_date,
    
    -- Frequency: Total number of orders
    COUNT(DISTINCT o.id) as total_orders,
    
    -- Monetary: Total amount spent
    COALESCE(SUM(o.final_amount), 0) as total_spent,
    COALESCE(AVG(o.final_amount), 0) as avg_order_value,
    
    -- RFM Scoring (1-5 scale, 5 is best)
    CASE 
        WHEN DATEDIFF(NOW(), MAX(o.created_at)) <= 7 THEN 5
        WHEN DATEDIFF(NOW(), MAX(o.created_at)) <= 14 THEN 4
        WHEN DATEDIFF(NOW(), MAX(o.created_at)) <= 30 THEN 3
        WHEN DATEDIFF(NOW(), MAX(o.created_at)) <= 60 THEN 2
        ELSE 1
    END as recency_score,
    
    CASE 
        WHEN COUNT(DISTINCT o.id) >= 10 THEN 5
        WHEN COUNT(DISTINCT o.id) >= 7 THEN 4
        WHEN COUNT(DISTINCT o.id) >= 5 THEN 3
        WHEN COUNT(DISTINCT o.id) >= 3 THEN 2
        ELSE 1
    END as frequency_score,
    
    CASE 
        WHEN COALESCE(SUM(o.final_amount), 0) >= 1000000 THEN 5
        WHEN COALESCE(SUM(o.final_amount), 0) >= 500000 THEN 4
        WHEN COALESCE(SUM(o.final_amount), 0) >= 250000 THEN 3
        WHEN COALESCE(SUM(o.final_amount), 0) >= 100000 THEN 2
        ELSE 1
    END as monetary_score,
    
    -- Customer Segment
    CASE 
        -- Champions: High R, F, M
        WHEN (
            CASE 
                WHEN DATEDIFF(NOW(), MAX(o.created_at)) <= 30 THEN 1 ELSE 0 
            END +
            CASE 
                WHEN COUNT(DISTINCT o.id) >= 5 THEN 1 ELSE 0 
            END +
            CASE 
                WHEN COALESCE(SUM(o.final_amount), 0) >= 500000 THEN 1 ELSE 0 
            END
        ) >= 3 THEN 'Champion'
        
        -- Loyal Customers: Good F and M, moderate R
        WHEN COUNT(DISTINCT o.id) >= 5 AND COALESCE(SUM(o.final_amount), 0) >= 250000 THEN 'Loyal'
        
        -- At Risk: Used to be good but haven't ordered recently
        WHEN COUNT(DISTINCT o.id) >= 3 
             AND COALESCE(SUM(o.final_amount), 0) >= 100000 
             AND DATEDIFF(NOW(), MAX(o.created_at)) > 30 THEN 'At Risk'
        
        -- New Customers: Low F but recent
        WHEN COUNT(DISTINCT o.id) <= 2 
             AND DATEDIFF(NOW(), MAX(o.created_at)) <= 30 THEN 'New'
        
        -- Promising: Moderate orders, room to grow
        WHEN COUNT(DISTINCT o.id) >= 3 
             AND COALESCE(SUM(o.final_amount), 0) < 500000 THEN 'Promising'
        
        -- Need Attention: Low engagement
        WHEN DATEDIFF(NOW(), MAX(o.created_at)) > 60 THEN 'Need Attention'
        
        ELSE 'Other'
    END as customer_segment,
    
    u.created_at as registration_date
FROM users u
LEFT JOIN orders o ON u.id = o.user_id AND o.payment_status = 'paid'
WHERE u.role = 'customer'
GROUP BY u.id, u.name, u.email, u.phone, u.loyalty_points, u.created_at;

-- Index for faster queries
CREATE INDEX IF NOT EXISTS idx_orders_user_payment ON orders(user_id, payment_status, created_at);
