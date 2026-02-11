<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "=== CHECKING ORDERS TABLE STRUCTURE ===\n\n";

$stmt = $db->query("DESCRIBE orders");
$columns = $stmt->fetchAll();

echo "Columns in orders table:\n";
foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']}) " . ($col['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . "\n";
}

echo "\n=== CHECKING SAMPLE ORDER ===\n";
$stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 1");
$order = $stmt->fetch();

if ($order) {
    echo "Latest Order Data:\n";
    foreach ($order as $key => $value) {
        if (!is_numeric($key)) {
            echo "- $key: " . ($value ?: 'NULL') . "\n";
        }
    }
}
