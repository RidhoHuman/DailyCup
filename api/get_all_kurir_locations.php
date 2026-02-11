<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Debug logging (remove after testing)
error_log('API Access - Session User ID: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log('API Access - Session Role: ' . ($_SESSION['role'] ?? 'NOT SET'));

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log('API Access DENIED - Unauthorized');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'debug' => 'Session: user_id=' . ($_SESSION['user_id'] ?? 'null') . ', role=' . ($_SESSION['role'] ?? 'null')]);
    exit;
}

$db = getDB();

try {
    // Get all kurir with latest locations
    $stmt = $db->query("SELECT k.id, k.name, k.status, k.vehicle_type, k.vehicle_number,
                       kl.latitude, kl.longitude, kl.updated_at as last_location_update,
                       COUNT(DISTINCT o.id) as active_deliveries
                       FROM kurir k
                       LEFT JOIN kurir_location kl ON k.id = kl.kurir_id
                       LEFT JOIN orders o ON k.id = o.kurir_id 
                          AND o.status IN ('ready', 'delivering')
                       WHERE k.is_active = 1
                       GROUP BY k.id, k.name, k.status, k.vehicle_type, k.vehicle_number,
                                kl.latitude, kl.longitude, kl.updated_at");

    $kurirs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($kurirs);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
