<?php
/**
 * Get Delivery Statistics API
 * Returns dashboard statistics for delivery monitoring
 */

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $period = $_GET['period'] ?? 'today'; // today, week, month
    
    // Calculate date range - for standalone queries on orders table
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(o.created_at) = CURDATE()";
            $dateConditionOrders = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $dateConditionOrders = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $dateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $dateConditionOrders = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $dateCondition = "DATE(o.created_at) = CURDATE()";
            $dateConditionOrders = "DATE(created_at) = CURDATE()";
    }
    
    // Overall statistics
    $query = "
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN status = 'delivering' THEN 1 ELSE 0 END) as delivering,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN payment_method = 'cod' THEN 1 ELSE 0 END) as cod_orders,
            SUM(CASE WHEN payment_method = 'cod' AND status = 'pending' THEN 1 ELSE 0 END) as pending_cod,
            SUM(final_amount) as total_revenue,
            AVG(final_amount) as avg_order_value,
            SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as completed_revenue
        FROM orders
        WHERE $dateConditionOrders
    ";
    
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    
    // Kurir statistics
    $kurirQuery = "
        SELECT 
            COUNT(*) as total_kurirs,
            SUM(CASE WHEN status = 'available' AND is_active = 1 THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'busy' THEN 1 ELSE 0 END) as busy,
            SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline
        FROM kurir
    ";
    $kurirResult = $conn->query($kurirQuery);
    $kurirStats = $kurirResult->fetch_assoc();
    
    // Top performing kurirs
    $topKurirsQuery = "
        SELECT 
            k.id,
            k.name,
            k.phone,
            k.vehicle_type,
            k.rating,
            COUNT(CASE WHEN o.status IN ('processing', 'ready', 'delivering', 'completed') THEN o.id END) as total_deliveries,
            COUNT(CASE WHEN o.status = 'completed' THEN o.id END) as completed,
            AVG(CASE WHEN o.status = 'completed' AND o.delivery_time IS NOT NULL AND o.assigned_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, o.assigned_at, o.delivery_time) END) as avg_time,
            SUM(CASE WHEN o.status = 'completed' THEN o.final_amount ELSE 0 END) as total_earnings
        FROM kurir k
        LEFT JOIN orders o ON o.kurir_id = k.id AND $dateCondition
        WHERE k.is_active = 1
        GROUP BY k.id, k.name, k.phone, k.vehicle_type, k.rating
        ORDER BY completed DESC, total_earnings DESC
        LIMIT 10
    ";
    $topKurirsResult = $conn->query($topKurirsQuery);
    $topKurirs = [];
    while ($row = $topKurirsResult->fetch_assoc()) {
        $topKurirs[] = $row;
    }
    
    // Average delivery times
    $avgTimesQuery = "
        SELECT 
            AVG(TIMESTAMPDIFF(MINUTE, assigned_at, pickup_time)) as avg_pickup_time,
            AVG(TIMESTAMPDIFF(MINUTE, pickup_time, delivery_time)) as avg_delivery_time,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, delivery_time)) as avg_total_time
        FROM orders
        WHERE status = 'completed' 
        AND $dateConditionOrders
        AND assigned_at IS NOT NULL 
        AND pickup_time IS NOT NULL 
        AND delivery_time IS NOT NULL
    ";
    $avgTimesResult = $conn->query($avgTimesQuery);
    $avgTimes = $avgTimesResult->fetch_assoc();
    
    // Hourly distribution (for today)
    if ($period === 'today') {
        $hourlyQuery = "
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as orders,
                SUM(final_amount) as revenue
            FROM orders
            WHERE DATE(created_at) = CURDATE()
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ";
        $hourlyResult = $conn->query($hourlyQuery);
        $hourlyData = [];
        while ($row = $hourlyResult->fetch_assoc()) {
            $hourlyData[] = $row;
        }
    } else {
        $hourlyData = [];
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'period' => $period,
        'stats' => $stats,
        'kurir_stats' => $kurirStats,
        'top_kurirs' => $topKurirs,
        'avg_times' => $avgTimes,
        'hourly_data' => $hourlyData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
