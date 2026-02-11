<?php
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Get all products with images not null
    $stmt = $pdo->query("SELECT id, image FROM products WHERE image IS NOT NULL AND image != ''");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($products) . " products with images.\n";

    foreach ($products as $product) {
        $id = $product['id'];
        $image = $product['image'];

        // Check if image needs fixing
        // It needs fixing if it doesn't start with '/uploads/' AND doesn't start with 'http'
        if (strpos($image, '/uploads/') !== 0 && strpos($image, 'http') !== 0) {
            
            // Assume it's a legacy filename that belongs in /uploads/products/
            // But verify if it has 'products/' prefix already or not.
            // Based on user error: "prod_695df2fca9a2e.jfif" -> No prefix.
            
            // Just to be safe, if it contains 'products/' but not start with /uploads, handle that too (though unlikely based on logs)
            
            // Construct new path
            $newPath = '/uploads/products/' . $image;
            
            echo "Fixing Product ID $id: '$image' -> '$newPath'\n";
            
            $updateStmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
            $updateStmt->execute([$newPath, $id]);
        } else {
            echo "Skipping Product ID $id: '$image' (Already correct format)\n";
        }
    }
    
    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>