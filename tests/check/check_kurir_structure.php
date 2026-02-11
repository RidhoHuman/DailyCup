<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "=== STRUKTUR TABEL KURIR ===\n";
$stmt = $db->query("DESCRIBE kurir");
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== DATA KURIR ===\n";
$stmt = $db->query("SELECT * FROM kurir");
while($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Phone: {$row['phone']}, Status: {$row['status']}\n";
}
?>
