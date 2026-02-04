<?php
/**
 * Happy Hour - Create/Update Schedule
 * 
 * Purpose: Admin creates or updates Happy Hour schedules
 * Used by: Admin panel Happy Hour management
 * 
 * POST Body:
 * {
 *   "name": "Morning Rush",
 *   "start_time": "07:00",
 *   "end_time": "09:00",
 *   "days_of_week": ["monday", "tuesday", "wednesday"],
 *   "discount_percentage": 15,
 *   "product_ids": [1, 2, 3],
 *   "is_active": true
 * }
 * 
 * PUT Body (with id): Same as POST, updates existing schedule
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

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

$userId = $decoded['user_id'];

// Check if user is admin
$userQuery = "SELECT role FROM users WHERE id = :user_id";
$userStmt = $pdo->prepare($userQuery);
$userStmt->bindParam(':user_id', $userId);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST' || $method === 'PUT') {
    // Validate required fields
    $required = ['name', 'start_time', 'end_time', 'days_of_week', 'discount_percentage', 'product_ids'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Validate discount percentage
    if ($input['discount_percentage'] <= 0 || $input['discount_percentage'] > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Discount percentage must be between 1 and 100']);
        exit;
    }
    
    // Validate time format and logic
    $startTime = $input['start_time'] . ':00'; // Add seconds
    $endTime = $input['end_time'] . ':00';
    
    if ($startTime >= $endTime) {
        http_response_code(400);
        echo json_encode(['error' => 'Start time must be before end time']);
        exit;
    }
    
    // Validate days
    $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    foreach ($input['days_of_week'] as $day) {
        if (!in_array(strtolower($day), $validDays)) {
            http_response_code(400);
            echo json_encode(['error' => "Invalid day: $day"]);
            exit;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        if ($method === 'POST') {
            // Create new schedule
            $insertQuery = "INSERT INTO happy_hour_schedules 
                            (name, start_time, end_time, days_of_week, discount_percentage, is_active, created_by)
                            VALUES 
                            (:name, :start_time, :end_time, :days_of_week, :discount_percentage, :is_active, :created_by)";
            
            $stmt = $pdo->prepare($insertQuery);
            $stmt->bindParam(':name', $input['name']);
            $stmt->bindParam(':start_time', $startTime);
            $stmt->bindParam(':end_time', $endTime);
            $stmt->bindValue(':days_of_week', json_encode($input['days_of_week']));
            $stmt->bindParam(':discount_percentage', $input['discount_percentage']);
            $stmt->bindValue(':is_active', isset($input['is_active']) ? (int)$input['is_active'] : 1);
            $stmt->bindParam(':created_by', $userId);
            $stmt->execute();
            
            $scheduleId = $pdo->lastInsertId();
            
        } else {
            // Update existing schedule
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Schedule ID is required for update']);
                exit;
            }
            
            $updateQuery = "UPDATE happy_hour_schedules 
                           SET name = :name,
                               start_time = :start_time,
                               end_time = :end_time,
                               days_of_week = :days_of_week,
                               discount_percentage = :discount_percentage,
                               is_active = :is_active
                           WHERE id = :id";
            
            $stmt = $pdo->prepare($updateQuery);
            $stmt->bindParam(':name', $input['name']);
            $stmt->bindParam(':start_time', $startTime);
            $stmt->bindParam(':end_time', $endTime);
            $stmt->bindValue(':days_of_week', json_encode($input['days_of_week']));
            $stmt->bindParam(':discount_percentage', $input['discount_percentage']);
            $stmt->bindValue(':is_active', isset($input['is_active']) ? (int)$input['is_active'] : 1);
            $stmt->bindParam(':id', $input['id']);
            $stmt->execute();
            
            $scheduleId = $input['id'];
            
            // Delete existing product assignments
            $deleteQuery = "DELETE FROM happy_hour_products WHERE happy_hour_id = :schedule_id";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->bindParam(':schedule_id', $scheduleId);
            $deleteStmt->execute();
        }
        
        // Insert product assignments
        if (!empty($input['product_ids'])) {
            $productQuery = "INSERT INTO happy_hour_products (happy_hour_id, product_id) VALUES (:schedule_id, :product_id)";
            $productStmt = $pdo->prepare($productQuery);
            
            foreach ($input['product_ids'] as $productId) {
                $productStmt->bindParam(':schedule_id', $scheduleId);
                $productStmt->bindParam(':product_id', $productId);
                $productStmt->execute();
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $method === 'POST' ? 'Schedule created successfully' : 'Schedule updated successfully',
            'schedule_id' => (int)$scheduleId
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
    
} elseif ($method === 'DELETE') {
    // Delete schedule
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Schedule ID is required']);
        exit;
    }
    
    try {
        $deleteQuery = "DELETE FROM happy_hour_schedules WHERE id = :id";
        $stmt = $pdo->prepare($deleteQuery);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
