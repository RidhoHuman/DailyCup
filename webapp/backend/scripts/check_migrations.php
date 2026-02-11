<?php
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query('SELECT filename, applied_at FROM migrations ORDER BY applied_at DESC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Error checking migrations: " . $e->getMessage() . "\n";
}
