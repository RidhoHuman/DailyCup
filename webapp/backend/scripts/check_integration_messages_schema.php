<?php
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query('SHOW COLUMNS FROM integration_messages');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error checking schema: " . $e->getMessage() . "\n";
}
