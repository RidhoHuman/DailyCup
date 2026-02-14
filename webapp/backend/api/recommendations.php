<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/recommendations_error.log');

// Output buffering to catch any unexpected output
ob_start();

header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Fatal error handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error occurred',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

try {
    require_once __DIR__ . '/../../../config/database.php';
    
    // Get MySQLi connection
    $db = Database::getConnection();
    
    if (!$db || $db->connect_error) {
        throw new Exception('Database connection failed: ' . ($db->connect_error ?? 'Unknown error'));
    }
} catch (Exception $e) {
    error_log('Recommendations API Error: ' . $e->getMessage());
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ]);
    exit;
}

// Get parameters
$type = $_GET['type'] ?? 'related'; // related, personalized, trending, similar, cart
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$cart_items = isset($_GET['cart_items']) ? json_decode($_GET['cart_items'], true) : [];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 8;

$recommendations = [];

try {
    switch ($type) {
        case 'related':
            // Products in same category or frequently bought together
            if (!$product_id) {
                throw new Exception('product_id required for related recommendations');
            }
            
            // Get product category_id
            $stmt = $db->prepare("SELECT category_id FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Get products in same category (excluding current product)
            $query = "
                SELECT DISTINCT
                    p.id,
                    p.name,
                    p.description,
                    p.base_price as price,
                    c.name as category,
                    p.image,
                    p.stock,
                    COALESCE(AVG(r.rating), 0) as avg_rating,
                    COUNT(DISTINCT r.id) as review_count,
                    COUNT(DISTINCT oi.order_id) as purchase_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN reviews r ON p.id = r.product_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                WHERE p.category_id = ? 
                    AND p.id != ? 
                    AND p.is_active = 1
                    AND p.stock > 0
                GROUP BY p.id
                ORDER BY purchase_count DESC, avg_rating DESC
                LIMIT ?
            ";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param("iii", $product['category_id'], $product_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => floatval($row['price']),
                    'category' => $row['category'],
                    'image' => $row['image'],
                    'stock' => intval($row['stock']),
                    'avg_rating' => round(floatval($row['avg_rating']), 2),
                    'review_count' => intval($row['review_count']),
                    'purchase_count' => intval($row['purchase_count']),
                    'reason' => 'Similar to ' . $product['category']
                ];
            }
            break;
            
        case 'personalized':
            // Recommendations based on user's purchase history
            if (!$user_id) {
                // If no user_id, return trending products
                $type = 'trending';
                goto trending_products;
            }
            
            // Get user's frequently bought categories
            $categoryQuery = "
                SELECT p.category_id, COUNT(*) as count
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                WHERE o.user_id = ? AND o.status = 'completed'
                GROUP BY p.category_id
                ORDER BY count DESC
                LIMIT 3
            ";
            
            $stmt = $db->prepare($categoryQuery);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $categories = [];
            
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['category_id'];
            }
            
            if (empty($categories)) {
                // New user, show trending products
                $type = 'trending';
                goto trending_products;
            }
            
            // Get products from favorite categories that user hasn't bought
            $placeholders = str_repeat('?,', count($categories) - 1) . '?';
            $query = "
                SELECT DISTINCT
                    p.id,
                    p.name,
                    p.description,
                    p.base_price as price,
                    c.name as category,
                    p.image,
                    p.stock,
                    COALESCE(AVG(r.rating), 0) as avg_rating,
                    COUNT(DISTINCT r.id) as review_count,
                    COUNT(DISTINCT oi.order_id) as total_orders
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN reviews r ON p.id = r.product_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
                WHERE p.category_id IN ($placeholders)
                    AND p.is_active = 1
                    AND p.stock > 0
                    AND p.id NOT IN (
                        SELECT DISTINCT oi2.product_id
                        FROM order_items oi2
                        JOIN orders o2 ON oi2.order_id = o2.id
                        WHERE o2.user_id = ?
                    )
                GROUP BY p.id
                ORDER BY total_orders DESC, avg_rating DESC
                LIMIT ?
            ";
            
            $stmt = $db->prepare($query);
            $types = str_repeat('i', count($categories)) . 'ii';
            $params = array_merge($categories, [$user_id, $limit]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => floatval($row['price']),
                    'category' => $row['category'],
                    'image' => $row['image'],
                    'stock' => intval($row['stock']),
                    'avg_rating' => round(floatval($row['avg_rating']), 2),
                    'review_count' => intval($row['review_count']),
                    'reason' => 'Based on your preferences'
                ];
            }
            break;
            
        case 'trending':
            trending_products:
            // Best selling products in last 30 days
            // First, get products with sales data
            $query = "
                SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.base_price,
                    p.category_id,
                    p.image,
                    p.stock,
                    p.is_featured
                FROM products p
                WHERE p.is_active = 1 AND p.stock > 0
                ORDER BY p.is_featured DESC, p.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception('Query preparation failed: ' . $db->error);
            }
            
            $stmt->bind_param("i", $limit);
            
            if (!$stmt->execute()) {
                throw new Exception('Query execution failed: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Get category name
                $categoryName = '';
                $catStmt = $db->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
                if ($catStmt) {
                    $catStmt->bind_param("i", $row['category_id']);
                    $catStmt->execute();
                    $catResult = $catStmt->get_result();
                    if ($catRow = $catResult->fetch_assoc()) {
                        $categoryName = $catRow['name'];
                    }
                    $catStmt->close();
                }
                
                // Get average rating
                $ratingStmt = $db->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ?");
                $avgRating = 0;
                $reviewCount = 0;
                if ($ratingStmt) {
                    $ratingStmt->bind_param("i", $row['id']);
                    $ratingStmt->execute();
                    $ratingResult = $ratingStmt->get_result();
                    if ($ratingRow = $ratingResult->fetch_assoc()) {
                        $avgRating = $ratingRow['avg_rating'];
                        $reviewCount = $ratingRow['review_count'];
                    }
                    $ratingStmt->close();
                }
                
                // Get total sold
                $soldStmt = $db->prepare("
                    SELECT COALESCE(SUM(oi.quantity), 0) as total_sold 
                    FROM order_items oi 
                    JOIN orders o ON oi.order_id = o.id 
                    WHERE oi.product_id = ? 
                    AND o.status = 'completed'
                    AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");
                $totalSold = 0;
                if ($soldStmt) {
                    $soldStmt->bind_param("i", $row['id']);
                    $soldStmt->execute();
                    $soldResult = $soldStmt->get_result();
                    if ($soldRow = $soldResult->fetch_assoc()) {
                        $totalSold = $soldRow['total_sold'];
                    }
                    $soldStmt->close();
                }
                
                $recommendations[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => floatval($row['base_price']),
                    'category' => $categoryName,
                    'image' => $row['image'],
                    'stock' => intval($row['stock']),
                    'avg_rating' => round(floatval($avgRating), 2),
                    'review_count' => intval($reviewCount),
                    'total_sold' => intval($totalSold),
                    'reason' => 'Trending now'
                ];
            }
            break;
            
        case 'cart':
            // Complementary products based on cart items
            if (empty($cart_items)) {
                throw new Exception('cart_items required for cart recommendations');
            }
            
            $product_ids = array_column($cart_items, 'product_id');
            
            if (empty($product_ids)) {
                $type = 'trending';
                goto trending_products;
            }
            
            // Get categories of cart items
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $categoryQuery = "
                SELECT DISTINCT category_id
                FROM products 
                WHERE id IN ($placeholders)
            ";
            
            $stmt = $db->prepare($categoryQuery);
            $types = str_repeat('i', count($product_ids));
            $stmt->bind_param($types, ...$product_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            $cartCategories = [];
            
            while ($row = $result->fetch_assoc()) {
                $cartCategories[] = $row['category_id'];
            }
            
            // Find complementary products (frequently bought together)
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $query = "
                SELECT 
                    p.id,
                    p.name,
                    p.description,
                    p.base_price as price,
                    c.name as category,
                    p.image,
                    p.stock,
                    COALESCE(AVG(r.rating), 0) as avg_rating,
                    COUNT(DISTINCT r.id) as review_count,
                    COUNT(DISTINCT oi.order_id) as times_bought_together
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN reviews r ON p.id = r.product_id
                LEFT JOIN order_items oi ON p.id = oi.product_id
                WHERE oi.order_id IN (
                    SELECT DISTINCT order_id 
                    FROM order_items 
                    WHERE product_id IN ($placeholders)
                )
                AND p.id NOT IN ($placeholders)
                AND p.is_active = 1
                AND p.stock > 0
                GROUP BY p.id
                ORDER BY times_bought_together DESC, avg_rating DESC
                LIMIT ?
            ";
            
            $stmt = $db->prepare($query);
            $types = str_repeat('i', count($product_ids) * 2) . 'i';
            $params = array_merge($product_ids, $product_ids, [$limit]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'price' => floatval($row['price']),
                    'category' => $row['category'],
                    'image' => $row['image'],
                    'stock' => intval($row['stock']),
                    'avg_rating' => round(floatval($row['avg_rating']), 2),
                    'review_count' => intval($row['review_count']),
                    'reason' => 'Frequently bought together'
                ];
            }
            break;
            
        default:
            throw new Exception('Invalid recommendation type');
    }
    
    // Clean output buffer and send JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'type' => $type,
        'count' => count($recommendations),
        'recommendations' => $recommendations
    ]);
    
} catch (Exception $e) {
    ob_clean();
    error_log('Recommendations API Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// Flush output buffer
ob_end_flush();
?>
