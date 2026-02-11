<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Get categories with product count (active products only)
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name as category_name,
            c.is_active as category_active,
            COUNT(p.id) as active_product_count,
            GROUP_CONCAT(p.name SEPARATOR ', ') as products
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        GROUP BY c.id, c.name, c.is_active
        ORDER BY c.id
    ");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== CATEGORY ANALYSIS ===\n\n";
    
    foreach ($categories as $cat) {
        echo "Category ID: {$cat['id']}\n";
        echo "Name: {$cat['category_name']}\n";
        echo "Active: " . ($cat['category_active'] ? 'YES' : 'NO (DELETED)') . "\n";
        echo "Active Products: {$cat['active_product_count']}\n";
        echo "Products: " . ($cat['products'] ?: 'NONE') . "\n";
        echo "-----------------------------------\n\n";
    }
    
    // Check all products with their categories
    echo "\n=== ALL PRODUCTS (including deleted) ===\n\n";
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.name as product_name,
            p.category_id,
            c.name as category_name,
            p.is_active as product_active
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id
    ");
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $prod) {
        echo "Product ID: {$prod['id']}\n";
        echo "Name: {$prod['product_name']}\n";
        echo "Category: {$prod['category_name']} (ID: {$prod['category_id']})\n";
        echo "Active: " . ($prod['product_active'] ? 'YES' : 'NO (DELETED)') . "\n";
        echo "-----------------------------------\n\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>