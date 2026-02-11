<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("SELECT id, name, image FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total Products: " . count($products) . "\n\n";

foreach ($products as $product) {
    echo "ID: " . $product['id'] . "\n";
    echo "Name: " . $product['name'] . "\n";
    echo "Image: " . ($product['image'] ?? 'NULL') . "\n";
    echo "---------------------------\n";
}
?>