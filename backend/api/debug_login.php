<?php
header('Content-Type: application/json');

try {
    echo json_encode(['step' => 1, 'status' => 'Starting login debug']) . "\n";
    
    // Step 2: Test CORS
    require_once __DIR__ . '/cors.php';
    echo json_encode(['step' => 2, 'status' => 'CORS loaded']) . "\n";
    
    // Step 3: Test database
    require_once __DIR__ . '/config/database.php';
    echo json_encode(['step' => 3, 'status' => 'Database connected', 'db' => DB_NAME]) . "\n";
    
    // Step 4: Check if users table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    echo json_encode(['step' => 4, 'status' => 'Users table check', 'exists' => $tableExists ? true : false]) . "\n";
    
    if (!$tableExists) {
        echo json_encode(['error' => 'Users table does not exist!']) . "\n";
        exit;
    }
    
    // Step 5: Check users table columns
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['step' => 5, 'status' => 'Users columns', 'columns' => $columns]) . "\n";
    
    // Step 6: Count users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    echo json_encode(['step' => 6, 'status' => 'Active users count', 'total' => $result['total']]) . "\n";
    
    // Step 7: Test input_sanitizer
    require_once __DIR__ . '/input_sanitizer.php';
    echo json_encode(['step' => 7, 'status' => 'InputSanitizer loaded']) . "\n";
    
    // Step 8: Test JWT
    require_once __DIR__ . '/jwt.php';
    echo json_encode(['step' => 8, 'status' => 'JWT loaded']) . "\n";
    
    echo json_encode(['step' => 9, 'status' => 'ALL TESTS PASSED']) . "\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]) . "\n";
}
