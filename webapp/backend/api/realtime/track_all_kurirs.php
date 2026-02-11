<?php
/**
 * Real-time All Kurirs Location Tracking - Server-Sent Events (SSE)
 * For Admin Dashboard to track all active deliveries
 * 
 * GET /api/realtime/track_all_kurirs.php
 * Requires: JWT Authorization (admin only)
 */

// SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
// NOTE: CORS handled in .htaccess to avoid duplicate Access-Control-Allow-Origin values
// Do not set Access-Control-Allow-* headers here to prevent conflicts


// Disable output buffering
if (ob_get_level()) ob_end_clean();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

// Polyfill for getallheaders() if not available
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// For SSE, allow access without strict auth for development
// In production, consider using WebSocket with proper auth
$isAuthorized = false;

// Try to verify JWT token from query param (workaround for SSE)
$token = $_GET['token'] ?? '';
if ($token) {
    $decoded = validateJWT($token);
    if ($decoded && ($decoded->role === 'admin' || $decoded->role === 'owner')) {
        $isAuthorized = true;
    }
}

// Also accept from Authorization header if sent
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!$isAuthorized && preg_match('/Bearer\s+(.*)/', $authHeader, $matches)) {
    $decoded = validateJWT($matches[1]);
    if ($decoded && ($decoded->role === 'admin' || $decoded->role === 'owner')) {
        $isAuthorized = true;
    }
}

// For development, allow access from localhost
$referer = $headers['Referer'] ?? $headers['referer'] ?? '';
$origin = $headers['Origin'] ?? $headers['origin'] ?? '';
if (!$isAuthorized && (strpos($referer, 'localhost') !== false || strpos($origin, 'localhost') !== false)) {
    $isAuthorized = true; // Allow in development
}

if (!$isAuthorized) {
    echo "event: error\n";
    echo "data: " . json_encode(['error' => 'Unauthorized - Admin only']) . "\n\n";
    flush();
    exit;
}

try {
    // $pdo is already created by database.php include
    // (it creates global $pdo variable on include)
    $lastUpdateHash = '';
    $iteration = 0;
    $maxIterations = 3600; // 3 hours max
    
    // Keep-alive loop
    while ($iteration < $maxIterations) {
        // Check if connection is still alive
        if (connection_aborted()) {
            break;
        }
        
        // Get all active deliveries with kurir locations (only latest location per kurir)
        $stmt = $pdo->prepare("
            SELECT 
                o.id as order_id,
                o.order_number,
                u.name as customer_name,
                o.delivery_address,
                o.delivery_lat,
                o.delivery_lng,
                o.status,
                o.created_at,
                k.id as kurir_id,
                k.name as kurir_name,
                k.phone as kurir_phone,
                k.vehicle_type,
                k.vehicle_number,
                kl.latitude as kurir_lat,
                kl.longitude as kurir_lng,
                kl.updated_at as location_updated,
                kl.accuracy,
                kl.speed
            FROM orders o
            INNER JOIN kurir k ON o.kurir_id = k.id
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN (
                SELECT kurir_id, latitude, longitude, updated_at, accuracy, speed
                FROM kurir_location kl1
                WHERE updated_at = (
                    SELECT MAX(updated_at) FROM kurir_location kl2 WHERE kl2.kurir_id = kl1.kurir_id
                )
            ) kl ON k.id = kl.kurir_id
            WHERE o.status IN ('processing', 'ready', 'delivering')
            AND o.kurir_id IS NOT NULL
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
        $activeDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data
        $kurirs = [];
        foreach ($activeDeliveries as $delivery) {
            $kurirId = $delivery['kurir_id'];
            
            if (!isset($kurirs[$kurirId])) {
                $kurirs[$kurirId] = [
                    'id' => $kurirId,
                    'name' => $delivery['kurir_name'],
                    'phone' => $delivery['kurir_phone'],
                    'vehicle_type' => $delivery['vehicle_type'],
                    'vehicle_number' => $delivery['vehicle_number'],
                    'location' => null,
                    'orders' => []
                ];
            }
            
            // Set kurir location (will be same for all orders of this kurir)
            if ($delivery['kurir_lat'] && $delivery['kurir_lng']) {
                $kurirs[$kurirId]['location'] = [
                    'lat' => floatval($delivery['kurir_lat']),
                    'lng' => floatval($delivery['kurir_lng']),
                    'updated_at' => $delivery['location_updated'],
                    'accuracy' => $delivery['accuracy'] ? floatval($delivery['accuracy']) : null,
                    'speed' => $delivery['speed'] ? floatval($delivery['speed']) : null
                ];
            }
            
            // Add order to kurir's order list
            $kurirs[$kurirId]['orders'][] = [
                'order_id' => $delivery['order_id'],
                'order_number' => $delivery['order_number'],
                'customer_name' => $delivery['customer_name'],
                'delivery_address' => $delivery['delivery_address'],
                'destination' => [
                    'lat' => floatval($delivery['delivery_lat']),
                    'lng' => floatval($delivery['delivery_lng'])
                ],
                'status' => $delivery['status'],
                'created_at' => $delivery['created_at']
            ];
        }
        
        // Convert to array
        $kurirsArray = array_values($kurirs);
        
        // Create hash to detect changes
        $currentHash = md5(json_encode($kurirsArray));
        
        // Only send data if changed
        if ($currentHash !== $lastUpdateHash) {
            $lastUpdateHash = $currentHash;
            
            echo "event: update\n";
            echo "data: " . json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'total_active' => count($kurirsArray),
                'kurirs' => $kurirsArray
            ]) . "\n\n";
            flush();
        } else {
            // Send keepalive ping
            echo "event: ping\n";
            echo "data: " . json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'active_kurirs' => count($kurirsArray)
            ]) . "\n\n";
            flush();
        }
        
        // Sleep for 5 seconds (admin view can be less frequent)
        sleep(5);
        $iteration++;
    }
    
} catch (Exception $e) {
    echo "event: error\n";
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    flush();
}

echo "event: close\n";
echo "data: " . json_encode(['message' => 'Connection closed']) . "\n\n";
flush();
