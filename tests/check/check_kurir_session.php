<?php
session_start();
require_once 'config/database.php';

$db = getDB();

echo "=== CEK SESSION KURIR ===\n";
echo "Kurir ID Session: " . ($_SESSION['kurir_id'] ?? 'NOT LOGGED IN') . "\n";
echo "Kurir Name Session: " . ($_SESSION['kurir_name'] ?? 'N/A') . "\n\n";

if (isset($_SESSION['kurir_id'])) {
    $kurirId = $_SESSION['kurir_id'];
    
    // Get kurir info
    $stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
    $stmt->execute([$kurirId]);
    $kurir = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== DATA KURIR DARI DB ===\n";
    echo "ID: " . $kurir['id'] . "\n";
    echo "Name: " . $kurir['name'] . "\n";
    echo "Username: " . $kurir['username'] . "\n\n";
}

// Check order 14
echo "=== CEK ORDER ID 14 ===\n";
$stmt = $db->prepare("SELECT id, user_id, kurir_id, status, kurir_arrived_at, pickup_time FROM orders WHERE id = ?");
$stmt->execute([14]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Order ID: " . $order['id'] . "\n";
echo "Kurir ID: " . $order['kurir_id'] . "\n";
echo "Status: " . $order['status'] . "\n";
echo "Kurir Arrived: " . ($order['kurir_arrived_at'] ?? 'NULL') . "\n";
echo "Pickup Time: " . ($order['pickup_time'] ?? 'NULL') . "\n\n";

// Check if match
if (isset($_SESSION['kurir_id'])) {
    $match = ($order['kurir_id'] == $_SESSION['kurir_id']);
    echo "=== MATCH CHECK ===\n";
    echo "Order kurir_id (" . $order['kurir_id'] . ") == Session kurir_id (" . $_SESSION['kurir_id'] . "): " . ($match ? 'YES ✓' : 'NO ✗') . "\n\n";
    
    if ($match) {
        $canArriveAtStore = (($order['status'] == 'processing' || $order['status'] == 'ready') && !$order['kurir_arrived_at']);
        echo "=== BUTTON CHECK ===\n";
        echo "Can show 'Tiba di Toko' button: " . ($canArriveAtStore ? 'YES ✓' : 'NO ✗') . "\n";
    }
}

// Get kurir name from order
$stmt = $db->prepare("SELECT k.name FROM kurir k JOIN orders o ON k.id = o.kurir_id WHERE o.id = ?");
$stmt->execute([14]);
$kurirName = $stmt->fetchColumn();
echo "\n=== INFO ===\n";
echo "Order 14 assigned to: " . $kurirName . "\n";
?>
