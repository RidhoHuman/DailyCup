<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();
$stmt = $db->prepare("SELECT id, name, phone, password FROM kurir WHERE phone = ?");
$stmt->execute(['081234567890']);
$kurir = $stmt->fetch();

if ($kurir) {
    echo "=== KURIR FOUND ===\n";
    echo "ID: " . $kurir['id'] . "\n";
    echo "Name: " . $kurir['name'] . "\n";
    echo "Phone: " . $kurir['phone'] . "\n";
    echo "Password Hash: " . $kurir['password'] . "\n\n";
    
    // Test password verification
    $test_password = 'password123';
    $verify_result = password_verify($test_password, $kurir['password']);
    echo "Password Verify Test: " . ($verify_result ? 'SUCCESS' : 'FAILED') . "\n";
    
    if (!$verify_result) {
        echo "\n=== FIXING PASSWORD ===\n";
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE kurir SET password = ? WHERE id = ?");
        $update->execute([$new_hash, $kurir['id']]);
        echo "Password updated successfully!\n";
        echo "New Hash: " . $new_hash . "\n";
    }
} else {
    echo "Kurir not found!\n";
}
