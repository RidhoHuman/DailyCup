<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();
$result = $db->query("DESCRIBE products");

echo "=== Products Table Structure ===" . PHP_EOL;
while ($col = $result->fetch_assoc()) {
    echo "{$col['Field']} ({$col['Type']})" . PHP_EOL;
}
