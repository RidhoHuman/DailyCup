<?php
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

// Verify JWT token
$headers = getallheaders();
$token = null;

// Check for Authorization header (case-insensitive)
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $token = str_replace('Bearer ', '', $value);
        break;
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

// Verify JWT token
$decoded = JWT::verify($token);
if (!$decoded || !isset($decoded['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Check if user is admin
if (($decoded['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Use $pdo from database.php
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection not available']);
    exit;
}

try {
    if ($method === 'GET') {
        // Check if getting single kurir by ID
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            // Get single kurir with detailed info
            $query = "SELECT k.*, 
                             COUNT(DISTINCT CASE WHEN o.status IN ('processing', 'ready', 'delivering') THEN o.id END) as active_deliveries,
                             COUNT(DISTINCT CASE WHEN o.status = 'completed' AND DATE(o.completed_at) = CURDATE() THEN o.id END) as today_deliveries,
                             SUM(CASE WHEN o.status = 'completed' AND DATE(o.completed_at) = CURDATE() THEN o.final_amount ELSE 0 END) as today_earnings,
                             COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as total_completed,
                             AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating END) as avg_rating,
                             COUNT(DISTINCT r.id) as total_reviews
                      FROM kurir k
                      LEFT JOIN orders o ON o.kurir_id = k.id
                      LEFT JOIN reviews r ON r.order_id = o.id
                      WHERE k.id = :id
                      GROUP BY k.id";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([':id' => $id]);
            $kurir = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$kurir) {
                http_response_code(404);
                echo json_encode(['error' => 'Kurir not found']);
                exit;
            }
            
            // Get latest location
            $locQuery = "SELECT latitude, longitude, updated_at 
                         FROM kurir_location 
                         WHERE kurir_id = :id 
                         ORDER BY updated_at DESC 
                         LIMIT 1";
            $locStmt = $pdo->prepare($locQuery);
            $locStmt->execute([':id' => $id]);
            $location = $locStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($location) {
                $kurir['latitude'] = $location['latitude'];
                $kurir['longitude'] = $location['longitude'];
                $kurir['location_updated_at'] = $location['updated_at'];
            }
            
            echo json_encode([
                'success' => true,
                'kurir' => $kurir
            ]);
            exit;
        }
        
        // Get kurirs with optional status filter
        $status = $_GET['status'] ?? null;
        
        $query = "SELECT id, name, phone, email, vehicle_type, vehicle_number, 
                         status, rating, total_deliveries, is_active
                  FROM kurir 
                  WHERE is_active = 1";
        
        $params = [];
        if ($status) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY status ASC, rating DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $kurirs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'kurirs' => $kurirs,
            'count' => count($kurirs)
        ]);
        
    } elseif ($method === 'POST') {
        // Create new kurir (admin only)
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'phone', 'password'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                exit;
            }
        }
        
        $query = "INSERT INTO kurir (name, phone, email, password, vehicle_type, 
                                    vehicle_number, status, is_active) 
                  VALUES (:name, :phone, :email, :password, :vehicle_type, 
                          :vehicle_number, 'available', 1)";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':phone' => $data['phone'],
            ':email' => $data['email'] ?? null,
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':vehicle_type' => $data['vehicle_type'] ?? 'motor',
            ':vehicle_number' => $data['vehicle_number'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Kurir created successfully',
            'kurir_id' => $pdo->lastInsertId()
        ]);
        
    } elseif ($method === 'PUT') {
        // Update kurir
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Kurir ID is required']);
            exit;
        }
        
        // Build dynamic update query
        $updates = [];
        $params = [':id' => $id];
        
        $allowed_fields = ['name', 'phone', 'email', 'vehicle_type', 'vehicle_number', 'status', 'is_active'];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }
        
        $query = "UPDATE kurir SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true,
            'message' => 'Kurir updated successfully'
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    error_log("Kurir API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
