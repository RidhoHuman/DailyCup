<?php
require_once 'config/database.php';

$db = getDB();
$orderId = 15;

$stmt = $db->prepare("SELECT id, status, kurir_arrived_at, pickup_time, delivery_time FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== STATUS ORDER #15 ===\n";
echo "Status: " . $order['status'] . "\n";
echo "Kurir Arrived: " . ($order['kurir_arrived_at'] ?? 'NULL') . "\n";
echo "Pickup Time: " . ($order['pickup_time'] ?? 'NULL') . "\n";
echo "Delivery Time: " . ($order['delivery_time'] ?? 'NULL') . "\n\n";

$pickupTime = $order['pickup_time'] ? new DateTime($order['pickup_time']) : null;
$canComplete = ($order['status'] == 'delivering' && $pickupTime);

echo "=== BUTTON CHECK ===\n";
echo "Status = 'delivering'? " . ($order['status'] == 'delivering' ? 'YES' : 'NO (' . $order['status'] . ')') . "\n";
echo "Pickup Time exists? " . ($pickupTime ? 'YES' : 'NO') . "\n";
echo "Can show 'Sudah Sampai' button? " . ($canComplete ? 'YES' : 'NO') . "\n\n";

if (!$canComplete) {
    echo "=== MASALAH ===\n";
    if ($order['status'] != 'delivering') {
        echo "❌ Status masih '" . $order['status'] . "', seharusnya 'delivering'\n";
        echo "Fix: UPDATE orders SET status='delivering' WHERE id=15\n";
    }
    if (!$pickupTime) {
        echo "❌ Pickup time masih NULL, seharusnya sudah ada\n";
    }
}
?>
