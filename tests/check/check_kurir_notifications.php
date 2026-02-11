<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>🔔 Cek Notifikasi Kurir</h2>";
echo "<hr>";

$db = getDB();

// 1. Cek notifikasi untuk kurir user_id=1
echo "<h3>1. Notifikasi untuk Kurir Budi Santoso (user_id=1):</h3>";
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$notifs = $stmt->fetchAll();

if (count($notifs) > 0) {
    echo "<p>✅ Ditemukan " . count($notifs) . " notifikasi:</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Message</th><th>Is Read</th><th>Created At</th></tr>";
    foreach ($notifs as $n) {
        $isRead = $n['is_read'] ? '✅ Read' : '❌ Unread';
        echo "<tr>";
        echo "<td>{$n['id']}</td>";
        echo "<td>{$n['type']}</td>";
        echo "<td>{$n['title']}</td>";
        echo "<td>{$n['message']}</td>";
        echo "<td>{$isRead}</td>";
        echo "<td>{$n['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ TIDAK ADA NOTIFIKASI untuk kurir ini!</p>";
}

// 2. Cek user_id untuk kurir di tabel users
echo "<hr><h3>2. Cek User ID Kurir:</h3>";
$stmt = $db->query("SELECT k.id as kurir_id, k.name, k.phone, k.user_id, u.id as users_table_id, u.name as user_name 
                    FROM kurir k 
                    LEFT JOIN users u ON k.user_id = u.id 
                    WHERE k.id = 1");
$kurirUser = $stmt->fetch();

if ($kurirUser) {
    echo "<pre>";
    echo "Kurir ID: {$kurirUser['kurir_id']}\n";
    echo "Kurir Name: {$kurirUser['name']}\n";
    echo "Kurir Phone: {$kurirUser['phone']}\n";
    echo "User ID di tabel kurir: " . ($kurirUser['user_id'] ?: '❌ NULL') . "\n";
    echo "User ID di tabel users: " . ($kurirUser['users_table_id'] ?: '❌ NOT FOUND') . "\n";
    echo "User Name: " . ($kurirUser['user_name'] ?: '❌ NOT FOUND') . "\n";
    echo "</pre>";
    
    if (!$kurirUser['user_id']) {
        echo "<p style='color: red; font-weight: bold;'>❌ PROBLEM: Kurir tidak punya user_id di tabel kurir!</p>";
        echo "<p>Notifikasi tidak bisa dikirim karena tidak tahu kemana harus mengirim.</p>";
    }
}

// 3. Cek struktur tabel kurir
echo "<hr><h3>3. Struktur Tabel Kurir:</h3>";
$stmt = $db->query("DESCRIBE kurir");
$columns = $stmt->fetchAll();
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    $highlight = ($col['Field'] == 'user_id') ? "style='background: yellow;'" : "";
    echo "<tr $highlight>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Cek auto_assign_kurir.php apakah mengirim notifikasi ke kurir
echo "<hr><h3>4. Cek Auto Assign Function:</h3>";
$autoAssignFile = __DIR__ . '/api/auto_assign_kurir.php';
if (file_exists($autoAssignFile)) {
    $content = file_get_contents($autoAssignFile);
    
    if (strpos($content, 'INSERT INTO notifications') !== false) {
        echo "<p>✅ Auto assign mengandung INSERT INTO notifications</p>";
        
        // Cek apakah mengirim ke kurir
        if (strpos($content, 'user_id') !== false) {
            echo "<p>✅ Ada referensi ke user_id</p>";
            
            // Extract notification code
            preg_match('/INSERT INTO notifications.*?;/s', $content, $matches);
            if (!empty($matches)) {
                echo "<pre style='background: #f0f0f0; padding: 10px;'>";
                echo htmlspecialchars($matches[0]);
                echo "</pre>";
            }
        } else {
            echo "<p style='color: red;'>❌ Tidak ada referensi ke user_id untuk notifikasi kurir</p>";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Auto assign TIDAK mengirim notifikasi!</p>";
    }
} else {
    echo "<p style='color: red;'>File auto_assign_kurir.php tidak ditemukan!</p>";
}

echo "<hr>";
echo "<h3>🔍 DIAGNOSIS:</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;'>";
if (!$kurirUser['user_id']) {
    echo "<p><strong>MASALAH UTAMA:</strong> Tabel kurir tidak memiliki kolom user_id atau nilainya NULL.</p>";
    echo "<p><strong>SOLUSI:</strong></p>";
    echo "<ol>";
    echo "<li>Tambahkan kolom user_id ke tabel kurir</li>";
    echo "<li>Link setiap kurir dengan user yang sesuai</li>";
    echo "<li>Update auto_assign_kurir.php untuk mengirim notifikasi ke kurir juga</li>";
    echo "</ol>";
} else {
    echo "<p><strong>KEMUNGKINAN MASALAH:</strong> Auto assign tidak mengirim notifikasi ke kurir, hanya ke customer.</p>";
    echo "<p><strong>SOLUSI:</strong> Tambahkan kode untuk mengirim notifikasi ke kurir saat order di-assign.</p>";
}
echo "</div>";
?>
