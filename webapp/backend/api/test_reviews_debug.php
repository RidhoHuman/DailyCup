<?php
require_once __DIR__ . '/cors.php';
// Simple test file to debug reviews API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

require_once __DIR__ . '/config.php';
echo "Config loaded\n";

global $pdo;
echo "PDO available: " . (isset($pdo) ? "YES" : "NO") . "\n";

// Test query
try {
    $stmt = $pdo->prepare("SELECT * FROM product_ratings_summary WHERE product_id = 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Query result:\n";
    print_r($result);
    
    echo "\nTest complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
