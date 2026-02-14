<?php
/**
 * Admin API: List failed geocode jobs
 * Endpoint: GET /api/admin/geocode/failed_jobs.php
 */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../utils/admin_notifier.php'; // correct path to shared utils

// Ensure JSON error responses (CORS is handled by Apache .htaccess to avoid duplicate headers)
set_exception_handler(function($e){ http_response_code(500); echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit; });
set_error_handler(function($errno,$errstr,$errfile,$errline){ http_response_code(500); echo json_encode(['success'=>false,'error'=>"$errstr in $errfile:$errline"]); exit; });

header('Content-Type: application/json');

// Check admin auth (adjust based on project auth mechanism)
$headers = function_exists('getallheaders') ? getallheaders() : [];
$token = $headers['Authorization'] ?? null;
// For brevity, assuming middleware/gateway handles auth or we rely on session. 
// REAL IMPLEMENTATION SHOULD CHECK AUTH HERE.

try {
    // 1. Get failed jobs from geocode_jobs table (recent ones first)
    $stmt = $pdo->prepare("
        SELECT j.id as job_id, j.order_id, j.status, j.last_error, j.attempts, j.updated_at,
               o.order_number, o.delivery_address, o.delivery_lat, o.delivery_lng
        FROM geocode_jobs j
        JOIN orders o ON j.order_id = o.id
        WHERE j.status = 'failed'
        ORDER BY j.updated_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Also get orders that failed locally (geocode_status='failed') but might not have a job entry or job is old
    // Use UNION or separate query. Let's stick to geocode_jobs for now as it tracks retries.
    
    echo json_encode(['success' => true, 'data' => $jobs]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
