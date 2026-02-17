<?php
/**
 * Outlets Management API
 * Handles CRUD operations for outlet/branch management
 * Each outlet has a delivery radius (default 30km)
 */

require_once __DIR__ . '/cors.php';
header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET - Public access for listing outlets, admin for details
// POST, PUT, DELETE - Admin only
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    $decoded = requireAuth();
    if (!in_array($decoded->role, ['admin', 'owner'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
}

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleGet($pdo) {
    $id = $_GET['id'] ?? null;
    $activeOnly = ($_GET['active'] ?? '1') === '1';
    
    if ($id) {
        // Get single outlet
        $stmt = $pdo->prepare("SELECT * FROM outlets WHERE id = ?");
        $stmt->execute([$id]);
        $outlet = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$outlet) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Outlet not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'outlet' => $outlet]);
    } else {
        // List all outlets
        $sql = "SELECT * FROM outlets";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $pdo->query($sql);
        $outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'outlets' => $outlets]);
    }
}

function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['name', 'address', 'latitude', 'longitude'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
            return;
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO outlets (name, code, address, city, province, latitude, longitude, phone, email, delivery_radius_km, opening_time, closing_time, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['name'],
        $data['code'] ?? null,
        $data['address'],
        $data['city'] ?? null,
        $data['province'] ?? null,
        $data['latitude'],
        $data['longitude'],
        $data['phone'] ?? null,
        $data['email'] ?? null,
        $data['delivery_radius_km'] ?? 30.00,
        $data['opening_time'] ?? '08:00:00',
        $data['closing_time'] ?? '22:00:00',
        $data['is_active'] ?? 1
    ]);
    
    $outletId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Outlet created successfully',
        'outlet_id' => $outletId
    ]);
}

function handlePut($pdo) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Outlet ID required']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $fields = [];
    $values = [];
    
    $allowedFields = ['name', 'code', 'address', 'city', 'province', 'latitude', 'longitude', 'phone', 'email', 'delivery_radius_km', 'opening_time', 'closing_time', 'is_active'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
    
    $values[] = $id;
    
    $stmt = $pdo->prepare("UPDATE outlets SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($values);
    
    echo json_encode(['success' => true, 'message' => 'Outlet updated successfully']);
}

function handleDelete($pdo) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Outlet ID required']);
        return;
    }
    
    // Soft delete - just deactivate
    $stmt = $pdo->prepare("UPDATE outlets SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Outlet deactivated successfully']);
}
