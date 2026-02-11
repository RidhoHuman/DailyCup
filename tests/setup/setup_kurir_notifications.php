<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>🔧 Setup Kurir Notifications System</h2>";
echo "<hr>";

$db = getDB();

try {
    // 1. Create kurir_notifications table
    echo "<h3>1. Membuat Tabel kurir_notifications:</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS kurir_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kurir_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        order_id INT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kurir_id) REFERENCES kurir(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
        INDEX idx_kurir_read (kurir_id, is_read),
        INDEX idx_created (created_at DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "<p style='color: green;'>✅ Tabel kurir_notifications berhasil dibuat!</p>";
    
    // 2. Create notification untuk Order #12 yang sudah di-assign
    echo "<hr><h3>2. Membuat Notifikasi untuk Order #12:</h3>";
    
    $stmt = $db->prepare("SELECT o.id, o.order_number, o.kurir_id, o.user_id, u.name as customer_name
                         FROM orders o
                         JOIN users u ON o.user_id = u.id
                         WHERE o.id = 12");
    $stmt->execute();
    $order = $stmt->fetch();
    
    if ($order && $order['kurir_id']) {
        // Check if notification already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM kurir_notifications WHERE order_id = 12");
        $stmt->execute();
        $exists = $stmt->fetchColumn();
        
        if (!$exists) {
            $stmt = $db->prepare("INSERT INTO kurir_notifications 
                                 (kurir_id, type, title, message, order_id) 
                                 VALUES (?, 'new_delivery', 'Pesanan Baru!', ?, ?)");
            $message = "Anda mendapat pesanan baru #{$order['order_number']} dari {$order['customer_name']}. Segera ambil pesanan!";
            $stmt->execute([$order['kurir_id'], $message, $order['id']]);
            
            echo "<p style='color: green;'>✅ Notifikasi berhasil dibuat untuk kurir #{$order['kurir_id']}!</p>";
            echo "<pre>";
            echo "Type: new_delivery\n";
            echo "Title: Pesanan Baru!\n";
            echo "Message: $message\n";
            echo "Order ID: {$order['id']}\n";
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ Notifikasi sudah ada untuk order ini</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Order #12 tidak ditemukan atau belum ada kurir</p>";
    }
    
    // 3. Verify notifications
    echo "<hr><h3>3. Verifikasi Notifikasi Kurir:</h3>";
    $stmt = $db->prepare("SELECT kn.*, k.name as kurir_name
                         FROM kurir_notifications kn
                         JOIN kurir k ON kn.kurir_id = k.id
                         ORDER BY kn.created_at DESC
                         LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    if (count($notifications) > 0) {
        echo "<p>✅ Ditemukan " . count($notifications) . " notifikasi:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Kurir</th><th>Type</th><th>Title</th><th>Message</th><th>Is Read</th><th>Created At</th></tr>";
        foreach ($notifications as $n) {
            $isRead = $n['is_read'] ? '✅' : '❌';
            echo "<tr>";
            echo "<td>{$n['id']}</td>";
            echo "<td>{$n['kurir_name']}</td>";
            echo "<td>{$n['type']}</td>";
            echo "<td>{$n['title']}</td>";
            echo "<td>{$n['message']}</td>";
            echo "<td>{$isRead}</td>";
            echo "<td>{$n['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Belum ada notifikasi</p>";
    }
    
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h3>✅ Setup Selesai!</h3>";
    echo "<p><strong>Langkah Selanjutnya:</strong></p>";
    echo "<ol>";
    echo "<li>Update auto_assign_kurir.php untuk mengirim notifikasi ke kurir</li>";
    echo "<li>Buat API endpoint /api/kurir_notifications.php</li>";
    echo "<li>Tambahkan notifikasi UI di kurir/index.php</li>";
    echo "<li>Test notifikasi real-time</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ ERROR: " . $e->getMessage() . "</p>";
}
?>
