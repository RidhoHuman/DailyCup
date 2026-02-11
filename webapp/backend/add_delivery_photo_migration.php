<?php
// add_delivery_photo_migration.php
$mysqli = new mysqli('localhost', 'root', '', 'dailycup_db');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Running migration: Add delivery_photo column\n\n";

// First check if column exists
$checkResult = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'delivery_photo'");
if ($checkResult && $checkResult->num_rows > 0) {
    echo "✓ Column delivery_photo already exists\n";
} else {
    // Add column
    $sql = "ALTER TABLE orders ADD COLUMN delivery_photo VARCHAR(500) NULL COMMENT 'Path to delivery confirmation photo' AFTER delivery_time";
    
    if ($mysqli->query($sql)) {
        echo "✓ Success: delivery_photo column added\n";
    } else {
        echo "✗ Error: " . $mysqli->error . "\n";
        exit(1);
    }
}

// Verify
$result = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'delivery_photo'");
if ($result && $result->num_rows > 0) {
    echo "✓ Verified: delivery_photo column exists in orders table\n";
    $row = $result->fetch_assoc();
    echo "  Type: " . $row['Type'] . "\n";
    echo "  Null: " . $row['Null'] . "\n";
} else {
    echo "✗ Warning: Column not found after migration\n";
}

$mysqli->close();
echo "\nMigration completed!\n";
