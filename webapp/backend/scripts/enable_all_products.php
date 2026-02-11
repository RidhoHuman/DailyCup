<?php
require_once __DIR__ . '/../../../config/database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getPDO();
    
    echo "=== ENABLING ALL PRODUCTS FOR POS ===\n\n";
    
    // Update all products to be available
    $stmt = $pdo->prepare("UPDATE products SET is_available = 1, is_active = 1 WHERE is_active = 1");
    $stmt->execute();
    
    $count = $stmt->rowCount();
    echo "✅ Updated {$count} products to be available\n\n";
    
    // Show current status
    $stmt = $pdo->query("SELECT id, name, stock, is_available, is_active FROM products WHERE is_active = 1 ORDER BY id");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CURRENT PRODUCT STATUS ===\n";
    foreach ($products as $p) {
        $status = $p['is_available'] ? '✅ AVAILABLE' : '❌ OUT OF STOCK';
        echo "#{$p['id']} - {$p['name']}: Stock={$p['stock']}, {$status}\n";
    }
    
    echo "\n✅ All products are now available for POS!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
