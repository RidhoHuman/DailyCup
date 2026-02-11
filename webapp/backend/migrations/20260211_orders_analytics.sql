-- Orders/Revenue analytics views

CREATE OR REPLACE VIEW analytics_orders_daily AS
SELECT
  DATE(created_at) AS day,
  COUNT(*) AS total_orders,
  SUM(total_amount) AS total_revenue,
  AVG(total_amount) AS avg_order_value
FROM orders
WHERE status IN ('completed','delivered')
GROUP BY DATE(created_at);

CREATE OR REPLACE VIEW analytics_orders_summary AS
SELECT
  SUM(total_amount) AS total_revenue,
  COUNT(*) AS total_orders,
  AVG(total_amount) AS avg_order_value
FROM orders
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status IN ('completed','delivered');