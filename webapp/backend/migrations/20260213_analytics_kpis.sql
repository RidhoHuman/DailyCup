-- Migration (moved): Create analytics views for integrations (moved to 20260213 to ensure dependencies)

-- Daily aggregates per provider/channel
CREATE OR REPLACE VIEW analytics_integration_messages_daily AS
SELECT
  DATE(created_at) AS day,
  provider,
  channel,
  SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) AS total_messages,
  SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_count,
  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
  SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) AS retry_scheduled,
  AVG(COALESCE(retry_count,0)) AS avg_retry_count
FROM integration_messages
GROUP BY DATE(created_at), provider, channel;

-- Recent aggregates (last 24 hours)
CREATE OR REPLACE VIEW analytics_integration_messages_recent AS
SELECT
  provider,
  SUM(CASE WHEN created_at >= (NOW() - INTERVAL 1 DAY) AND direction = 'outbound' THEN 1 ELSE 0 END) AS sent_last_24h,
  SUM(CASE WHEN created_at >= (NOW() - INTERVAL 1 DAY) AND status = 'failed' AND direction = 'outbound' THEN 1 ELSE 0 END) AS failed_last_24h,
  SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) AS retry_scheduled_total,
  AVG(COALESCE(retry_count,0)) AS avg_retry_count
FROM integration_messages
GROUP BY provider;