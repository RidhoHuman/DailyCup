<?php
require_once 'config/database.php';

$db = getDB();

// Fix order 15 status
$stmt = $db->prepare("UPDATE orders SET status = 'ready' WHERE id = 15");
$stmt->execute();

echo "✅ Order 15 status updated to 'ready'!\n";
echo "Refresh halaman order_detail.php?id=15\n";
?>
