<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>🔍 Verification: Apakah Fix Berhasil?</h2>";
echo "<hr>";

$db = getDB();

// 1. Cek Order #12 sekarang
echo "<h3>1. Status Order #12:</h3>";
$stmt = $db->prepare("SELECT id, order_number, user_id, kurir_id, status, delivery_method, assigned_at, created_at 
                      FROM orders WHERE id = 12");
$stmt->execute();
$order = $stmt->fetch();

if ($order) {
    echo "<pre>";
    echo "Order ID: {$order['id']}\n";
    echo "Order Number: {$order['order_number']}\n";
    echo "User ID: {$order['user_id']}\n";
    echo "Kurir ID: " . ($order['kurir_id'] ? $order['kurir_id'] : '❌ NULL') . "\n";
    echo "Status: {$order['status']}\n";
    echo "Delivery Method: {$order['delivery_method']}\n";
    echo "Assigned At: " . ($order['assigned_at'] ? $order['assigned_at'] : '❌ NULL') . "\n";
    echo "Created At: {$order['created_at']}\n";
    echo "</pre>";
    
    if ($order['kurir_id']) {
        echo "<p style='color: green; font-weight: bold;'>✅ ORDER SUDAH ASSIGNED KE KURIR!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ ORDER BELUM ASSIGNED!</p>";
    }
} else {
    echo "<p style='color: red;'>Order #12 tidak ditemukan!</p>";
}

// 2. Cek info Kurir yang menerima order
if ($order && $order['kurir_id']) {
    echo "<hr><h3>2. Info Kurir yang Menerima Order:</h3>";
    $stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
    $stmt->execute([$order['kurir_id']]);
    $kurir = $stmt->fetch();
    
    if ($kurir) {
        echo "<pre>";
        echo "Kurir ID: {$kurir['id']}\n";
        echo "Nama: {$kurir['name']}\n";
        echo "Phone: {$kurir['phone']}\n";
        echo "Status: {$kurir['status']}\n";
        echo "Is Active: " . ($kurir['is_active'] ? 'Yes' : 'No') . "\n";
        echo "</pre>";
        
        echo "<p style='background: #ffffcc; padding: 10px; border-radius: 5px;'>";
        echo "📱 <strong>Login Info:</strong><br>";
        echo "Phone: {$kurir['phone']}<br>";
        echo "Password: password123<br>";
        echo "URL: <a href='http://localhost/DailyCup/kurir/login.php'>http://localhost/DailyCup/kurir/login.php</a>";
        echo "</p>";
    }
}

// 3. Test query yang dipakai di kurir dashboard
echo "<hr><h3>3. Test Query Dashboard Kurir:</h3>";
if ($order && $order['kurir_id']) {
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone
                         FROM orders o
                         JOIN users u ON o.user_id = u.id
                         WHERE o.kurir_id = ? AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                         ORDER BY o.created_at ASC");
    $stmt->execute([$order['kurir_id']]);
    $activeOrders = $stmt->fetchAll();
    
    echo "<p>Query mencari order dengan:</p>";
    echo "<ul>";
    echo "<li>kurir_id = {$order['kurir_id']}</li>";
    echo "<li>status IN ('confirmed', 'processing', 'ready', 'delivering')</li>";
    echo "</ul>";
    
    if (count($activeOrders) > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ DITEMUKAN " . count($activeOrders) . " ACTIVE ORDER!</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Order Number</th><th>Customer</th><th>Status</th><th>Created At</th></tr>";
        foreach ($activeOrders as $ao) {
            echo "<tr>";
            echo "<td>{$ao['order_number']}</td>";
            echo "<td>{$ao['customer_name']}</td>";
            echo "<td><strong>{$ao['status']}</strong></td>";
            echo "<td>{$ao['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ TIDAK ADA ACTIVE ORDER!</p>";
        echo "<p>Kemungkinan penyebab:</p>";
        echo "<ul>";
        echo "<li>Status order '{$order['status']}' tidak termasuk dalam query</li>";
        echo "<li>Query hanya mencari status: 'confirmed', 'processing', 'ready', 'delivering'</li>";
        echo "</ul>";
    }
}

// 4. Cek delivery_history
echo "<hr><h3>4. Cek Delivery History:</h3>";
$stmt = $db->prepare("SELECT * FROM delivery_history WHERE order_id = 12 ORDER BY created_at DESC");
$stmt->execute();
$history = $stmt->fetchAll();

if (count($history) > 0) {
    echo "<p>✅ Ditemukan " . count($history) . " history record:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Order ID</th><th>Kurir ID</th><th>Action</th><th>Created At</th></tr>";
    foreach ($history as $h) {
        echo "<tr>";
        echo "<td>{$h['order_id']}</td>";
        echo "<td>{$h['kurir_id']}</td>";
        echo "<td>{$h['action']}</td>";
        echo "<td>{$h['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>⚠️ Tidak ada delivery history untuk order ini</p>";
}

echo "<hr>";
echo "<h3>✅ KESIMPULAN:</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
if ($order && $order['kurir_id']) {
    echo "<p>✅ <strong>Order sudah assigned ke kurir</strong></p>";
    echo "<p>Sekarang kurir bisa:</p>";
    echo "<ol>";
    echo "<li>Login dengan phone number: {$kurir['phone']}</li>";
    echo "<li>Buka dashboard di: <a href='http://localhost/DailyCup/kurir/index.php'>http://localhost/DailyCup/kurir/index.php</a></li>";
    echo "<li>Order akan muncul di section 'Active Deliveries'</li>";
    echo "</ol>";
    
    if ($order['status'] != 'confirmed' && $order['status'] != 'processing' && $order['status'] != 'ready' && $order['status'] != 'delivering') {
        echo "<p style='color: orange;'>⚠️ <strong>CATATAN:</strong> Status order saat ini '{$order['status']}' TIDAK akan muncul di dashboard kurir karena query hanya mencari status: 'confirmed', 'processing', 'ready', 'delivering'</p>";
        echo "<p>Untuk test, ubah status order menjadi salah satu dari status tersebut.</p>";
    }
} else {
    echo "<p>❌ Order belum assigned ke kurir. Perlu manual assignment atau fix auto-assign function.</p>";
}
echo "</div>";
?>
