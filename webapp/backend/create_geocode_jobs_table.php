<?php
/**
 * Create geocode_jobs table for async geocoding
 * Run: php create_geocode_jobs_table.php
 */
require_once __DIR__ . '/config/database.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS geocode_jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status ENUM('pending','processing','done','failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        last_error TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (order_id),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "geocode_jobs table created or already exists.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
