<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/jwt_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Verify admin authentication
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$jwt = $matches[1];

try {
    $decoded = validateJWT($jwt);
    
    if (!isset($decoded->role) || $decoded->role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit();
    }

    // Get invitation ID from query parameter
    $invitationId = $_GET['id'] ?? '';

    if (empty($invitationId) || !is_numeric($invitationId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid invitation ID']);
        exit();
    }

    $database = new Database();
    $conn = $database->getConnection();

    // Check if invitation exists and is not used
    $checkQuery = "SELECT status FROM kurir_invitations WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $invitationId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invitation not found']);
        exit();
    }

    $invitation = $result->fetch_assoc();

    // Prevent deletion of used invitations
    if ($invitation['status'] === 'used') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete used invitation']);
        exit();
    }

    // Delete the invitation
    $deleteQuery = "DELETE FROM kurir_invitations WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $invitationId);
    
    if ($deleteStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Invitation deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete invitation']);
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token: ' . $e->getMessage()]);
}
