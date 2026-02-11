<?php
require_once __DIR__ . '/../config/database.php';

$query = 'DESCRIBE push_subscriptions';
$stmt = $pdo->query($query);
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['columns' => $columns], JSON_PRETTY_PRINT);
