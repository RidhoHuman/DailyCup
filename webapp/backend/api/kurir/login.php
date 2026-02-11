<?php
/**
 * Kurir Login API Endpoint
 * 
 * Authenticates courier and returns JWT token
 * POST /api/kurir/login.php
 * Body: { phone: string, password: string }
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../input_sanitizer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }

    $phone = InputSanitizer::phone($input['phone'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($phone) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nomor HP dan password wajib diisi']);
        exit;
    }

    // Find kurir by phone
    $stmt = $pdo->prepare("
        SELECT id, name, phone, email, photo, vehicle_type, vehicle_number,
               status, rating, total_deliveries, is_active, created_at
        FROM kurir 
        WHERE phone = ?
    ");
    $stmt->execute([$phone]);
    $kurir = $stmt->fetch();

    if (!$kurir) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Nomor HP atau password salah']);
        exit;
    }

    // Check if active
    if (!$kurir['is_active']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Akun kurir Anda dinonaktifkan. Hubungi admin.']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $kurir['password'] ?? '')) {
        // Re-fetch with password column for verification
        $stmt2 = $pdo->prepare("SELECT password FROM kurir WHERE id = ?");
        $stmt2->execute([$kurir['id']]);
        $kurirPw = $stmt2->fetch();
        
        if (!$kurirPw || !password_verify($password, $kurirPw['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Nomor HP atau password salah']);
            exit;
        }
    }

    // Update status to available on login
    $updateStmt = $pdo->prepare("UPDATE kurir SET status = 'available' WHERE id = ?");
    $updateStmt->execute([$kurir['id']]);

    // Generate JWT token with kurir role
    $tokenPayload = [
        'user_id' => $kurir['id'],
        'name' => $kurir['name'],
        'email' => $kurir['email'] ?? '',
        'role' => 'kurir',
        'kurir_id' => $kurir['id']
    ];
    $token = JWT::generate($tokenPayload);

    // Return kurir data
    $kurirData = [
        'id' => (int)$kurir['id'],
        'name' => $kurir['name'],
        'phone' => $kurir['phone'],
        'email' => $kurir['email'],
        'photo' => $kurir['photo'],
        'vehicleType' => $kurir['vehicle_type'],
        'vehicleNumber' => $kurir['vehicle_number'],
        'status' => 'available',
        'rating' => (float)$kurir['rating'],
        'totalDeliveries' => (int)$kurir['total_deliveries'],
        'joinDate' => $kurir['created_at']
    ];

    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil',
        'user' => $kurirData,
        'token' => $token
    ]);

} catch (PDOException $e) {
    error_log("Kurir Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Kurir Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
