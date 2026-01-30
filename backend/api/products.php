<?php
/**
 * Products API - Full CRUD
 * 
 * Returns list of active products with variants.
 * Implements rate limiting.
 */

// Ensure CORS headers are set for cross-origin requests (ngrok / Vercel)
require_once __DIR__ . '/cors.php';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rate_limiter.php';

header('Content-Type: application/json');

// Rate limiting
$clientIP = RateLimiter::getClientIP();
RateLimiter::enforce($clientIP, 'default');

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get single product or list all
            if (isset($_GET['id'])) {
                // Get single product with rating data
                $stmt = $pdo->prepare("
                    SELECT
                        p.id,
                        p.name,
                        p.description,
                        p.base_price as price,
                        p.category_id,
                        p.image,
                        p.is_featured,
                        p.stock,
                        c.name as category,
                        prs.average_rating,
                        prs.total_reviews
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN (
                        SELECT product_id,
                               ROUND(AVG(rating),1) AS average_rating,
                               COUNT(*) AS total_reviews
                        FROM product_reviews
                        WHERE status = 'approved'
                        GROUP BY product_id
                    ) prs ON p.id = prs.product_id
                    WHERE p.id = ? AND p.is_active = 1
                ");
                $stmt->execute([$_GET['id']]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Product not found']);
                    exit;
                }
                
                $product['price'] = (float)$product['price'];
                $product['is_featured'] = (bool)$product['is_featured'];
                $product['stock'] = (int)$product['stock'];
                $product['average_rating'] = $product['average_rating'] ? (float)$product['average_rating'] : null;
                $product['total_reviews'] = $product['total_reviews'] ? (int)$product['total_reviews'] : 0;
                
                echo json_encode(['success' => true, 'product' => $product]);
            } else {
                // Get all products with category, variants, and ratings
                $stmt = $pdo->prepare("
                    SELECT
                        p.id,
                        p.name,
                        p.description,
                        p.base_price as price,
                        p.image,
                        p.is_featured,
                        p.stock,
                        c.name as category,
                        prs.average_rating,
                        prs.total_reviews,
                        GROUP_CONCAT(
                            CONCAT(
                                pv.variant_type, ':', pv.variant_value, ':', pv.price_adjustment
                            ) SEPARATOR ';'
                        ) as variants
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN product_variants pv ON p.id = pv.product_id
                    LEFT JOIN (
                        SELECT product_id,
                               ROUND(AVG(rating),1) AS average_rating,
                               COUNT(*) AS total_reviews
                        FROM product_reviews
                        WHERE status = 'approved'
                        GROUP BY product_id
                    ) prs ON p.id = prs.product_id
                    WHERE p.is_active = 1
                    GROUP BY p.id
                    ORDER BY p.is_featured DESC, p.id
                ");
                $stmt->execute();
                $products = $stmt->fetchAll();

                // Process variants and ratings
                foreach ($products as &$product) {
                    $variants = [];
                    if ($product['variants']) {
                        $variantStrings = explode(';', $product['variants']);
                        foreach ($variantStrings as $variantStr) {
                            list($type, $value, $adjustment) = explode(':', $variantStr);
                            if (!isset($variants[$type])) {
                                $variants[$type] = [];
                            }
                            $variants[$type][] = [
                                'value' => $value,
                                'price_adjustment' => (float)$adjustment
                            ];
                        }
                    }
                    $product['variants'] = $variants;
                    $product['price'] = (float)$product['price'];
                    $product['is_featured'] = (bool)$product['is_featured'];
                    $product['stock'] = (int)$product['stock'];
                    $product['average_rating'] = $product['average_rating'] ? (float)$product['average_rating'] : null;
                    $product['total_reviews'] = $product['total_reviews'] ? (int)$product['total_reviews'] : 0;
                }

                echo json_encode([
                    'success' => true,
                    'data' => $products
                ]);
            }
            break;

        case 'POST':
            // Create new product
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product name is required']);
                exit;
            }
            
            if (!isset($data['price']) || $data['price'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Valid price is required']);
                exit;
            }
            
            if (!isset($data['category_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO products (name, description, base_price, category_id, image, is_featured, stock, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([
                trim($data['name']),
                isset($data['description']) ? trim($data['description']) : null,
                (float)$data['price'],
                (int)$data['category_id'],
                isset($data['image']) ? $data['image'] : null,
                isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
                isset($data['stock']) ? (int)$data['stock'] : 0
            ]);
            
            $productId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => ['id' => $productId]
            ]);
            break;

        case 'PUT':
            // Update product
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product name is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, 
                    description = ?, 
                    base_price = ?, 
                    category_id = ?, 
                    stock = ?,
                    is_featured = ?,
                    updated_at = NOW()
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([
                trim($data['name']),
                isset($data['description']) ? trim($data['description']) : null,
                isset($data['price']) ? (float)$data['price'] : null,
                isset($data['category_id']) ? (int)$data['category_id'] : null,
                isset($data['stock']) ? (int)$data['stock'] : null,
                isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
                $_GET['id']
            ]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            break;

        case 'DELETE':
            // Delete product (soft delete)
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit;
            }
            
            // Soft delete
            $stmt = $pdo->prepare("UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>