<?php
/**
 * Check Delivery Availability API
 * Validates if a location is within delivery range of any active outlet
 * Uses Haversine formula for distance calculation
 */

header('Content-Type: application/json');
// CORS handled centrally (cors.php / .htaccess)

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param float $lat1 Latitude of point 1
 * @param float $lng1 Longitude of point 1
 * @param float $lat2 Latitude of point 2
 * @param float $lng2 Longitude of point 2
 * @return float Distance in kilometers
 */
function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDelta / 2) * sin($lngDelta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Find the nearest outlet to given coordinates
 * @param PDO $pdo Database connection
 * @param float $lat Customer latitude
 * @param float $lng Customer longitude
 * @return array|null Nearest outlet info or null if none in range
 */
function findNearestOutlet($pdo, $lat, $lng) {
    $stmt = $pdo->query("SELECT * FROM outlets WHERE is_active = 1");
    $outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $nearestOutlet = null;
    $minDistance = PHP_FLOAT_MAX;
    
    foreach ($outlets as $outlet) {
        $distance = haversineDistance(
            $lat, $lng,
            floatval($outlet['latitude']),
            floatval($outlet['longitude'])
        );
        
        if ($distance < $minDistance) {
            $minDistance = $distance;
            $nearestOutlet = $outlet;
            $nearestOutlet['distance_km'] = round($distance, 2);
        }
    }
    
    return $nearestOutlet;
}

/**
 * Check if delivery is available to given coordinates
 * @param PDO $pdo Database connection
 * @param float $lat Customer latitude
 * @param float $lng Customer longitude
 * @return array Delivery availability info
 */
function checkDeliveryAvailability($pdo, $lat, $lng) {
    $nearestOutlet = findNearestOutlet($pdo, $lat, $lng);
    
    if (!$nearestOutlet) {
        return [
            'available' => false,
            'reason' => 'No active outlets found',
            'outlets' => []
        ];
    }
    
    $maxRadius = floatval($nearestOutlet['delivery_radius_km']);
    $distance = $nearestOutlet['distance_km'];
    $isAvailable = $distance <= $maxRadius;
    
    // Get all outlets with their distances
    $stmt = $pdo->query("SELECT * FROM outlets WHERE is_active = 1");
    $allOutlets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $outletsWithDistance = array_map(function($outlet) use ($lat, $lng) {
        $distance = haversineDistance(
            $lat, $lng,
            floatval($outlet['latitude']),
            floatval($outlet['longitude'])
        );
        return [
            'id' => $outlet['id'],
            'name' => $outlet['name'],
            'code' => $outlet['code'],
            'address' => $outlet['address'],
            'city' => $outlet['city'],
            'latitude' => $outlet['latitude'],
            'longitude' => $outlet['longitude'],
            'distance_km' => round($distance, 2),
            'max_radius_km' => floatval($outlet['delivery_radius_km']),
            'in_range' => $distance <= floatval($outlet['delivery_radius_km']),
            'opening_time' => $outlet['opening_time'],
            'closing_time' => $outlet['closing_time']
        ];
    }, $allOutlets);
    
    // Sort by distance
    usort($outletsWithDistance, function($a, $b) {
        return $a['distance_km'] <=> $b['distance_km'];
    });
    
    return [
        'available' => $isAvailable,
        'nearest_outlet' => [
            'id' => $nearestOutlet['id'],
            'name' => $nearestOutlet['name'],
            'code' => $nearestOutlet['code'],
            'address' => $nearestOutlet['address'],
            'city' => $nearestOutlet['city'],
            'distance_km' => $distance,
            'max_radius_km' => $maxRadius
        ],
        'reason' => $isAvailable 
            ? "Delivery available from {$nearestOutlet['name']}" 
            : "Location is {$distance}km away, maximum delivery radius is {$maxRadius}km",
        'outlets' => $outletsWithDistance
    ];
}

// Handle request
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
        $lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    } else if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $lat = isset($data['lat']) ? floatval($data['lat']) : null;
        $lng = isset($data['lng']) ? floatval($data['lng']) : null;
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Validate coordinates
    if ($lat === null || $lng === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Latitude (lat) and longitude (lng) are required'
        ]);
        exit;
    }
    
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid coordinates'
        ]);
        exit;
    }
    
    $result = checkDeliveryAvailability($pdo, $lat, $lng);
    
    echo json_encode([
        'success' => true,
        'delivery' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
