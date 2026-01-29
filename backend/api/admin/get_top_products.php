<?php
/**
 * Get Top Selling Products API
 *
 * Returns top selling products with sales data for admin dashboard
 * GET /api/admin/get_top_products.php?limit=5
 * Requires: Admin authentication
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require admin authentication
$authUser = JWT::requireAuth();
if ($authUser['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Get limit from query parameter (default 5, max 20)
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 5;

    // Fetch top selling products
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.name,
            p.base_price,
            p.image,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'paid'
        GROUP BY p.id, p.name, p.base_price, p.image
        HAVING total_sold > 0
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $products = $stmt->fetchAll();

    // Format products for frontend
    $formattedProducts = array_map(function($product) {
        return [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => (float)$product['base_price'],
            'image' => $product['image'],
            'sold' => (int)$product['total_sold'],
            'revenue' => (float)$product['total_revenue']
        ];
    }, $products);

    echo json_encode([
        'success' => true,
        'data' => $formattedProducts
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
