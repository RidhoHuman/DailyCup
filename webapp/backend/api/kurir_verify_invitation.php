<?php
/**
 * Verify Kurir Invitation Code API
 * Check if invitation code is valid and not expired
 */

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $invitationCode = $input['invitation_code'] ?? '';
    
    if (empty($invitationCode)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kode undangan wajib diisi']);
        exit;
    }
    
    // Check invitation code
    $stmt = $pdo->prepare("
        SELECT id, invited_name, invited_phone, invited_email, vehicle_type, 
               status, expires_at
        FROM kurir_invitations 
        WHERE invitation_code = ?
    ");
    $stmt->execute([$invitationCode]);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$invitation) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Kode undangan tidak ditemukan'
        ]);
        exit;
    }
    
    // Check if already used
    if ($invitation['status'] === 'used') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Kode undangan sudah digunakan'
        ]);
        exit;
    }
    
    // Check if expired
    if (strtotime($invitation['expires_at']) < time()) {
        // Update status to expired
        $updateStmt = $pdo->prepare("
            UPDATE kurir_invitations 
            SET status = 'expired' 
            WHERE id = ?
        ");
        $updateStmt->execute([$invitation['id']]);
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Kode undangan telah kedaluwarsa'
        ]);
        exit;
    }
    
    // Valid invitation
    echo json_encode([
        'success' => true,
        'message' => 'Kode undangan valid',
        'data' => [
            'name' => $invitation['invited_name'],
            'phone' => $invitation['invited_phone'],
            'email' => $invitation['invited_email'],
            'vehicle_type' => $invitation['vehicle_type']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Verify Invitation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server']);
}
?>
