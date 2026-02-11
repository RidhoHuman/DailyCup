<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Check Categories
    $stmt = $pdo->query("SELECT id, image FROM categories WHERE image IS NOT NULL AND image != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($categories) . " categories with images.\n";
    
    foreach ($categories as $cat) {
        $id = $cat['id'];
        $image = $cat['image'];
        
        if (strpos($image, '/uploads/') !== 0 && strpos($image, 'http') !== 0) {
            $newPath = '/uploads/categorys/' . $image; // distinct from 'products' vs 'categorys' check folder name
            // Wait, in upload_image.php: $uploadDir = $baseUploadDir . $uploadType . 's/';
            // type='category' -> 'categorys' (yes, typo in folder name likely, or just simple pluralization)
            
            echo "Fixing Category ID $id: '$image' -> '$newPath'\n";
            $updateStmt = $pdo->prepare("UPDATE categories SET image = ? WHERE id = ?");
            $updateStmt->execute([$newPath, $id]);
        } else {
             echo "Skipping Category ID $id: '$image'\n";
        }
    }
    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>