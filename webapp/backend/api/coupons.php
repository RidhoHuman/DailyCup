<?php
/**
 * Coupons/Vouchers API
 * Handles CRUD operations for discount coupons
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/input_sanitizer.php';

header('Content-Type: application/json');

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'dailycup_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$couponId = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    switch ($method) {
        case 'GET':
            // Check if validation request
            if (isset($_GET['action']) && $_GET['action'] === 'validate') {
                validateCouponForCustomer($db);
            } else if ($couponId) {
                getCoupon($db, $couponId);
            } else {
                getAllCoupons($db);
            }
            break;

        case 'POST':
            // Requires authentication
            $userData = validateToken();
            if ($userData['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }
            createCoupon($db, $input);
            break;

        case 'PUT':
            // Requires authentication
            $userData = validateToken();
            if ($userData['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }
            updateCoupon($db, $couponId, $input);
            break;

        case 'DELETE':
            // Requires authentication
            $userData = validateToken();
            if ($userData['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }
            deleteCoupon($db, $couponId);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Get all coupons
 */
function getAllCoupons($db) {
    $query = "SELECT * FROM discounts ORDER BY created_at DESC";
    $stmt = $db->query($query);
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'coupons' => $coupons
    ]);
}

/**
 * Get single coupon
 */
function getCoupon($db, $couponId) {
    $query = "SELECT * FROM discounts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$couponId]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        echo json_encode(['success' => true, 'coupon' => $coupon]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Coupon not found']);
    }
}

/**
 * Create new coupon
 */
function createCoupon($db, $data) {
    $code = sanitize($data['code'] ?? '');
    $name = sanitize($data['name'] ?? '');
    $description = sanitize($data['description'] ?? '');
    $discount_type = sanitize($data['discount_type'] ?? 'percentage');
    $discount_value = floatval($data['discount_value'] ?? 0);
    $min_purchase = floatval($data['min_purchase'] ?? 0);
    $max_discount = isset($data['max_discount']) && $data['max_discount'] !== '' ? floatval($data['max_discount']) : null;
    $usage_limit = isset($data['usage_limit']) && $data['usage_limit'] !== '' ? intval($data['usage_limit']) : null;
    $start_date = sanitize($data['start_date'] ?? '');
    $end_date = sanitize($data['end_date'] ?? '');
    $is_active = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1;

    // Validate required fields
    if (empty($code) || empty($name) || empty($start_date) || empty($end_date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }

    // Check if code already exists
    $checkStmt = $db->prepare("SELECT id FROM discounts WHERE code = ?");
    $checkStmt->execute([$code]);
    if ($checkStmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Coupon code already exists']);
        return;
    }

    $query = "INSERT INTO discounts (
        code, name, description, discount_type, discount_value, 
        min_purchase, max_discount, usage_limit, start_date, end_date, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($query);
    $stmt->execute([
        $code, $name, $description, $discount_type, $discount_value,
        $min_purchase, $max_discount, $usage_limit, $start_date, $end_date, $is_active
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Coupon created successfully',
        'coupon_id' => $db->lastInsertId()
    ]);
}

/**
 * Update coupon
 */
function updateCoupon($db, $couponId, $data) {
    if (!$couponId) {
        http_response_code(400);
        echo json_encode(['error' => 'Coupon ID required']);
        return;
    }

    // Check if coupon exists
    $checkStmt = $db->prepare("SELECT id FROM discounts WHERE id = ?");
    $checkStmt->execute([$couponId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Coupon not found']);
        return;
    }

    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [];

    if (isset($data['code'])) {
        $updateFields[] = "code = ?";
        $params[] = sanitize($data['code']);
    }
    if (isset($data['name'])) {
        $updateFields[] = "name = ?";
        $params[] = sanitize($data['name']);
    }
    if (isset($data['description'])) {
        $updateFields[] = "description = ?";
        $params[] = sanitize($data['description']);
    }
    if (isset($data['discount_type'])) {
        $updateFields[] = "discount_type = ?";
        $params[] = sanitize($data['discount_type']);
    }
    if (isset($data['discount_value'])) {
        $updateFields[] = "discount_value = ?";
        $params[] = floatval($data['discount_value']);
    }
    if (isset($data['min_purchase'])) {
        $updateFields[] = "min_purchase = ?";
        $params[] = floatval($data['min_purchase']);
    }
    if (isset($data['max_discount'])) {
        $updateFields[] = "max_discount = ?";
        $params[] = $data['max_discount'] !== '' ? floatval($data['max_discount']) : null;
    }
    if (isset($data['usage_limit'])) {
        $updateFields[] = "usage_limit = ?";
        $params[] = $data['usage_limit'] !== '' ? intval($data['usage_limit']) : null;
    }
    if (isset($data['start_date'])) {
        $updateFields[] = "start_date = ?";
        $params[] = sanitize($data['start_date']);
    }
    if (isset($data['end_date'])) {
        $updateFields[] = "end_date = ?";
        $params[] = sanitize($data['end_date']);
    }
    if (isset($data['is_active'])) {
        $updateFields[] = "is_active = ?";
        $params[] = $data['is_active'] ? 1 : 0;
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $couponId;
    $query = "UPDATE discounts SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Coupon updated successfully'
    ]);
}

/**
 * Delete coupon
 */
function deleteCoupon($db, $couponId) {
    if (!$couponId) {
        http_response_code(400);
        echo json_encode(['error' => 'Coupon ID required']);
        return;
    }

    $query = "DELETE FROM discounts WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$couponId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Coupon deleted successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Coupon not found']);
    }
}

/**
 * Validate and apply coupon to order
 */
function validateCoupon($db, $code, $orderTotal) {
    $code = strtoupper(trim($code));
    
    $query = "SELECT * FROM discounts 
              WHERE code = ? 
              AND is_active = 1 
              AND start_date <= NOW() 
              AND end_date >= NOW()
              AND (usage_limit IS NULL OR usage_count < usage_limit)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        return ['valid' => false, 'message' => 'Invalid or expired coupon code'];
    }

    // Check minimum purchase
    if ($orderTotal < $coupon['min_purchase']) {
        return [
            'valid' => false, 
            'message' => 'Minimum purchase of Rp ' . number_format($coupon['min_purchase'], 0, ',', '.') . ' required'
        ];
    }

    // Calculate discount
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($orderTotal * $coupon['discount_value']) / 100;
        
        // Apply max discount if set
        if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['discount_value'];
    }

    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount_amount' => $discount,
        'final_total' => max(0, $orderTotal - $discount)
    ];
}

/**
 * Validate coupon for customer (public endpoint)
 */
function validateCouponForCustomer($db) {
    $code = $_GET['code'] ?? '';
    $total = floatval($_GET['total'] ?? 0);

    if (!$code || $total <= 0) {
        echo json_encode([
            'valid' => false,
            'error' => 'Invalid request parameters'
        ]);
        return;
    }

    $result = validateCoupon($db, $code, $total);
    
    if ($result['valid']) {
        echo json_encode([
            'valid' => true,
            'discount' => $result['discount_amount'],
            'type' => $result['coupon']['discount_type'],
            'message' => 'Coupon applied successfully'
        ]);
    } else {
        echo json_encode([
            'valid' => false,
            'error' => $result['message']
        ]);
    }
}
