<?php
/**
 * Categories API - Full CRUD
 */

// CORS handled by .htaccess

header('Content-Type: application/json');

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get single category or list all
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("
                    SELECT c.id, c.name, c.description, c.image,
                           COUNT(p.id) as product_count
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id
                    WHERE c.id = ? AND c.is_active = 1
                    GROUP BY c.id
                ");
                $stmt->execute([$_GET['id']]);
                $category = $stmt->fetch();
                
                if (!$category) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Category not found']);
                    exit;
                }
                
                echo json_encode(['success' => true, 'data' => $category]);
            } else {
                // Get all categories with product count
                $stmt = $pdo->prepare("
                    SELECT c.id, c.name, c.description, c.image,
                           COUNT(p.id) as product_count
                    FROM categories c
                    LEFT JOIN products p ON c.id = p.category_id
                    WHERE c.is_active = 1
                    GROUP BY c.id, c.name, c.description, c.image, c.display_order
                    ORDER BY c.display_order
                ");
                $stmt->execute();
                $categories = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'data' => $categories]);
            }
            break;

        case 'POST':
            // Create new category
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, description, image, is_active, display_order, created_at)
                VALUES (?, ?, ?, 1, (SELECT IFNULL(MAX(display_order), 0) + 1 FROM categories c), NOW())
            ");
            $stmt->execute([
                trim($data['name']),
                isset($data['description']) ? trim($data['description']) : null,
                isset($data['image']) ? $data['image'] : null
            ]);
            
            $categoryId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => ['id' => $categoryId]
            ]);
            break;

        case 'PUT':
            // Update category
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category name is required']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET name = ?, description = ?, updated_at = NOW()
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([
                trim($data['name']),
                isset($data['description']) ? trim($data['description']) : null,
                $_GET['id']
            ]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            break;

        case 'DELETE':
            // Delete category (soft delete)
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Category ID is required']);
                exit;
            }
            
            // Check if category has products
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete category because it has {$result['count']} products. Please reassign or delete the products first."
                ]);
                exit;
            }
            
            // Soft delete
            $stmt = $pdo->prepare("UPDATE categories SET is_active = 0, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Category not found']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
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