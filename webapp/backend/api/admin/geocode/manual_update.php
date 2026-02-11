<?php
/**
 * Admin API: Manually update order coordinates
 * Endpoint: POST /api/admin/geocode/manual_update.php
 */
require_once __DIR__ . '/../../config.php';

// Auth check placeholder
// ...

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;

if (!$orderId || !$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing order_id, lat, or lng']);
    exit;
}

try {
    // 1. Update order
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET delivery_lat = ?, delivery_lng = ?, 
            geocode_status = 'manual', 
            geocoded_at = NOW(),
            geocode_error = NULL
        WHERE id = ?
    ");
    $stmt->execute([$lat, $lng, $orderId]);

    // 2. Mark any pending/failed jobs as done (so worker doesn't overwrite)
    $stmtJob = $pdo->prepare("
        UPDATE geocode_jobs 
        SET status = 'done', updated_at = NOW(), last_error = 'Manual override'
        WHERE order_id = ? AND status != 'done'
    ");
    $stmtJob->execute([$orderId]);

    echo json_encode(['success' => true, 'message' => 'Coordinates updated']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
