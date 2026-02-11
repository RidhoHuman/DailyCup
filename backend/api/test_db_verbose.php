<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

try {
    require_once __DIR__ . '/../config/database.php';
    echo "Database.php loaded\n";
    
    $conn = getDbConnection();
    echo "Connection obtained\n";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'user_count' => $row['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
