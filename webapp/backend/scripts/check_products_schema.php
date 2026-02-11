<?php
require_once __DIR__ . '/../../../config/database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getPDO();
    
    echo "=== PRODUCTS TABLE STRUCTURE ===\n\n";
    
    $stmt = $pdo->query("DESCRIBE products");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
