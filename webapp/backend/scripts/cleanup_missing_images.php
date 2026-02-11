<?php
require_once __DIR__ . '/../../../config/database.php';

try {
    $database = Database::getInstance();
    $pdo = $database->getPDO();
    
    echo "=== CLEANING UP MISSING IMAGE REFERENCES ===\n\n";
    
    // Get all products with images
    $stmt = $pdo->query("SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != ''");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed = 0;
    $notFound = 0;
    
    foreach ($products as $product) {
        $imagePath = $product['image'];
        
        // Convert database path to filesystem path
        // /uploads/products/filename.jpg -> C:\laragon\www\DailyCup\webapp\uploads\products\filename.jpg
        $filename = basename($imagePath);
        $fullPath = __DIR__ . '/../../../webapp/uploads/products/' . $filename;
        
        echo "Checking Product #{$product['id']} - {$product['name']}\n";
        echo "  Image Path: {$imagePath}\n";
        echo "  Full Path: {$fullPath}\n";
        
        if (!file_exists($fullPath)) {
            echo "  ❌ FILE NOT FOUND - Setting to NULL\n";
            
            // Update database to set image to NULL
            $updateStmt = $pdo->prepare("UPDATE products SET image = NULL WHERE id = ?");
            $updateStmt->execute([$product['id']]);
            
            $notFound++;
        } else {
            echo "  ✅ FILE EXISTS\n";
            $fixed++;
        }
        
        echo "-----------------------------------\n";
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Total Products Checked: " . count($products) . "\n";
    echo "Files Found: {$fixed}\n";
    echo "Files Missing (set to NULL): {$notFound}\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
