<?php
/**
 * Kurir Register API Endpoint
 * 
 * Creates new courier account and returns JWT token
 * POST /api/kurir/register.php
 * Body: { name, phone, password, email?, vehicle_type?, vehicle_number? }
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

    // Sanitize inputs
    $name = InputSanitizer::string($input['name'] ?? '');
    $phone = InputSanitizer::phone($input['phone'] ?? '');
    $email = InputSanitizer::email($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $vehicleType = $input['vehicle_type'] ?? 'motor';
    $vehicleNumber = InputSanitizer::string($input['vehicle_number'] ?? '');
    $invitationCode = $input['invitation_code'] ?? '';

    // Validation
    $errors = [];
    
    if (empty($invitationCode)) {
        $errors[] = 'Kode undangan wajib diisi';
    }

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Nama minimal 2 karakter';
    }

    if (empty($phone)) {
        $errors[] = 'Nomor HP wajib diisi';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }

    if (!in_array($vehicleType, ['motor', 'mobil', 'sepeda'])) {
        $errors[] = 'Jenis kendaraan tidak valid';
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Validasi gagal', 'details' => $errors]);
        exit;
    }

    // Verify invitation code
    $invitationStmt = $pdo->prepare("
        SELECT id, invited_name, invited_phone, status, expires_at
        FROM kurir_invitations 
        WHERE invitation_code = ?
    ");
    $invitationStmt->execute([$invitationCode]);
    $invitation = $invitationStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invitation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Kode undangan tidak valid']);
        exit;
    }
    
    if ($invitation['status'] === 'used') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Kode undangan sudah digunakan']);
        exit;
    }
    
    if (strtotime($invitation['expires_at']) < time()) {
        $pdo->prepare("UPDATE kurir_invitations SET status = 'expired' WHERE id = ?")->execute([$invitation['id']]);
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Kode undangan telah kedaluwarsa']);
        exit;
    }

    // Check if phone already exists
    $checkStmt = $pdo->prepare("SELECT id FROM kurir WHERE phone = ?");
    $checkStmt->execute([$phone]);

    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Nomor HP sudah terdaftar']);
        exit;
    }

    // Check if email already exists (if provided)
    if (!empty($email)) {
        $checkEmail = $pdo->prepare("SELECT id FROM kurir WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Email sudah terdaftar']);
            exit;
        }
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $pdo->beginTransaction();

    // Insert new kurir
    $stmt = $pdo->prepare("
        INSERT INTO kurir (name, phone, email, password, vehicle_type, vehicle_number, status, rating, total_deliveries, is_active, invitation_code_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'offline', 5.00, 0, 1, ?, NOW())
    ");
    $stmt->execute([
        $name,
        $phone,
        $email ?: null,
        $hashedPassword,
        $vehicleType,
        $vehicleNumber ?: null,
        $invitation['id']
    ]);

    $kurirId = $pdo->lastInsertId();
    
    // Mark invitation as used
    $updateInvitation = $pdo->prepare("
        UPDATE kurir_invitations 
        SET status = 'used', used_by = ?, used_at = NOW() 
        WHERE id = ?
    ");
    $updateInvitation->execute([$kurirId, $invitation['id']]);
    
    $pdo->commit();

    // Generate JWT token
    $tokenPayload = [
        'user_id' => $kurirId,
        'name' => $name,
        'email' => $email ?? '',
        'role' => 'kurir',
        'kurir_id' => $kurirId
    ];
    $token = JWT::generate($tokenPayload);

    // Return kurir data
    $kurirData = [
        'id' => (int)$kurirId,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'photo' => null,
        'vehicleType' => $vehicleType,
        'vehicleNumber' => $vehicleNumber ?: null,
        'status' => 'available',
        'rating' => 5.0,
        'totalDeliveries' => 0,
        'joinDate' => date('Y-m-d H:i:s')
    ];

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Pendaftaran kurir berhasil',
        'user' => $kurirData,
        'token' => $token
    ]);

} catch (PDOException $e) {
    error_log("Kurir Register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Kurir Register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
