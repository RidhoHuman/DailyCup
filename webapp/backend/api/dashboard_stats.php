<?php
/**
 * Dashboard Statistics API
 * Returns general stats for admin dashboard
 */

require_once __DIR__ . '/cors.php';
// CORS handled centrally (cors.php / .htaccess)
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../config/database.php';

// Get and verify JWT token with case-insensitive header check
$headers = getallheaders();
$token = null;

foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $token = str_replace('Bearer ', '', $value);
        break;
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

try {
    $decoded = JWT::verify($token);
    
    // Check if user is admin
    if (($decoded['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

try {
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $total_customers = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $total_products = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Available kurir
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM kurir WHERE is_active = 1 AND status = 'available'");
    $available_kurir = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending reviews (is_approved = 0 or NULL means pending)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reviews WHERE (is_approved = 0 OR is_approved IS NULL)");
    $pending_reviews = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Low stock products (stock < 10)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock < 10 AND is_active = 1");
    $low_stock_products = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_customers' => $total_customers,
            'total_products' => $total_products,
            'available_kurir' => $available_kurir,
            'pending_reviews' => $pending_reviews,
            'low_stock_products' => $low_stock_products
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
