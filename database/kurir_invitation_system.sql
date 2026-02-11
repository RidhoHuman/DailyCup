-- Add invitation code system for kurir recruitment
-- This ensures only authorized personnel can register as kurir

USE dailycup_db;

-- Create kurir_invitations table
CREATE TABLE IF NOT EXISTS kurir_invitations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invitation_code VARCHAR(50) UNIQUE NOT NULL,
    invited_name VARCHAR(100),
    invited_phone VARCHAR(20),
    invited_email VARCHAR(100),
    vehicle_type ENUM('motor', 'mobil', 'sepeda') DEFAULT 'motor',
    status ENUM('pending', 'used', 'expired') DEFAULT 'pending',
    created_by INT NOT NULL COMMENT 'Admin user ID who created invitation',
    used_by INT NULL COMMENT 'Kurir ID who used this invitation',
    used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT COMMENT 'Internal notes from admin',
    INDEX idx_code (invitation_code),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (used_by) REFERENCES kurir(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add invitation_code_id column to kurir table
ALTER TABLE kurir 
ADD COLUMN invitation_code_id INT NULL AFTER is_active,
ADD CONSTRAINT fk_kurir_invitation 
    FOREIGN KEY (invitation_code_id) REFERENCES kurir_invitations(id) 
    ON DELETE SET NULL;

-- Create index
ALTER TABLE kurir ADD INDEX idx_invitation (invitation_code_id);

-- Sample: Create invitation codes (admin dengan id=1)
-- Password untuk generate code: DailyCup2026

-- Note: Admin needs to create invitations through admin panel
-- Example invitation code format: DC-KURIR-XXXXX-XXXXX
