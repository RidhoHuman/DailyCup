<?php
/**
 * Happy Hour - Get All Schedules
 * 
 * Purpose: Retrieve all Happy Hour schedules for admin management
 * Used by: Admin panel to list/edit schedules
 * 
 * GET Parameters:
 * - None (returns all schedules)
 * 
 * Returns: Array of all Happy Hour schedules with assigned products
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

// Verify admin authentication
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

$decoded = JWT::verify($token);
if (!$decoded || !isset($decoded['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Check if user is admin
$userQuery = "SELECT role FROM users WHERE id = :user_id";
$userStmt = $pdo->prepare($userQuery);
$userStmt->bindParam(':user_id', $decoded['user_id']);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

try {
    // Get all schedules
    $query = "SELECT 
                id,
                name,
                start_time,
                end_time,
                days_of_week,
                discount_percentage,
                is_active,
                created_at,
                updated_at
            FROM happy_hour_schedules
            ORDER BY is_active DESC, start_time ASC";
    
    $stmt = $pdo->query($query);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each schedule, get assigned products
    foreach ($schedules as &$schedule) {
        $productsQuery = "SELECT 
                            p.id,
                            p.name,
                            p.base_price,
                            p.image,
                            c.name AS category_name
                        FROM products p
                        INNER JOIN happy_hour_products hhp ON p.id = hhp.product_id
                        LEFT JOIN categories c ON p.category_id = c.id
                        WHERE hhp.happy_hour_id = :schedule_id
                        ORDER BY p.name ASC";
        
        $productsStmt = $pdo->prepare($productsQuery);
        $productsStmt->bindParam(':schedule_id', $schedule['id']);
        $productsStmt->execute();
        $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format products
        $schedule['products'] = array_map(function($product) use ($schedule) {
            $originalPrice = (float)$product['base_price'];
            $discountAmount = round($originalPrice * ((float)$schedule['discount_percentage'] / 100));
            
            return [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'category' => $product['category_name'],
                'original_price' => $originalPrice,
                'discounted_price' => $originalPrice - $discountAmount,
                'savings' => $discountAmount,
                'image' => $product['image']
            ];
        }, $products);
        
        // Parse JSON days
        $schedule['days_of_week'] = json_decode($schedule['days_of_week'], true);
        
        // Format times (remove seconds)
        $schedule['start_time'] = substr($schedule['start_time'], 0, 5);
        $schedule['end_time'] = substr($schedule['end_time'], 0, 5);
        
        // Convert boolean
        $schedule['is_active'] = (bool)$schedule['is_active'];
        
        // Add stats
        $schedule['product_count'] = count($schedule['products']);
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'total' => count($schedules)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
