<?php
require_once 'config/database.php';

$db = getDB();
$orderId = 14;

$stmt = $db->prepare("SELECT id, status, kurir_arrived_at, pickup_time, delivery_time FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Order ID: " . $order['id'] . "\n";
echo "Status: " . $order['status'] . "\n";
echo "Kurir Arrived: " . ($order['kurir_arrived_at'] ?? 'NULL') . "\n";
echo "Pickup Time: " . ($order['pickup_time'] ?? 'NULL') . "\n";
echo "Delivery Time: " . ($order['delivery_time'] ?? 'NULL') . "\n";

echo "\n--- Kondisi Tombol ---\n";
$canArriveAtStore = ($order['status'] == 'processing' && !$order['kurir_arrived_at']);
$canPickup = ($order['status'] == 'ready' && $order['kurir_arrived_at']);
$canComplete = ($order['status'] == 'delivering' && $order['pickup_time']);

echo "Can Arrive At Store: " . ($canArriveAtStore ? 'YES' : 'NO') . "\n";
echo "Can Pickup: " . ($canPickup ? 'YES' : 'NO') . "\n";
echo "Can Complete: " . ($canComplete ? 'YES' : 'NO') . "\n";
?>
