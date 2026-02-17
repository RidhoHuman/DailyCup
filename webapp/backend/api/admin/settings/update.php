<?php
require_once __DIR__ . '/../../cors.php';
/**
 * Admin Settings API - Update settings
 * POST /api/admin/settings/update.php
 * 
 * Body (JSON):
 * {
 *   "settings": {
 *     "support_email": "new@email.com",
 *     "delivery_fee_flat": 15000,
 *     ...
 *   }
 * }
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate admin
$user = JWT::getUser();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['settings']) || !is_array($input['settings'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request: settings array required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $updated = [];
    $failed = [];
    
    // Prepare update statement
    $stmt = $pdo->prepare("
        UPDATE admin_settings 
        SET setting_value = :value,
            updated_at = CURRENT_TIMESTAMP
        WHERE setting_key = :key
    ");
    
    foreach ($input['settings'] as $key => $value) {
        try {
            // Convert value to string for storage
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            
            $stmt->execute([
                ':key' => $key,
                ':value' => $value
            ]);
            
            if ($stmt->rowCount() > 0) {
                $updated[] = $key;
            } else {
                $failed[] = [
                    'key' => $key,
                    'reason' => 'Setting key not found'
                ];
            }
        } catch (PDOException $e) {
            $failed[] = [
                'key' => $key,
                'reason' => $e->getMessage()
            ];
        }
    }
    
    $pdo->commit();
    
    // Log admin action
    $adminId = $user['user_id'] ?? $user['id'];
    $logStmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent)
        VALUES (?, 'update', 'settings', NULL, ?, ?, ?)
    ");
    $logStmt->execute([
        $adminId,
        json_encode(['updated' => $updated, 'failed' => $failed]),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'updated' => $updated,
            'failed' => $failed,
            'total_updated' => count($updated),
            'total_failed' => count($failed)
        ],
        'message' => count($updated) . ' settings updated successfully'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Admin settings update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update settings'
    ]);
}
