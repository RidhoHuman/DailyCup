<?php
require_once __DIR__ . '/../config/database.php';

echo "=== FIXING KURIR AUTO-ASSIGN ===\n\n";

$db = getDB();

// 1. Find orders that need kurir assignment
echo "Step 1: Finding orders without kurir...\n";
$stmt = $db->query("SELECT id, order_number, status, delivery_method 
                    FROM orders 
                    WHERE kurir_id IS NULL 
                    AND delivery_method = 'delivery'
                    AND status IN ('confirmed', 'processing', 'ready', 'delivering')
                    ORDER BY created_at ASC");
$orders = $stmt->fetchAll();

echo "Found " . count($orders) . " orders without kurir\n\n";

if (empty($orders)) {
    echo "✓ All delivery orders already have kurir assigned!\n";
    exit;
}

// 2. Get available kurir
echo "Step 2: Finding available kurir...\n";
$stmt = $db->query("SELECT id, name, phone, status 
                    FROM kurir 
                    WHERE is_active = 1 
                    ORDER BY 
                        CASE WHEN status = 'available' THEN 1 ELSE 2 END,
                        (SELECT COUNT(*) FROM orders WHERE kurir_id = kurir.id AND status IN ('confirmed', 'processing', 'ready', 'delivering')) ASC");
$kurirs = $stmt->fetchAll();

if (empty($kurirs)) {
    echo "✗ No active kurir found!\n";
    exit;
}

echo "Found " . count($kurirs) . " active kurir:\n";
foreach ($kurirs as $k) {
    echo "  - Kurir #{$k['id']}: {$k['name']} ({$k['status']})\n";
}
echo "\n";

// 3. Assign kurir to orders (round-robin)
echo "Step 3: Assigning kurir to orders...\n\n";
$kurirIndex = 0;

foreach ($orders as $order) {
    $kurir = $kurirs[$kurirIndex % count($kurirs)];
    
    $stmt = $db->prepare("UPDATE orders 
                         SET kurir_id = ?, 
                             assigned_at = NOW(),
                             updated_at = NOW()
                         WHERE id = ?");
    $stmt->execute([$kurir['id'], $order['id']]);
    
    echo "✓ Order #{$order['order_number']} → Kurir {$kurir['name']}\n";
    
    // Update kurir status to busy if was available
    if ($kurir['status'] === 'available') {
        $db->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?")->execute([$kurir['id']]);
    }
    
    $kurirIndex++;
}

echo "\n=== ASSIGNMENT COMPLETE ===\n";
echo "Total orders assigned: " . count($orders) . "\n";
echo "\nNow kurir can see orders in their dashboard!\n";
