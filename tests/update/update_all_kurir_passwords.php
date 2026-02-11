<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$stmt = $db->query("SELECT id, name, phone FROM kurir");
$kurirs = $stmt->fetchAll();

$new_password = 'password123';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "=== UPDATING ALL KURIR PASSWORDS ===\n\n";

foreach ($kurirs as $kurir) {
    $update = $db->prepare("UPDATE kurir SET password = ? WHERE id = ?");
    $update->execute([$new_hash, $kurir['id']]);
    echo "✓ Updated: " . $kurir['name'] . " (Phone: " . $kurir['phone'] . ")\n";
}

echo "\n=== DONE ===\n";
echo "All kurir passwords have been set to: password123\n";
