<?php
/**
 * Check order_items table structure
 */

require_once __DIR__ . '/../../config/database.php';

echo "===========================================\n";
echo "📋 ORDER_ITEMS TABLE STRUCTURE\n";
echo "===========================================\n\n";

// Get table structure
$result = $db->query("DESCRIBE order_items");

echo "Columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

// Check for specific columns
$checkColumns = ['price', 'unit_price', 'item_price', 'product_price'];
echo "\n";
foreach ($checkColumns as $col) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = ?");
    $stmt->bind_param("s", $col);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $exists = $res['count'] > 0 ? 'YES ✅' : 'NO ❌';
    echo "Has '$col': $exists\n";
}

echo "\n===========================================\n";
