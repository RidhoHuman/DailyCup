<?php
header('Content-Type: application/json');

try {
    echo json_encode(['step' => 1, 'status' => 'Starting debug', 'dir' => __DIR__]) . "\n";
    
    // Step 2: Test CORS (should be in parent folder)
    $corsPath = __DIR__ . '/../cors.php';
    echo json_encode(['step' => 2, 'status' => 'CORS path check', 'path' => $corsPath, 'exists' => file_exists($corsPath)]) . "\n";
    require_once $corsPath;
    echo json_encode(['step' => 3, 'status' => 'CORS loaded']) . "\n";
    
    // Step 4: Test database (config folder is in parent)
    require_once __DIR__ . '/../config/database.php';
    echo json_encode(['step' => 4, 'status' => 'Database connected', 'db' => DB_NAME]) . "\n";
    
    // Step 5: Test simple query
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $result = $stmt->fetch();
    echo json_encode(['step' => 5, 'status' => 'Products table accessible', 'total_products' => $result['total']]) . "\n";
    
    // Step 5: Test column existence
    $stmt = $pdo->prepare("SHOW COLUMNS FROM products");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['step' => 5, 'status' => 'Product columns', 'columns' => $columns]) . "\n";
    
    // Step 6: Test the actual query from products.php
    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.name,
            p.description,
            p.base_price as price,
            p.image,
            p.is_featured,
            p.stock,
            c.name as category
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        LIMIT 1
    ");
    $stmt->execute();
    $product = $stmt->fetch();
    echo json_encode(['step' => 6, 'status' => 'Query executed', 'sample_product' => $product]) . "\n";
    
    echo json_encode(['step' => 7, 'status' => 'ALL TESTS PASSED']) . "\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]) . "\n";
}
