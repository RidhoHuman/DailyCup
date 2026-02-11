<?php
require_once __DIR__ . '/../config/database.php';

echo "=== TESTING DATABASE CONNECTION & STRUCTURE ===\n\n";

try {
    $db = getDB();
    echo "✓ Database connection: SUCCESS\n\n";
    
    // Test all tables exist
    $tables = [
        'users', 'categories', 'products', 'orders', 'order_items',
        'favorites', 'reviews', 'notifications', 'discounts', 'redeem_codes',
        'loyalty_transactions', 'refunds', 'kurir', 'kurir_location', 'delivery_history'
    ];
    
    echo "=== CHECKING TABLES ===\n";
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "✓ $table - $count records\n";
        } else {
            echo "✗ $table - NOT FOUND\n";
        }
    }
    
    echo "\n=== CHECKING CRITICAL COLUMNS ===\n";
    
    // Check orders table has kurir columns
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'kurir_id'");
    echo $stmt->rowCount() > 0 ? "✓ orders.kurir_id exists\n" : "✗ orders.kurir_id MISSING\n";
    
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'pickup_time'");
    echo $stmt->rowCount() > 0 ? "✓ orders.pickup_time exists\n" : "✗ orders.pickup_time MISSING\n";
    
    $stmt = $db->query("SHOW COLUMNS FROM orders LIKE 'delivery_time'");
    echo $stmt->rowCount() > 0 ? "✓ orders.delivery_time exists\n" : "✗ orders.delivery_time MISSING\n";
    
    // Check users table has refund columns
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'refund_count'");
    echo $stmt->rowCount() > 0 ? "✓ users.refund_count exists\n" : "✗ users.refund_count MISSING\n";
    
    echo "\n=== CHECKING INDEXES ===\n";
    $indexes = $db->query("SHOW INDEX FROM orders")->fetchAll();
    $hasKurirIndex = false;
    foreach ($indexes as $index) {
        if ($index['Column_name'] === 'kurir_id') {
            $hasKurirIndex = true;
            break;
        }
    }
    echo $hasKurirIndex ? "✓ orders.kurir_id indexed\n" : "⚠ orders.kurir_id not indexed (consider adding)\n";
    
    echo "\n=== TESTING DATA INTEGRITY ===\n";
    
    // Check orphaned orders (orders without valid user)
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE user_id NOT IN (SELECT id FROM users)");
    $orphaned = $stmt->fetchColumn();
    echo $orphaned == 0 ? "✓ No orphaned orders\n" : "⚠ Found $orphaned orphaned orders\n";
    
    // Check kurir with no location data
    $stmt = $db->query("SELECT COUNT(*) FROM kurir k LEFT JOIN kurir_location kl ON k.id = kl.kurir_id WHERE kl.id IS NULL");
    $noLocation = $stmt->fetchColumn();
    echo "ℹ $noLocation kurir(s) without GPS location data\n";
    
    echo "\n=== SAMPLE DATA CHECK ===\n";
    
    $activeUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $activeUsers\n";
    
    $products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "Products: $products\n";
    
    $orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    echo "Orders: $orders\n";
    
    $kurirs = $db->query("SELECT COUNT(*) FROM kurir WHERE is_active = 1")->fetchColumn();
    echo "Active Kurir: $kurirs\n";
    
    $reviews = $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    echo "Reviews: $reviews\n";
    
    $refunds = $db->query("SELECT COUNT(*) FROM refunds")->fetchColumn();
    echo "Refunds: $refunds\n";
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "Status: " . ($orphaned == 0 ? "PASSED ✓" : "WARNINGS FOUND ⚠") . "\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection FAILED: " . $e->getMessage() . "\n";
}
