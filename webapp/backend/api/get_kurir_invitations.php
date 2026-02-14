<?php
/**
 * Get Kurir Invitations List
 * Admin only - view all invitation codes
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

try {
    $status = $_GET['status'] ?? null;
    
    $query = "
        SELECT 
            ki.*,
            k.name as kurir_name
        FROM kurir_invitations ki
        LEFT JOIN kurir k ON ki.used_by = k.id
    ";
    
    $params = [];
    if ($status && in_array($status, ['pending', 'used', 'expired'])) {
        $query .= " WHERE ki.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY ki.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'invitations' => $invitations
    ]);
    
} catch (PDOException $e) {
    error_log("Get Invitations Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
