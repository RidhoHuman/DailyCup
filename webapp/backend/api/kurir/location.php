<?php
/**
 * Kurir Location API
 * 
 * POST /api/kurir/location.php — Update kurir location
 * GET  /api/kurir/location.php?kurir_id=X — Get kurir location (for customers/admin)
 */

require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

$authUser = JWT::getUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Only kurirs can update their own location
        if (($authUser['role'] ?? '') !== 'kurir') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Kurir access required']);
            exit;
        }

        $kurirId = $authUser['kurir_id'] ?? $authUser['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        $lat = $input['latitude'] ?? null;
        $lng = $input['longitude'] ?? null;
        $accuracy = $input['accuracy'] ?? null;
        $speed = $input['speed'] ?? null;

        if ($lat === null || $lng === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'latitude dan longitude wajib diisi']);
            exit;
        }

        // Upsert location with accuracy and speed
        $stmt = $pdo->prepare("
            INSERT INTO kurir_location (kurir_id, latitude, longitude, accuracy, speed, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude), 
                longitude = VALUES(longitude), 
                accuracy = VALUES(accuracy),
                speed = VALUES(speed),
                updated_at = NOW()
        ");
        $stmt->execute([$kurirId, $lat, $lng, $accuracy, $speed]);

        echo json_encode(['success' => true, 'message' => 'Lokasi diperbarui']);

    } elseif ($method === 'GET') {
        // Customers can track their kurir, admins can see all
        $targetKurirId = $_GET['kurir_id'] ?? null;

        if (!$targetKurirId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'kurir_id required']);
            exit;
        }

        // If customer, verify they have an active order with this kurir
        if (($authUser['role'] ?? '') === 'customer') {
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE user_id = ? AND kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering')
            ");
            $checkStmt->execute([$authUser['user_id'], $targetKurirId]);
            if ($checkStmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                exit;
            }
        }

        $stmt = $pdo->prepare("
            SELECT kl.latitude, kl.longitude, kl.updated_at,
                   k.name as kurir_name, k.phone as kurir_phone, k.vehicle_type, k.vehicle_number
            FROM kurir_location kl
            JOIN kurir k ON kl.kurir_id = k.id
            WHERE kl.kurir_id = ?
            ORDER BY kl.updated_at DESC
            LIMIT 1
        ");
        $stmt->execute([$targetKurirId]);
        $location = $stmt->fetch();

        if (!$location) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Lokasi kurir tidak ditemukan']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'latitude' => (float)$location['latitude'],
                'longitude' => (float)$location['longitude'],
                'updatedAt' => $location['updated_at'],
                'kurir' => [
                    'name' => $location['kurir_name'],
                    'phone' => $location['kurir_phone'],
                    'vehicleType' => $location['vehicle_type'],
                    'vehicleNumber' => $location['vehicle_number']
                ]
            ]
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    error_log("Kurir Location error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Kurir Location error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
