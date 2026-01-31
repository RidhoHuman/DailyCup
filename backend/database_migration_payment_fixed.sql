-- Database Migration for Payment Integration (InfinityFree Compatible)
-- Run this to add required columns for payment gateway integration

-- Add payment-related columns one by one (IF NOT EXISTS tidak didukung di MySQL <8.0)
ALTER TABLE orders 
ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER status;

ALTER TABLE orders 
ADD COLUMN midtrans_response TEXT NULL AFTER payment_status;

ALTER TABLE orders 
ADD COLUMN xendit_response TEXT NULL AFTER midtrans_response;

-- Add index for faster lookups
ALTER TABLE orders
ADD INDEX idx_payment_status (payment_status);

ALTER TABLE orders
ADD INDEX idx_order_number (order_number);
