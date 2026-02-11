<?php
// Check notifications table structure
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "<h2>Struktur Tabel Notifications</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

$result = $db->query('DESCRIBE notifications');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Sample Notifications</h2>";
$result = $db->query('SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5');
echo "<pre>";
print_r($result->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
