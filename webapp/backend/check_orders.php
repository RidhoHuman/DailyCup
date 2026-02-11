<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();
$result = $conn->query('SELECT id, order_number, status FROM orders ORDER BY id DESC LIMIT 10');
while ($r = $result->fetch_assoc()) {
    echo "id: " . $r['id'] . ", order_number: " . $r['order_number'] . ", status: " . $r['status'] . PHP_EOL;
}
