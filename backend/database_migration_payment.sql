-- Database Migration for Payment Integration
-- Run this to add required columns for payment gateway integration

USE dailycup_db;

-- Add payment-related columns to orders table if they don't exist
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER status,
ADD COLUMN IF NOT EXISTS midtrans_response TEXT NULL AFTER payment_status,
ADD COLUMN IF NOT EXISTS xendit_response TEXT NULL AFTER midtrans_response;

-- Add index for faster lookups
ALTER TABLE orders
ADD INDEX IF NOT EXISTS idx_payment_status (payment_status),
ADD INDEX IF NOT EXISTS idx_order_number (order_number);

-- Check current structure
DESC orders;

-- Test query
SELECT id, order_number, status, payment_status, created_at 
FROM orders 
ORDER BY created_at DESC 
LIMIT 5;
