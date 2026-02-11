<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

echo "<h2>Pemeriksaan Struktur Database</h2>";

try {
    // 1. Cek tabel password_reset_tokens
    $stmt = $db->query("DESCRIBE password_reset_tokens");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tabel: password_reset_tokens</h3>";
    echo "<pre>";
    foreach ($columns as $col) {
        if ($col['Field'] == 'token') {
            echo "Kolom 'token': " . $col['Type'] . "\n";
            // Check if user needs update
            if (strpos($col['Type'], '64') === false && strpos($col['Type'], '255') === false) {
                 echo "-> STATUS: PERLU DIUPDATE (Terlalu pendek)\n";
            } else {
                 echo "-> STATUS: OK\n";
            }
        }
    }
    echo "</pre>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
