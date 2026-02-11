-- COD (Cash on Delivery) Tracking System
-- This table stores COD-specific tracking information for orders

CREATE TABLE IF NOT EXISTS `cod_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL UNIQUE,
    
    -- Delivery tracking
    `courier_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nama kurir yang mengantar',
    `courier_phone` VARCHAR(20) DEFAULT NULL COMMENT 'Nomor telepon kurir',
    `tracking_number` VARCHAR(100) DEFAULT NULL COMMENT 'Nomor resi pengiriman',
    
    -- Delivery status timeline
    `status` ENUM(
        'pending',          -- Menunggu konfirmasi
        'confirmed',        -- Dikonfirmasi, siap dikirim
        'packed',          -- Barang sudah dikemas
        'out_for_delivery', -- Dalam perjalanan pengiriman
        'delivered',        -- Sudah diterima pelanggan
        'payment_received', -- Pembayaran sudah diterima
        'cancelled'         -- Dibatalkan
    ) DEFAULT 'pending',
    
    -- Payment tracking
    `payment_received` TINYINT(1) DEFAULT 0 COMMENT 'Apakah uang sudah diterima (0=belum, 1=sudah)',
    `payment_received_at` DATETIME DEFAULT NULL COMMENT 'Waktu pembayaran diterima',
    `payment_amount` DECIMAL(12,2) DEFAULT NULL COMMENT 'Jumlah yang dibayarkan',
    `payment_notes` TEXT DEFAULT NULL COMMENT 'Catatan pembayaran (kelebihan/kekurangan)',
    
    -- Delivery verification
    `receiver_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nama penerima barang',
    `receiver_relation` VARCHAR(50) DEFAULT NULL COMMENT 'Hubungan dengan pemesan (sendiri/keluarga/teman)',
    `delivery_photo_url` VARCHAR(255) DEFAULT NULL COMMENT 'Foto bukti pengiriman',
    `signature_url` VARCHAR(255) DEFAULT NULL COMMENT 'Tanda tangan penerima',
    
    -- Timestamps for each status
    `confirmed_at` DATETIME DEFAULT NULL,
    `packed_at` DATETIME DEFAULT NULL,
    `out_for_delivery_at` DATETIME DEFAULT NULL,
    `delivered_at` DATETIME DEFAULT NULL,
    
    -- Administrative
    `notes` TEXT DEFAULT NULL COMMENT 'Catatan internal admin',
    `admin_notes` TEXT DEFAULT NULL COMMENT 'Catatan khusus admin',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_payment_received` (`payment_received`),
    INDEX `idx_courier_name` (`courier_name`),
    INDEX `idx_created_at` (`created_at`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COD Status History for audit trail
CREATE TABLE IF NOT EXISTS `cod_status_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(50) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `changed_by_user_id` INT DEFAULT NULL COMMENT 'Admin/kurir yang mengubah status',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_created_at` (`created_at`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
