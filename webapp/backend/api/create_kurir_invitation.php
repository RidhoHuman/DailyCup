<?php
/**
 * Create Kurir Invitation Code
 * Admin only - generate invitation for new kurir
 */

require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

// Authenticate admin
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
if (!$decoded || !isset($decoded['user_id']) || ($decoded['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $invitedName = $input['invited_name'] ?? '';
    $invitedPhone = $input['invited_phone'] ?? '';
    $invitedEmail = $input['invited_email'] ?? null;
    $vehicleType = $input['vehicle_type'] ?? 'motor';
    $notes = $input['notes'] ?? null;
    $expiresDays = max(1, min(30, (int)($input['expires_days'] ?? 7)));
    
    if (empty($invitedName) || empty($invitedPhone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nama dan nomor HP wajib diisi']);
        exit;
    }
    
    // Generate unique invitation code
    function generateInvitationCode($pdo) {
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = 'DC-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)) . 
                    '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)) . 
                    '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            
            // Check if code exists
            $check = $pdo->prepare("SELECT id FROM kurir_invitations WHERE invitation_code = ?");
            $check->execute([$code]);
            if (!$check->fetch()) {
                return $code;
            }
        }
        throw new Exception('Failed to generate unique code');
    }
    
    $invitationCode = generateInvitationCode($pdo);
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiresDays days"));
    
    // Insert invitation
    $stmt = $pdo->prepare("
        INSERT INTO kurir_invitations 
        (invitation_code, invited_name, invited_phone, invited_email, vehicle_type, 
         status, created_by, expires_at, notes, created_at)
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $invitationCode,
        $invitedName,
        $invitedPhone,
        $invitedEmail,
        $vehicleType,
        $decoded['user_id'],
        $expiresAt,
        $notes
    ]);
    
    $invitationId = $pdo->lastInsertId();
    
    // TODO: Send invitation via SMS/Email
    // sendInvitationNotification($invitedPhone, $invitedEmail, $invitationCode);
    
    echo json_encode([
        'success' => true,
        'message' => 'Undangan berhasil dibuat',
        'invitation' => [
            'id' => (int)$invitationId,
            'invitation_code' => $invitationCode,
            'invited_name' => $invitedName,
            'invited_phone' => $invitedPhone,
            'invited_email' => $invitedEmail,
            'expires_at' => $expiresAt
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Create Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal membuat undangan']);
}
?>
