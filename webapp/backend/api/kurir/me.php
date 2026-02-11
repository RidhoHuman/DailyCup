<?php
/**
 * Kurir Profile API
 * 
 * GET  /api/kurir/me.php         — Get kurir profile
 * PUT  /api/kurir/me.php         — Update kurir profile
 * POST /api/kurir/me.php?action=status — Update online/offline status
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../input_sanitizer.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

// Auth check - must be kurir
$authUser = JWT::getUser();
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

if (($authUser['role'] ?? '') !== 'kurir') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Kurir access required']);
    exit;
}

$kurirId = $authUser['kurir_id'] ?? $authUser['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get kurir profile with stats
        $stmt = $pdo->prepare("
            SELECT k.id, k.name, k.phone, k.email, k.photo, 
                   k.vehicle_type, k.vehicle_number, k.status,
                   k.rating, k.total_deliveries, k.is_active, k.created_at,
                   (SELECT COUNT(*) FROM orders WHERE kurir_id = k.id AND status = 'delivering') as active_orders,
                   (SELECT COUNT(*) FROM orders WHERE kurir_id = k.id AND status = 'completed' 
                    AND DATE(completed_at) = CURDATE()) as today_deliveries,
                   (SELECT COALESCE(SUM(final_amount), 0) FROM orders WHERE kurir_id = k.id 
                    AND status = 'completed' AND DATE(completed_at) = CURDATE()) as today_earnings
            FROM kurir k
            WHERE k.id = ?
        ");
        $stmt->execute([$kurirId]);
        $kurir = $stmt->fetch();

        if (!$kurir) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Kurir not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => (int)$kurir['id'],
                'name' => $kurir['name'],
                'phone' => $kurir['phone'],
                'email' => $kurir['email'],
                'photo' => $kurir['photo'],
                'vehicleType' => $kurir['vehicle_type'],
                'vehicleNumber' => $kurir['vehicle_number'],
                'status' => $kurir['status'],
                'rating' => (float)$kurir['rating'],
                'totalDeliveries' => (int)$kurir['total_deliveries'],
                'isActive' => (bool)$kurir['is_active'],
                'joinDate' => $kurir['created_at'],
                'stats' => [
                    'activeOrders' => (int)$kurir['active_orders'],
                    'todayDeliveries' => (int)$kurir['today_deliveries'],
                    'todayEarnings' => (float)$kurir['today_earnings']
                ]
            ]
        ]);

    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
            exit;
        }

        // Build update query dynamically
        $updates = [];
        $params = [];

        if (isset($input['name'])) {
            $updates[] = "name = ?";
            $params[] = InputSanitizer::string($input['name']);
        }
        if (isset($input['email'])) {
            $updates[] = "email = ?";
            $params[] = InputSanitizer::email($input['email']);
        }
        if (isset($input['phone'])) {
            $updates[] = "phone = ?";
            $params[] = InputSanitizer::phone($input['phone']);
        }
        if (isset($input['vehicle_type']) && in_array($input['vehicle_type'], ['motor', 'mobil', 'sepeda'])) {
            $updates[] = "vehicle_type = ?";
            $params[] = $input['vehicle_type'];
        }
        if (isset($input['vehicle_number'])) {
            $updates[] = "vehicle_number = ?";
            $params[] = InputSanitizer::string($input['vehicle_number']);
        }

        // Password change
        if (!empty($input['new_password'])) {
            if (empty($input['current_password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Password lama wajib diisi']);
                exit;
            }
            // Verify current password
            $pwStmt = $pdo->prepare("SELECT password FROM kurir WHERE id = ?");
            $pwStmt->execute([$kurirId]);
            $currentHash = $pwStmt->fetchColumn();

            if (!password_verify($input['current_password'], $currentHash)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Password lama salah']);
                exit;
            }
            $updates[] = "password = ?";
            $params[] = password_hash($input['new_password'], PASSWORD_DEFAULT);
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tidak ada data yang diperbarui']);
            exit;
        }

        $params[] = $kurirId;
        $sql = "UPDATE kurir SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);

    } elseif ($method === 'POST') {
        $action = $_GET['action'] ?? '';

        if ($action === 'status') {
            $input = json_decode(file_get_contents('php://input'), true);
            $newStatus = $input['status'] ?? '';

            if (!in_array($newStatus, ['available', 'offline'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Status harus available atau offline']);
                exit;
            }

            // Check if kurir has active deliveries before going offline
            if ($newStatus === 'offline') {
                $activeStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM orders WHERE kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering')
                ");
                $activeStmt->execute([$kurirId]);
                if ($activeStmt->fetchColumn() > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Tidak bisa offline, masih ada pesanan aktif']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("UPDATE kurir SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $kurirId]);

            echo json_encode(['success' => true, 'message' => 'Status diperbarui ke ' . $newStatus, 'status' => $newStatus]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }

} catch (PDOException $e) {
    error_log("Kurir Me error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Kurir Me error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
