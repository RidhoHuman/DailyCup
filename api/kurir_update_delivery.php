<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// Check if kurir is logged in
if (!isset($_SESSION['kurir_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db = getDB();
$kurirId = $_SESSION['kurir_id'];

// Get action
$action = $_POST['action'] ?? '';
$orderId = intval($_POST['order_id'] ?? 0);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Order ID required']);
    exit;
}

// Verify order belongs to this kurir
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND kurir_id = ?");
$stmt->execute([$orderId, $kurirId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Invalid order']);
    exit;
}

try {
    switch ($action) {
        case 'arrived_at_store':
            // Kurir arrived at store - check if on time (15 minutes before ready)
            $now = new DateTime();
            $estimatedReady = new DateTime($order['estimated_ready_at']);
            $minArrivalTime = clone $estimatedReady;
            $minArrivalTime->modify('-15 minutes');
            
            if ($now > $estimatedReady) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Terlambat! Pesanan sudah siap sejak ' . $estimatedReady->format('H:i'),
                    'late' => true
                ]);
                exit;
            }
            
            // Update arrival time and status to ready
            $stmt = $db->prepare("UPDATE orders SET kurir_arrived_at = NOW(), status = 'ready' WHERE id = ?");
            $stmt->execute([$orderId]);
            
            // Log in delivery history
            $stmt = $db->prepare("INSERT INTO delivery_history (order_id, kurir_id, status, notes, latitude, longitude) 
                                 VALUES (?, ?, 'arrived_at_store', 'Kurir tiba di toko', ?, ?)");
            $lat = floatval($_POST['latitude'] ?? 0);
            $lon = floatval($_POST['longitude'] ?? 0);
            $stmt->execute([$orderId, $kurirId, $lat, $lon]);
            
            // Notify admin
            createAdminNotification(
                $orderId,
                "Kurir Tiba di Toko",
                "Kurir telah tiba di toko untuk mengambil pesanan #{$order['order_number']}",
                'kurir_arrived'
            );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Berhasil! Tunggu hingga pesanan siap.'
            ]);
            break;
            
        case 'pickup_with_photo':
            // Kurir pickup order - upload departure photo
            // TESTING MODE: Photo is optional
            $photo = null;
            if (TESTING_MODE) {
                // Testing mode: photo optional
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo = uploadImage($_FILES['photo'], 'assets/images/delivery/');
                }
                // Use dummy photo if not uploaded
                if (!$photo) {
                    $photo = 'test_pickup_photo.jpg';
                }
            } else {
                // Production: photo required
                if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'Foto bukti keberangkatan wajib diunggah']);
                    exit;
                }
                $photo = uploadImage($_FILES['photo'], 'assets/images/delivery/');
                if (!$photo) {
                    echo json_encode(['success' => false, 'message' => 'Gagal upload foto']);
                    exit;
                }
            }
            
            // Check if order is ready (bypass in testing mode)
            if (!TESTING_MODE && $order['status'] !== 'ready') {
                echo json_encode(['success' => false, 'message' => 'Pesanan belum siap diambil']);
                exit;
            }
            
            // Update order
            $stmt = $db->prepare("UPDATE orders SET 
                                 status = 'delivering', 
                                 pickup_time = NOW(),
                                 kurir_departure_photo = ?
                                 WHERE id = ?");
            $stmt->execute([$photo, $orderId]);
            
            // Log in delivery history with photo
            $stmt = $db->prepare("INSERT INTO delivery_history (order_id, kurir_id, status, notes, photo, latitude, longitude) 
                                 VALUES (?, ?, 'pickup', 'Pesanan diambil, dalam perjalanan', ?, ?, ?)");
            // TESTING MODE: Use dummy coordinates
            $lat = TESTING_MODE ? STORE_LATITUDE : floatval($_POST['latitude'] ?? 0);
            $lon = TESTING_MODE ? STORE_LONGITUDE : floatval($_POST['longitude'] ?? 0);
            $stmt->execute([$orderId, $kurirId, $photo, $lat, $lon]);
            
            // Notify customer
            createNotification(
                $order['user_id'],
                "Pesanan Dalam Perjalanan!",
                "Kurir sedang dalam perjalanan mengantar pesanan #{$order['order_number']}. Lacak posisi kurir secara real-time.",
                'order_update',
                $orderId
            );
            
            // Notify admin
            createAdminNotification(
                $orderId,
                "Pesanan Diantar",
                "Pesanan #{$order['order_number']} telah diambil kurir dan dalam perjalanan",
                'out_for_delivery'
            );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Berhasil! Pesanan dalam perjalanan.'
            ]);
            break;
            
        case 'delivered_with_photo':
            // Kurir delivered order - upload arrival photo
            // TESTING MODE: Photo is optional
            $photo = null;
            if (TESTING_MODE) {
                // Testing mode: photo optional
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo = uploadImage($_FILES['photo'], 'assets/images/delivery/');
                }
                // Use dummy photo if not uploaded
                if (!$photo) {
                    $photo = 'test_delivery_photo.jpg';
                }
            } else {
                // Production: photo required
                if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'Foto bukti sampai wajib diunggah']);
                    exit;
                }
                $photo = uploadImage($_FILES['photo'], 'assets/images/delivery/');
                if (!$photo) {
                    echo json_encode(['success' => false, 'message' => 'Gagal upload foto']);
                    exit;
                }
            }
            
            // Check if order is delivering (bypass in testing mode)
            if (!TESTING_MODE && $order['status'] !== 'delivering') {
                echo json_encode(['success' => false, 'message' => 'Status order tidak valid']);
                exit;
            }
            
            // Calculate actual delivery time
            $pickupTime = new DateTime($order['pickup_time']);
            $deliveryTime = new DateTime();
            $actualDeliveryTime = $deliveryTime->diff($pickupTime)->i; // minutes
            
            // Update order
            $stmt = $db->prepare("UPDATE orders SET 
                                 status = 'completed', 
                                 delivery_time = NOW(),
                                 kurir_arrival_photo = ?,
                                 actual_delivery_time = ?
                                 WHERE id = ?");
            $stmt->execute([$photo, $actualDeliveryTime, $orderId]);
            
            // Log in delivery history with photo
            $stmt = $db->prepare("INSERT INTO delivery_history (order_id, kurir_id, status, notes, photo, latitude, longitude) 
                                 VALUES (?, ?, 'completed', 'Pesanan berhasil diantar', ?, ?, ?)");
            $lat = floatval($_POST['latitude'] ?? 0);
            $lon = floatval($_POST['longitude'] ?? 0);
            $stmt->execute([$orderId, $kurirId, $photo, $lat, $lon]);
            
            // Update kurir status
            // Check if kurir has other active orders
            $stmt = $db->prepare("SELECT COUNT(*) FROM orders 
                                 WHERE kurir_id = ? 
                                 AND status IN ('confirmed', 'processing', 'ready', 'delivering')");
            $stmt->execute([$kurirId]);
            $activeOrders = $stmt->fetchColumn();
            
            if ($activeOrders == 0) {
                // No more active orders, set to available
                $stmt = $db->prepare("UPDATE kurir SET status = 'available' WHERE id = ?");
                $stmt->execute([$kurirId]);
            }
            
            // Update kurir stats
            $stmt = $db->prepare("UPDATE kurir SET 
                                 total_deliveries = total_deliveries + 1
                                 WHERE id = ?");
            $stmt->execute([$kurirId]);
            
            // Award loyalty points to customer
            $earnRate = 0.01; // 1% of order amount
            $pointsEarned = floor($order['final_amount'] * $earnRate);
            
            if ($pointsEarned > 0) {
                $stmt = $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
                $stmt->execute([$pointsEarned, $order['user_id']]);
                
                $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, transaction_type, points, description, order_id) 
                                     VALUES (?, 'earned', ?, ?, ?)");
                $stmt->execute([
                    $order['user_id'],
                    $pointsEarned,
                    "Poin dari pesanan #{$order['order_number']}",
                    $orderId
                ]);
            }
            
            // Notify customer
            createNotification(
                $order['user_id'],
                "Pesanan Selesai!",
                "Pesanan #{$order['order_number']} telah berhasil diantar. Terima kasih! Anda mendapat {$pointsEarned} poin loyalty.",
                'order_update',
                $orderId
            );
            
            // Notify admin
            createAdminNotification(
                $orderId,
                "Pesanan Selesai",
                "Pesanan #{$order['order_number']} telah berhasil diantar dalam {$actualDeliveryTime} menit",
                'order_completed'
            );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pesanan berhasil diantar! Terima kasih.',
                'points_earned' => $pointsEarned
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
