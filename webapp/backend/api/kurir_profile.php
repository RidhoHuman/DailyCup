<?php
/**
 * Kurir Profile API
 * Get and update courier profile information
 */

require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

// Authenticate kurir
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
    echo json_encode(['success' => false, 'message' => 'No token provided']);
    exit;
}

$decoded = JWT::verify($token);
if (!$decoded || !isset($decoded['user_id']) || ($decoded['role'] ?? '') !== 'kurir') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$kurirId = $decoded['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get kurir profile with stats
        $stmt = $pdo->prepare("
            SELECT k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, 
                   k.vehicle_number, k.status, k.rating, k.total_deliveries, k.is_active,
                   (SELECT COUNT(*) FROM orders WHERE kurir_id = k.id AND status IN ('confirmed', 'processing', 'ready', 'delivering')) as active_orders,
                   (SELECT COUNT(*) FROM orders WHERE kurir_id = k.id AND DATE(created_at) = CURDATE()) as today_deliveries,
                   (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE kurir_id = k.id AND DATE(created_at) = CURDATE() AND status = 'completed') as today_earnings
            FROM kurir k
            WHERE k.id = ?
        ");
        $stmt->execute([$kurirId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profile) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Profile not found']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'profile' => $profile,
                'stats' => [
                    'activeOrders' => (int)$profile['active_orders'],
                    'todayDeliveries' => (int)$profile['today_deliveries'],
                    'todayEarnings' => (float)$profile['today_earnings']
                ]
            ]
        ]);
        
    } elseif ($method === 'PUT') {
        // Update kurir profile
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updates = [];
        $params = [':id' => $kurirId];
        
        $allowed = ['name', 'email', 'photo', 'vehicle_type', 'vehicle_number'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            exit;
        }
        
        $query = "UPDATE kurir SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        
    } elseif ($method === 'POST') {
        // Handle status update
        $action = $_GET['action'] ?? '';
        
        if ($action === 'status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $newStatus = $input['status'] ?? '';
            
            if (!in_array($newStatus, ['available', 'offline'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE kurir SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $kurirId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully',
                'status' => $newStatus
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
} catch (PDOException $e) {
    error_log("Kurir Profile Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
