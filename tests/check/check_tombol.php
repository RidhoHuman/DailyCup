<?php
session_start();
require_once 'config/database.php';

// Get order ID from URL
$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    echo "<h1>Error: Tidak ada Order ID</h1>";
    echo "<p>Gunakan URL: check_tombol.php?id=ORDER_ID</p>";
    exit;
}

$db = getDB();

echo "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "<h1>🔍 DEBUG TOMBOL - Order #$orderId</h1><hr>";

// 1. Check session
echo "<h2>1️⃣ SESSION CHECK</h2>";
if (isset($_SESSION['kurir_id'])) {
    echo "<p class='success'>✅ Kurir ID: " . $_SESSION['kurir_id'] . "</p>";
    echo "<p class='success'>✅ Kurir Name: " . $_SESSION['kurir_name'] . "</p>";
} else {
    echo "<p class='error'>❌ TIDAK LOGIN! Session kosong.</p>";
    echo "<p>Login dulu di: <a href='/DailyCup/kurir/login.php'>kurir/login.php</a></p>";
    exit;
}

$kurirId = $_SESSION['kurir_id'];

// 2. Check order
echo "<h2>2️⃣ ORDER CHECK</h2>";
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<p class='error'>❌ Order tidak ditemukan!</p>";
    exit;
}

echo "<p>Order ID: <strong>" . $order['id'] . "</strong></p>";
echo "<p>Status: <strong class='" . ($order['status'] == 'cancelled' ? 'error' : 'success') . "'>" . $order['status'] . "</strong></p>";
echo "<p>Kurir ID di Order: <strong>" . ($order['kurir_id'] ?? 'NULL') . "</strong></p>";
echo "<p>Kurir Arrived At: <strong>" . ($order['kurir_arrived_at'] ?? 'NULL') . "</strong></p>";
echo "<p>Pickup Time: <strong>" . ($order['pickup_time'] ?? 'NULL') . "</strong></p>";
echo "<p>Delivery Time: <strong>" . ($order['delivery_time'] ?? 'NULL') . "</strong></p>";

// 3. Check match
echo "<h2>3️⃣ MATCH CHECK</h2>";
if ($order['kurir_id'] != $kurirId) {
    echo "<p class='error'>❌ Order ini bukan untuk Anda!</p>";
    echo "<p>Order assigned ke kurir ID: " . $order['kurir_id'] . "</p>";
    echo "<p>Anda login sebagai kurir ID: " . $kurirId . "</p>";
    exit;
} else {
    echo "<p class='success'>✅ Order ini untuk Anda</p>";
}

// 4. Check conditions (SAMA SEPERTI DI order_detail.php)
echo "<h2>4️⃣ BUTTON CONDITIONS</h2>";

$kurirArrived = $order['kurir_arrived_at'] ? new DateTime($order['kurir_arrived_at']) : null;
$pickupTime = $order['pickup_time'] ? new DateTime($order['pickup_time']) : null;

$canArriveAtStore = ($order['status'] == 'processing' || $order['status'] == 'ready') && !$kurirArrived;
$canPickup = ($order['status'] == 'ready' || $order['status'] == 'delivering') && $kurirArrived;
$canComplete = $order['status'] == 'delivering' && $pickupTime;

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Kondisi</th><th>Status</th><th>Penjelasan</th></tr>";

// Tombol 1: Tiba di Toko
echo "<tr>";
echo "<td><strong>Tombol: Tiba di Toko</strong></td>";
echo "<td class='" . ($canArriveAtStore ? 'success' : 'error') . "'>" . ($canArriveAtStore ? '✅ MUNCUL' : '❌ TIDAK') . "</td>";
echo "<td>Status = processing/ready (" . $order['status'] . ") DAN belum tiba (" . ($kurirArrived ? 'sudah' : 'belum') . ")</td>";
echo "</tr>";

// Tombol 2: Ambil Pesanan
echo "<tr>";
echo "<td><strong>Tombol: Ambil & Berangkat</strong></td>";
echo "<td class='" . ($canPickup ? 'success' : 'error') . "'>" . ($canPickup ? '✅ MUNCUL' : '❌ TIDAK') . "</td>";
echo "<td>Status = ready/delivering (" . $order['status'] . ") DAN sudah tiba (" . ($kurirArrived ? 'sudah' : 'belum') . ")</td>";
echo "</tr>";

// Tombol 3: Selesai
echo "<tr>";
echo "<td><strong>Tombol: Sudah Sampai</strong></td>";
echo "<td class='" . ($canComplete ? 'success' : 'error') . "'>" . ($canComplete ? '✅ MUNCUL' : '❌ TIDAK') . "</td>";
echo "<td>Status = delivering (" . $order['status'] . ") DAN sudah pickup (" . ($pickupTime ? 'sudah' : 'belum') . ")</td>";
echo "</tr>";

echo "</table>";

// 5. Kesimpulan
echo "<h2>5️⃣ KESIMPULAN</h2>";
if ($canArriveAtStore) {
    echo "<p class='success'>✅ Seharusnya muncul tombol: <strong>Saya Sudah Tiba di Toko</strong></p>";
} elseif ($canPickup) {
    echo "<p class='success'>✅ Seharusnya muncul tombol: <strong>Ambil & Berangkat (Upload Foto)</strong></p>";
} elseif ($canComplete) {
    echo "<p class='success'>✅ Seharusnya muncul tombol: <strong>Sudah Sampai (Upload Foto)</strong></p>";
} else {
    echo "<p class='warning'>⚠️ TIDAK ADA TOMBOL YANG SEHARUSNYA MUNCUL</p>";
    echo "<p>Kemungkinan:</p>";
    echo "<ul>";
    if ($order['status'] == 'cancelled') {
        echo "<li>Order sudah dibatalkan</li>";
    } elseif ($order['status'] == 'completed') {
        echo "<li>Order sudah selesai</li>";
    } else {
        echo "<li>Status order: " . $order['status'] . " (tidak sesuai flow)</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='/DailyCup/kurir/order_detail.php?id=$orderId'>➡️ Buka Order Detail</a></p>";
echo "<p><a href='/DailyCup/kurir/index.php'>➡️ Kembali ke Dashboard</a></p>";
?>
