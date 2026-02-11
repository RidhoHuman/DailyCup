-- Kurir Management System
-- Create kurir table and related changes

USE dailycup_db;

-- Create kurir table
CREATE TABLE IF NOT EXISTS kurir (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL, -- For kurir login
    photo VARCHAR(255),
    vehicle_type ENUM('motor', 'mobil', 'sepeda') DEFAULT 'motor',
    vehicle_number VARCHAR(20),
    status ENUM('available', 'busy', 'offline') DEFAULT 'available',
    rating DECIMAL(3,2) DEFAULT 5.00,
    total_deliveries INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add kurir_id to orders table
ALTER TABLE orders ADD COLUMN kurir_id INT AFTER user_id;
ALTER TABLE orders ADD COLUMN assigned_at TIMESTAMP NULL AFTER kurir_id;
ALTER TABLE orders ADD COLUMN pickup_time TIMESTAMP NULL AFTER assigned_at;
ALTER TABLE orders ADD COLUMN delivery_time TIMESTAMP NULL AFTER pickup_time;

-- Add foreign key
ALTER TABLE orders ADD CONSTRAINT fk_orders_kurir 
    FOREIGN KEY (kurir_id) REFERENCES kurir(id) 
    ON DELETE SET NULL;

-- Create kurir_location table for GPS tracking (future use)
CREATE TABLE IF NOT EXISTS kurir_location (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kurir_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kurir_id) REFERENCES kurir(id) ON DELETE CASCADE,
    INDEX idx_kurir (kurir_id),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create delivery_history table
CREATE TABLE IF NOT EXISTS delivery_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    kurir_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    location_lat DECIMAL(10, 8),
    location_lng DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (kurir_id) REFERENCES kurir(id) ON DELETE CASCADE,
    INDEX idx_order (order_id),
    INDEX idx_kurir (kurir_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample kurir data
INSERT INTO kurir (name, phone, email, password, vehicle_type, vehicle_number, status) VALUES
('Budi Santoso', '081234567890', 'budi.kurir@dailycup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'motor', 'B 1234 XYZ', 'available'),
('Andi Wijaya', '081234567891', 'andi.kurir@dailycup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'motor', 'B 5678 ABC', 'available'),
('Siti Nurhaliza', '081234567892', 'siti.kurir@dailycup.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'motor', 'B 9012 DEF', 'available');

-- Password for all sample kurir: password123
