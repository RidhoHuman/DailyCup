-- Add columns for preparation time and tracking
ALTER TABLE orders 
ADD COLUMN preparation_time INT DEFAULT 30 COMMENT 'Waktu persiapan dalam menit',
ADD COLUMN estimated_ready_at DATETIME NULL COMMENT 'Estimasi waktu pesanan siap',
ADD COLUMN kurir_arrived_at DATETIME NULL COMMENT 'Waktu kurir tiba di toko',
ADD COLUMN kurir_departure_photo VARCHAR(255) NULL COMMENT 'Foto bukti keberangkatan',
ADD COLUMN kurir_arrival_photo VARCHAR(255) NULL COMMENT 'Foto bukti sampai ke customer',
ADD COLUMN actual_delivery_time INT NULL COMMENT 'Waktu delivery aktual dalam menit';

-- Create table for admin/store notifications
CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'new_order, order_ready, kurir_arrived, etc',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_read (is_read),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update delivery_history to include photos
ALTER TABLE delivery_history
ADD COLUMN photo VARCHAR(255) NULL COMMENT 'Foto bukti untuk setiap status',
ADD COLUMN latitude DECIMAL(10,8) NULL COMMENT 'GPS latitude saat update',
ADD COLUMN longitude DECIMAL(11,8) NULL COMMENT 'GPS longitude saat update';
