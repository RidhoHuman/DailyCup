<?php
require_once __DIR__ . '/../../../config/database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getPDO();
    
    echo "=== PRODUCT STOCK STATUS ===\n\n";
    
    $stmt = $pdo->query("SELECT id, name, stock, is_active FROM products WHERE is_active=1 ORDER BY id");
    
    while($r = $stmt->fetch()) {
        $status = $r['stock'] > 0 ? '✅' : '❌';
        echo "{$status} ID: {$r['id']} - {$r['name']}: Stock={$r['stock']}\n";
    }
    
    echo "\n";
    $total = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active=1 AND stock > 0")->fetch();
    echo "Total products available: {$total['total']}\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
