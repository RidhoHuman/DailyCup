<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$stmt = $db->query("SELECT id, name, phone, email, is_active FROM kurir");

echo "=== KURIR ACCOUNTS ===\n";
while($row = $stmt->fetch()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Phone: " . $row['phone'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Active: " . ($row['is_active'] ? 'Yes' : 'No') . "\n";
    echo "------------------------\n";
}
