<?php
require_once __DIR__ . '/includes/functions.php';
$db = getDB();

echo "<h2>Isi Database Password Reset Tokens</h2>";

$stmt = $db->query("SELECT * FROM password_reset_tokens ORDER BY created_at DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "Tabel kosong.";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Token (First 20 chars)</th><th>Token Length</th><th>Expires</th><th>Used</th></tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . substr($row['token'], 0, 20) . "...</td>";
        echo "<td>" . strlen($row['token']) . "</td>";
        echo "<td>" . $row['expires_at'] . "</td>";
        echo "<td>" . $row['used'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
