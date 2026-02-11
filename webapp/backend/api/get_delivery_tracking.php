<?php
/**
 * Get Delivery Tracking API
 * Returns real-time tracking for all active deliveries
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
// CORS is handled at Apache level (.htaccess). Avoid setting Access-Control headers here to prevent duplicates.

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $status = $_GET['status'] ?? '';
    $kurirId = isset($_GET['kurir_id']) ? intval($_GET['kurir_id']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    // Build query - use subquery to get only the latest kurir location
    $query = "
        SELECT 
            o.id,
            o.order_number,
            o.user_id,
            o.kurir_id,
            o.final_amount,
            o.delivery_address,
            o.delivery_distance,
            o.status,
            o.payment_method,
            o.payment_status,
            o.assigned_at,
            o.pickup_time,
            o.delivery_time,
            o.kurir_arrived_at,
            o.created_at,
            o.updated_at,
            COALESCE(o.customer_name, u.name) as customer_name,
            COALESCE(o.customer_phone, u.phone) as customer_phone,
            k.name as kurir_name,
            k.phone as kurir_phone,
            k.vehicle_type,
            k.status as kurir_status,
            kl.latitude as kurir_lat,
            kl.longitude as kurir_lng,
            kl.updated_at as location_updated_at,
            TIMESTAMPDIFF(MINUTE, o.assigned_at, NOW()) as minutes_since_assigned,
            TIMESTAMPDIFF(MINUTE, o.pickup_time, NOW()) as minutes_since_pickup
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN kurir k ON k.id = o.kurir_id
        LEFT JOIN (
            SELECT kurir_id, latitude, longitude, updated_at
            FROM kurir_location kl1
            WHERE kl1.updated_at = (
                SELECT MAX(kl2.updated_at) FROM kurir_location kl2 WHERE kl2.kurir_id = kl1.kurir_id
            )
        ) kl ON kl.kurir_id = k.id
        WHERE o.status IN ('confirmed', 'processing', 'ready', 'delivering')
    ";
    
    $params = [];
    $types = "";
    
    if ($status && $status !== '') {
        $query .= " AND o.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if ($kurirId) {
        $query .= " AND o.kurir_id = ?";
        $params[] = $kurirId;
        $types .= "i";
    }
    
    $query .= " ORDER BY 
        CASE o.status
            WHEN 'delivering' THEN 1
            WHEN 'ready' THEN 2
            WHEN 'processing' THEN 3
            WHEN 'confirmed' THEN 4
        END,
        o.created_at ASC
        LIMIT ?";
    
    $params[] = $limit;
    $types .= "i";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $deliveries = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate delivery progress
        $progress = 0;
        switch ($row['status']) {
            case 'confirmed': $progress = 20; break;
            case 'processing': $progress = 40; break;
            case 'ready': $progress = 60; break;
            case 'delivering': $progress = 80; break;
            case 'completed': $progress = 100; break;
        }
        
        $row['progress'] = $progress;
        
        // Add delay warnings
        if ($row['status'] === 'processing' && $row['minutes_since_assigned'] > 30) {
            $row['warning'] = 'Preparation taking longer than expected';
        }
        if ($row['status'] === 'delivering' && $row['minutes_since_pickup'] > 45) {
            $row['warning'] = 'Delivery taking longer than expected';
        }
        
        $deliveries[] = $row;
    }
    
    // Get summary stats
    $statsQuery = "
        SELECT 
            COUNT(*) as total_active,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN status = 'delivering' THEN 1 ELSE 0 END) as delivering,
            SUM(CASE WHEN payment_method = 'cod' THEN 1 ELSE 0 END) as cod_orders
        FROM orders
        WHERE status IN ('confirmed', 'processing', 'ready', 'delivering')
    ";
    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'deliveries' => $deliveries,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
