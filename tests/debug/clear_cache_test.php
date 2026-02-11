<?php
// Clear OPcache and test create ticket
echo "<h2>DailyCup - Clear Cache & Test</h2>";

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color: green;'>✓ OPcache berhasil dibersihkan</p>";
} else {
    echo "<p style='color: orange;'>⚠ OPcache tidak aktif</p>";
}

// Check current file modification time
$file = __DIR__ . '/customer/create_ticket.php';
echo "<h3>Info File create_ticket.php</h3>";
echo "<p>Last Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "</p>";
echo "<p>File Size: " . filesize($file) . " bytes</p>";

// Check the actual query in the file
echo "<h3>Cek Query INSERT notifications:</h3>";
$content = file_get_contents($file);
if (strpos($content, "INSERT INTO notifications (user_id, type, title, message)") !== false) {
    echo "<p style='color: green;'>✓ Query BENAR - Tidak menggunakan kolom 'link'</p>";
} else if (strpos($content, "INSERT INTO notifications") !== false) {
    // Find the actual query
    preg_match('/INSERT INTO notifications[^;]+/i', $content, $matches);
    echo "<p style='color: red;'>✗ Query ditemukan:</p>";
    echo "<pre>" . htmlspecialchars($matches[0] ?? 'Not found') . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠ Query INSERT notifications tidak ditemukan</p>";
}

echo "<hr>";
echo "<h3>Testing Notification Insert</h3>";

require_once __DIR__ . '/../config/database.php';
$db = getDB();

try {
    $testAdminId = 1;
    $testMessage = "Test ticket dari clear cache - " . date('H:i:s');
    
    $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) 
                         VALUES (?, 'ticket', 'Test Ticket', ?)");
    $stmt->execute([$testAdminId, $testMessage]);
    
    echo "<p style='color: green;'>✓ Test INSERT berhasil!</p>";
    echo "<p>ID: " . $db->lastInsertId() . "</p>";
    echo "<p>Message: {$testMessage}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Test INSERT gagal!</p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Solusi untuk User:</h3>";
echo "<ol>";
echo "<li><strong>Clear Browser Cache:</strong> Tekan Ctrl+Shift+Delete atau Ctrl+F5 untuk hard refresh</li>";
echo "<li><strong>Clear PHP Session:</strong> Logout dan login kembali</li>";
echo "<li><strong>Coba Incognito Mode:</strong> Buka browser dalam mode private/incognito</li>";
echo "</ol>";

echo "<br><a href='customer/create_ticket.php' style='background: #6F4E37; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Coba Buat Ticket Sekarang</a>";
