<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

require_once __DIR__ . '/../config/database.php';

$orderId = intval($_GET['order_id'] ?? 0);

if (!$orderId) {
    echo "data: " . json_encode(['error' => 'Invalid order ID']) . "\n\n";
    flush();
    exit;
}

$db = getDB();

// Verify order exists and get kurir_id
$stmt = $db->prepare("SELECT kurir_id, status FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || !$order['kurir_id']) {
    echo "data: " . json_encode(['error' => 'No kurir assigned']) . "\n\n";
    flush();
    exit;
}

$kurirId = $order['kurir_id'];
$lastUpdate = null;

// Keep connection alive and send updates
while (true) {
    // Get latest kurir location
    $stmt = $db->prepare("SELECT latitude, longitude, updated_at 
                         FROM kurir_location 
                         WHERE kurir_id = ? 
                         ORDER BY updated_at DESC 
                         LIMIT 1");
    $stmt->execute([$kurirId]);
    $location = $stmt->fetch();
    
    if ($location && $location['updated_at'] !== $lastUpdate) {
        $lastUpdate = $location['updated_at'];
        
        $data = [
            'lat' => floatval($location['latitude']),
            'lng' => floatval($location['longitude']),
            'timestamp' => $location['updated_at']
        ];
        
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }
    
    // Check if order is completed
    $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $currentOrder = $stmt->fetch();
    
    if ($currentOrder && $currentOrder['status'] === 'completed') {
        echo "data: " . json_encode(['status' => 'completed', 'message' => 'Delivery completed!']) . "\n\n";
        flush();
        break;
    }
    
    // Sleep for 3 seconds before next check
    sleep(3);
    
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }
}
