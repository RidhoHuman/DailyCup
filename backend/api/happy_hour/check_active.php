<?php
/**
 * Happy Hour - Check Active Discount
 * 
 * Purpose: Check if a product has an active Happy Hour discount RIGHT NOW
 * Used by: Frontend product catalog to show real-time discounts
 * 
 * GET Parameters:
 * - product_id (required): Product ID to check
 * - current_time (optional): Override current time for testing (HH:MM:SS)
 * - current_day (optional): Override current day for testing (monday, tuesday, etc)
 * 
 * Returns: Discount info if active, or no discount status
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

// Get request parameters
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;
$currentTime = isset($_GET['current_time']) ? $_GET['current_time'] : date('H:i:s');
$currentDay = isset($_GET['current_day']) ? strtolower($_GET['current_day']) : strtolower(date('l'));

// Validate required parameters
if (!$productId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Product ID is required'
    ]);
    exit;
}

try {
    // Query active Happy Hour schedules for this product at current time
    $query = "SELECT 
                hhs.id,
                hhs.name,
                hhs.start_time,
                hhs.end_time,
                hhs.discount_percentage,
                hhs.days_of_week
            FROM happy_hour_schedules hhs
            INNER JOIN happy_hour_products hhp ON hhs.id = hhp.happy_hour_id
            WHERE hhp.product_id = :product_id
            AND hhs.is_active = 1
            AND :current_time BETWEEN hhs.start_time AND hhs.end_time
            LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $stmt->bindParam(':current_time', $currentTime, PDO::PARAM_STR);
    $stmt->execute();
    
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no schedule found, return no discount
    if (!$schedule) {
        echo json_encode([
            'success' => true,
            'has_discount' => false,
            'message' => 'No active Happy Hour for this product'
        ]);
        exit;
    }
    
    // Check if current day is in the schedule's active days
    $activeDays = json_decode($schedule['days_of_week'], true);
    if (!in_array($currentDay, $activeDays)) {
        echo json_encode([
            'success' => true,
            'has_discount' => false,
            'message' => 'Happy Hour not active on ' . $currentDay
        ]);
        exit;
    }
    
    // Get product price
    $productQuery = "SELECT id, name, base_price FROM products WHERE id = :product_id";
    $productStmt = $pdo->prepare($productQuery);
    $productStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $productStmt->execute();
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Product not found'
        ]);
        exit;
    }
    
    // Calculate discount
    $originalPrice = (float)$product['base_price'];
    $discountPercentage = (float)$schedule['discount_percentage'];
    $discountAmount = round($originalPrice * ($discountPercentage / 100));
    $finalPrice = $originalPrice - $discountAmount;
    
    // Return discount information
    echo json_encode([
        'success' => true,
        'has_discount' => true,
        'discount' => [
            'schedule_id' => (int)$schedule['id'],
            'schedule_name' => $schedule['name'],
            'start_time' => substr($schedule['start_time'], 0, 5), // HH:MM format
            'end_time' => substr($schedule['end_time'], 0, 5),
            'discount_percentage' => $discountPercentage,
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'savings' => $discountAmount
        ],
        'product' => [
            'id' => (int)$product['id'],
            'name' => $product['name']
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
