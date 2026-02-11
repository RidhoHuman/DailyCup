<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
$email = $argv[1] ?? ($_GET['email'] ?? 'ridhohuman11@gmail.com');
$stmt = $pdo->prepare('SELECT id, email, password, is_active FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($user, JSON_PRETTY_PRINT) . "\n";