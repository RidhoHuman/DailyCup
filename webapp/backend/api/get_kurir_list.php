<?php
/**
 * Get Kurir List API
 * Returns all kurirs with their status and active deliveries
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $status = $_GET['status'] ?? '';
    $includeInactive = isset($_GET['include_inactive']) ? $_GET['include_inactive'] === 'true' : false;
    
    // Get kurirs with stats
    $query = "
        SELECT 
            k.id,
            k.name,
            k.phone,
            k.email,
            k.photo,
            k.vehicle_type,
            k.vehicle_number,
            k.status,
            k.rating,
            k.total_deliveries,
            k.is_active,
            k.created_at,
            ANY_VALUE(kl.latitude) as latitude,
            ANY_VALUE(kl.longitude) as longitude,
            ANY_VALUE(kl.updated_at) as location_updated_at,
            COUNT(CASE WHEN o.status IN ('processing', 'ready', 'delivering') THEN 1 END) as active_deliveries,
            COUNT(CASE WHEN o.status = 'completed' AND DATE(o.completed_at) = CURDATE() THEN 1 END) as today_deliveries,
            SUM(CASE WHEN o.status = 'completed' AND DATE(o.completed_at) = CURDATE() THEN o.final_amount ELSE 0 END) as today_earnings
        FROM kurir k
        LEFT JOIN kurir_location kl ON kl.kurir_id = k.id
        LEFT JOIN orders o ON o.kurir_id = k.id
    ";
    
    $whereConditions = [];
    $params = [];
    $types = "";
    
    if (!$includeInactive) {
        $whereConditions[] = "k.is_active = 1";
    }
    
    if ($status && $status !== '') {
        $whereConditions[] = "k.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $query .= " GROUP BY k.id ORDER BY k.status ASC, k.name ASC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $kurirs = [];
    while ($row = $result->fetch_assoc()) {
        // Add availability indicator
        $row['is_available'] = $row['status'] === 'available' && $row['is_active'] == 1;
        
        // Calculate location freshness
        if ($row['location_updated_at']) {
            $locationAge = strtotime('now') - strtotime($row['location_updated_at']);
            $row['location_is_fresh'] = $locationAge < 300; // 5 minutes
        } else {
            $row['location_is_fresh'] = false;
        }
        
        $kurirs[] = $row;
    }
    
    // Get overall stats
    $statsQuery = "
        SELECT 
            COUNT(*) as total_kurirs,
            SUM(CASE WHEN status = 'available' AND is_active = 1 THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'busy' THEN 1 ELSE 0 END) as busy,
            SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        FROM kurir
    ";
    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'kurirs' => $kurirs,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
