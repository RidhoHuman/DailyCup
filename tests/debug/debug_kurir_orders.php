<?php
require_once __DIR__ . '/../config/database.php';

echo "=== DEBUGGING KURIR ORDER SYSTEM ===\n\n";

$db = getDB();

// 1. Check recent orders
echo "=== RECENT ORDERS (Last 5) ===\n";
$stmt = $db->query("SELECT id, order_number, user_id, kurir_id, status, delivery_type, final_amount, created_at 
                    FROM orders 
                    ORDER BY created_at DESC 
                    LIMIT 5");
$orders = $stmt->fetchAll();

foreach ($orders as $order) {
    echo "Order #{$order['id']} - {$order['order_number']}\n";
    echo "  Status: {$order['status']}\n";
    echo "  Kurir ID: " . ($order['kurir_id'] ?: 'NOT ASSIGNED') . "\n";
    echo "  Delivery Type: {$order['delivery_type']}\n";
    echo "  Amount: Rp " . number_format($order['final_amount']) . "\n";
    echo "  Created: {$order['created_at']}\n";
    echo "  ---\n";
}

// 2. Check kurir status
echo "\n=== KURIR STATUS ===\n";
$stmt = $db->query("SELECT id, name, phone, status, is_active, 
                    (SELECT COUNT(*) FROM orders WHERE kurir_id = kurir.id AND status IN ('confirmed', 'processing', 'ready', 'delivering')) as active_orders
                    FROM kurir");
$kurirs = $stmt->fetchAll();

foreach ($kurirs as $kurir) {
    echo "Kurir #{$kurir['id']} - {$kurir['name']}\n";
    echo "  Phone: {$kurir['phone']}\n";
    echo "  Status: {$kurir['status']}\n";
    echo "  Active: " . ($kurir['is_active'] ? 'Yes' : 'No') . "\n";
    echo "  Active Orders: {$kurir['active_orders']}\n";
    echo "  ---\n";
}

// 3. Check orders that should be visible to kurir but aren't
echo "\n=== ORDERS ASSIGNED TO KURIR (Should be visible) ===\n";
$stmt = $db->query("SELECT o.id, o.order_number, o.kurir_id, k.name as kurir_name, o.status, o.delivery_type
                    FROM orders o
                    LEFT JOIN kurir k ON o.kurir_id = k.id
                    WHERE o.kurir_id IS NOT NULL
                    AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                    ORDER BY o.created_at DESC");
$assignedOrders = $stmt->fetchAll();

if (empty($assignedOrders)) {
    echo "⚠️ NO ORDERS ASSIGNED TO ANY KURIR!\n";
    echo "This means auto-assign is not working.\n";
} else {
    foreach ($assignedOrders as $order) {
        echo "Order #{$order['id']} - {$order['order_number']}\n";
        echo "  Assigned to: Kurir #{$order['kurir_id']} - {$order['kurir_name']}\n";
        echo "  Status: {$order['status']}\n";
        echo "  Type: {$order['delivery_type']}\n";
        echo "  ---\n";
    }
}

// 4. Check orders that need kurir assignment
echo "\n=== ORDERS NEEDING KURIR ASSIGNMENT ===\n";
$stmt = $db->query("SELECT id, order_number, status, delivery_type, created_at
                    FROM orders 
                    WHERE kurir_id IS NULL 
                    AND delivery_type = 'delivery'
                    AND status IN ('confirmed', 'processing', 'ready')
                    ORDER BY created_at DESC
                    LIMIT 10");
$unassigned = $stmt->fetchAll();

if (empty($unassigned)) {
    echo "✓ No unassigned delivery orders\n";
} else {
    echo "⚠️ Found " . count($unassigned) . " unassigned orders:\n";
    foreach ($unassigned as $order) {
        echo "Order #{$order['id']} - {$order['order_number']}\n";
        echo "  Status: {$order['status']}\n";
        echo "  Created: {$order['created_at']}\n";
        echo "  ---\n";
    }
}

// 5. Diagnostic
echo "\n=== DIAGNOSTIC ===\n";

$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$deliveryOrders = $db->query("SELECT COUNT(*) FROM orders WHERE delivery_type = 'delivery'")->fetchColumn();
$assignedOrders = $db->query("SELECT COUNT(*) FROM orders WHERE kurir_id IS NOT NULL")->fetchColumn();
$activeKurir = $db->query("SELECT COUNT(*) FROM kurir WHERE is_active = 1")->fetchColumn();
$availableKurir = $db->query("SELECT COUNT(*) FROM kurir WHERE is_active = 1 AND status = 'available'")->fetchColumn();

echo "Total Orders: $totalOrders\n";
echo "Delivery Orders: $deliveryOrders\n";
echo "Orders with Kurir: $assignedOrders\n";
echo "Active Kurir: $activeKurir\n";
echo "Available Kurir: $availableKurir\n";

if ($deliveryOrders > 0 && $assignedOrders == 0) {
    echo "\n⚠️ PROBLEM DETECTED:\n";
    echo "- You have delivery orders\n";
    echo "- But NO orders have been assigned to kurir\n";
    echo "- AUTO-ASSIGN IS NOT WORKING!\n";
    echo "\nPossible causes:\n";
    echo "1. Auto-assign function not called after order confirmation\n";
    echo "2. All kurir status is 'busy' or 'offline' (need 'available')\n";
    echo "3. Order status flow skipped 'confirmed' stage\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
