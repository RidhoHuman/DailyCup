<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>✅ Test Notifikasi Kurir</h2>";
echo "<hr>";

$db = getDB();

// 1. Verify kurir_notifications table
echo "<h3>1. Cek Tabel kurir_notifications:</h3>";
$stmt = $db->query("SELECT COUNT(*) FROM kurir_notifications");
$count = $stmt->fetchColumn();
echo "<p>✅ Total notifikasi: <strong>$count</strong></p>";

// 2. Check unread notifications for kurir #1
echo "<h3>2. Notifikasi Unread untuk Kurir Budi Santoso (ID=1):</h3>";
$stmt = $db->prepare("SELECT * FROM kurir_notifications WHERE kurir_id = 1 AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute();
$unread = $stmt->fetchAll();

if (count($unread) > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    echo "<p><strong>✅ Ada " . count($unread) . " notifikasi belum dibaca:</strong></p>";
    foreach ($unread as $n) {
        echo "<div style='background: white; padding: 10px; margin-bottom: 10px; border-radius: 5px;'>";
        echo "<h5>{$n['title']}</h5>";
        echo "<p>{$n['message']}</p>";
        echo "<small>Type: {$n['type']} | Created: {$n['created_at']}</small>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p style='color: orange;'>⚠️ Tidak ada notifikasi unread</p>";
}

// 3. Check Order #12 assignment
echo "<hr><h3>3. Status Order #12:</h3>";
$stmt = $db->prepare("SELECT o.id, o.order_number, o.kurir_id, o.status, k.name as kurir_name
                      FROM orders o
                      LEFT JOIN kurir k ON o.kurir_id = k.id
                      WHERE o.id = 12");
$stmt->execute();
$order = $stmt->fetch();

if ($order) {
    echo "<pre>";
    echo "Order Number: {$order['order_number']}\n";
    echo "Status: {$order['status']}\n";
    echo "Kurir ID: {$order['kurir_id']}\n";
    echo "Kurir Name: {$order['kurir_name']}\n";
    echo "</pre>";
}

echo "<hr>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<h3>✅ Cara Test:</h3>";
echo "<ol>";
echo "<li><strong>Login sebagai Kurir:</strong>";
echo "<ul>";
echo "<li>URL: <a href='http://localhost/DailyCup/kurir/login.php' target='_blank'>http://localhost/DailyCup/kurir/login.php</a></li>";
echo "<li>Phone: <code>081234567890</code></li>";
echo "<li>Password: <code>password123</code></li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Lihat Notifikasi:</strong>";
echo "<ul>";
echo "<li>Klik icon bell 🔔 di header dashboard</li>";
echo "<li>Seharusnya ada badge merah dengan angka " . count($unread) . "</li>";
echo "<li>Modal akan muncul dengan notifikasi pesanan baru</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Test Auto-Assign untuk Order Baru:</strong>";
echo "<ul>";
echo "<li>Buat order baru sebagai customer</li>";
echo "<li>Upload payment proof</li>";
echo "<li>Kurir akan otomatis di-assign</li>";
echo "<li>Notifikasi akan muncul di dashboard kurir</li>";
echo "</ul>";
echo "</li>";
echo "</ol>";
echo "</div>";
?>
