<?php
/**
 * Real-time Kurir Location Tracking - Server-Sent Events (SSE)
 * Streams kurir location updates to clients every 3 seconds
 * 
 * GET /api/realtime/track_kurir.php?order_id=123
 * No authentication required (public tracking)
 */

// SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx/proxy buffering
// NOTE: Do NOT set Access-Control headers here - .htaccess manages CORS to avoid duplicate values
// If needed, set in .htaccess or central CORS handler (backend/api/.htaccess, backend/api/cors.php)


// Disable output buffering
if (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../../config/database.php';

// Get order_id from query parameter
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$orderId) {
    echo "event: error\n";
    echo "data: " . json_encode(['error' => 'Invalid order_id']) . "\n\n";
    flush();
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Verify order exists and get kurir_id
    $stmt = $pdo->prepare("
        SELECT o.id, o.kurir_id, o.status, o.delivery_address, o.delivery_lat, o.delivery_lng,
               k.name as kurir_name, k.phone as kurir_phone, k.vehicle_type
        FROM orders o
        LEFT JOIN kurir k ON o.kurir_id = k.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "event: error\n";
        echo "data: " . json_encode(['error' => 'Order not found']) . "\n\n";
        flush();
        exit;
    }
    
    if (!$order['kurir_id']) {
        echo "event: waiting\n";
        echo "data: " . json_encode(['message' => 'Waiting for kurir assignment']) . "\n\n";
        flush();
        exit;
    }
    
    $kurirId = $order['kurir_id'];
    $lastUpdate = null;
    $iteration = 0;
    $maxIterations = 3600; // Run for max 3 hours (3600 * 3 seconds)
    
    // Send initial order info
    echo "event: init\n";
    echo "data: " . json_encode([
        'order_id' => $order['id'],
        'kurir_name' => $order['kurir_name'],
        'kurir_phone' => $order['kurir_phone'],
        'vehicle_type' => $order['vehicle_type'],
        'status' => $order['status'],
        'destination' => [
            'address' => $order['delivery_address'],
            'lat' => floatval($order['delivery_lat']),
            'lng' => floatval($order['delivery_lng'])
        ]
    ]) . "\n\n";
    flush();
    
    // Keep-alive loop
    while ($iteration < $maxIterations) {
        // Check if connection is still alive
        if (connection_aborted()) {
            break;
        }
        
        // Get latest kurir location
        $stmt = $pdo->prepare("
            SELECT latitude, longitude, updated_at, accuracy, speed
            FROM kurir_location 
            WHERE kurir_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$kurirId]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get current order status
        $statusStmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
        $statusStmt->execute([$orderId]);
        $currentOrder = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if order status changed to completed/cancelled
        if ($currentOrder && in_array($currentOrder['status'], ['completed', 'cancelled'])) {
            echo "event: complete\n";
            echo "data: " . json_encode([
                'status' => $currentOrder['status'],
                'message' => $currentOrder['status'] === 'completed' 
                    ? 'Pesanan telah diantar!' 
                    : 'Pesanan dibatalkan'
            ]) . "\n\n";
            flush();
            break;
        }
        
        // Send location update if changed
        if ($location && $location['updated_at'] !== $lastUpdate) {
            $lastUpdate = $location['updated_at'];
            
            $data = [
                'lat' => floatval($location['latitude']),
                'lng' => floatval($location['longitude']),
                'timestamp' => $location['updated_at'],
                'accuracy' => $location['accuracy'] ? floatval($location['accuracy']) : null,
                'speed' => $location['speed'] ? floatval($location['speed']) : null,
                'status' => $currentOrder['status']
            ];
            
            echo "event: location\n";
            echo "data: " . json_encode($data) . "\n\n";
            flush();
        } else {
            // Send keepalive ping every cycle
            echo "event: ping\n";
            echo "data: " . json_encode(['timestamp' => date('Y-m-d H:i:s')]) . "\n\n";
            flush();
        }
        
        // Sleep for 3 seconds before next update
        sleep(3);
        $iteration++;
    }
    
} catch (Exception $e) {
    echo "event: error\n";
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    flush();
}

// Close connection gracefully
echo "event: close\n";
echo "data: " . json_encode(['message' => 'Connection closed']) . "\n\n";
flush();
