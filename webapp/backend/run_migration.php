<?php
$pdo = new PDO('mysql:host=localhost;dbname=dailycup_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cols = [];
$result = $pdo->query("SHOW COLUMNS FROM orders");
foreach($result as $r) { $cols[] = $r['Field']; }

$migrations = [
    ['kurir_departure_photo', "ALTER TABLE orders ADD COLUMN kurir_departure_photo VARCHAR(500) NULL AFTER delivery_time"],
    ['kurir_arrival_photo', "ALTER TABLE orders ADD COLUMN kurir_arrival_photo VARCHAR(500) NULL AFTER kurir_departure_photo"],
    ['kurir_arrived_at', "ALTER TABLE orders ADD COLUMN kurir_arrived_at DATETIME NULL AFTER kurir_arrival_photo"],
    ['actual_delivery_time', "ALTER TABLE orders ADD COLUMN actual_delivery_time INT NULL AFTER kurir_arrived_at"],
];

foreach ($migrations as [$col, $sql]) {
    if (!in_array($col, $cols)) {
        $pdo->exec($sql);
        echo "Added: $col\n";
    } else {
        echo "Exists: $col\n";
    }
}

// Create delivery uploads directory
$uploadDir = __DIR__ . '/uploads/delivery/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "Created: uploads/delivery/\n";
} else {
    echo "Exists: uploads/delivery/\n";
}

echo "\nMigration complete!\n";
