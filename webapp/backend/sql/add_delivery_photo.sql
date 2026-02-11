-- Migration: Add delivery photo confirmation system
-- Created: 2026-02-10
-- Description: Add photo proof of delivery feature for kurir

-- Add delivery_photo column to orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS delivery_photo VARCHAR(500) NULL COMMENT 'Path to delivery confirmation photo' AFTER delivery_time;

-- Add index for faster queries
CREATE INDEX IF NOT EXISTS idx_orders_delivery_photo ON orders(delivery_photo);

-- Update existing completed orders (optional - set NULL explicitly)
-- UPDATE orders SET delivery_photo = NULL WHERE status = 'completed' AND delivery_photo IS NULL;
