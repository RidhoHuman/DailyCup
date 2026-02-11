-- Migration: Create materialized table for analytics_integration_messages_daily (improves read performance)

CREATE TABLE IF NOT EXISTS analytics_integration_messages_daily_mat (
  provider VARCHAR(50) NOT NULL,
  day DATE NOT NULL,
  channel VARCHAR(50) NOT NULL,
  total_messages INT DEFAULT 0,
  delivered_count INT DEFAULT 0,
  failed_count INT DEFAULT 0,
  retry_scheduled INT DEFAULT 0,
  avg_retry_count DOUBLE DEFAULT 0,
  PRIMARY KEY (provider, day, channel)
);

-- initial populate
TRUNCATE TABLE analytics_integration_messages_daily_mat;
INSERT INTO analytics_integration_messages_daily_mat (provider, day, channel, total_messages, delivered_count, failed_count, retry_scheduled, avg_retry_count)
SELECT provider, DATE(created_at) as day, channel, SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as total_messages, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count, SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) as retry_scheduled, AVG(COALESCE(retry_count,0)) as avg_retry_count
FROM integration_messages
GROUP BY provider, DATE(created_at), channel;