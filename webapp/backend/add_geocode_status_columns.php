<?php
/**
 * Add geocode_status, geocode_error, geocode_attempts to orders
 * Run: php add_geocode_status_columns.php
 */
require_once __DIR__ . '/config/database.php';
try {
    $queries = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'geocode_status'");
    if (!$stmt->fetch()) {
        $queries[] = "ALTER TABLE orders ADD COLUMN geocode_status VARCHAR(20) DEFAULT 'pending' AFTER geocode_raw";
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'geocode_error'");
    if (!$stmt->fetch()) {
        $queries[] = "ALTER TABLE orders ADD COLUMN geocode_error TEXT NULL AFTER geocode_status";
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'geocode_attempts'");
    if (!$stmt->fetch()) {
        $queries[] = "ALTER TABLE orders ADD COLUMN geocode_attempts INT DEFAULT 0 AFTER geocode_error";
    }

    if (count($queries) === 0) {
        echo "No changes required. Columns already exist.\n";
        exit;
    }

    foreach ($queries as $q) {
        $pdo->exec($q);
        echo "Executed: $q\n";
    }
    echo "Migration completed.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
