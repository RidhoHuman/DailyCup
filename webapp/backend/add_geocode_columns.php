<?php
/**
 * Add geocoding columns to orders table if missing
 * Run: php add_geocode_columns.php
 */
require_once __DIR__ . '/config/database.php';
try {
    $queries = [];
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'geocoded_at'");
    if (!$stmt->fetch()) {
        $queries[] = "ALTER TABLE orders ADD COLUMN geocoded_at DATETIME NULL AFTER delivery_lng";
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'geocode_raw'");
    if (!$stmt->fetch()) {
        $queries[] = "ALTER TABLE orders ADD COLUMN geocode_raw TEXT NULL AFTER geocoded_at";
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
