<?php
/**
 * Geocode an order's delivery address using OSM Nominatim and update the orders table
 * GET /api/geocode_order.php?order_id=123 OR ?order_number=ORD-...
 * (For PoC only — rate-limited and intended for dev/testing)
 */
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
$orderNumber = $_GET['order_number'] ?? null;

if (!$orderId && !$orderNumber) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id or order_number required']);
    exit;
}

try {
    if ($orderId) {
        $stmt = $pdo->prepare("SELECT id, order_number, delivery_address, delivery_lat, delivery_lng FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
    } else {
        $stmt = $pdo->prepare("SELECT id, order_number, delivery_address, delivery_lat, delivery_lng FROM orders WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
    }
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        exit;
    }

    if (!empty($order['delivery_lat']) && !empty($order['delivery_lng'])) {
        echo json_encode(['success' => true, 'message' => 'Order already has coordinates', 'order' => $order]);
        exit;
    }

    $address = trim($order['delivery_address']);
    if (!$address) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No delivery_address to geocode']);
        exit;
    }

    // Nominatim query
    $q = http_build_query([
        'q' => $address,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1
    ]);
    $url = "https://nominatim.openstreetmap.org/search?$q";

    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: DailyCup/1.0 (dev@dailycup.local)\r\nAccept: application/json\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    $result = @file_get_contents($url, false, $context);
    if ($result === false) {
        http_response_code(502);
        echo json_encode(['success' => false, 'error' => 'Geocoding request failed']);
        exit;
    }

    $json = json_decode($result, true);
    if (!is_array($json) || count($json) === 0) {
        // Mark failed
        $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([json_encode($json), $order['id']]);
        echo json_encode(['success' => false, 'message' => 'No results from geocoder', 'raw' => $json]);
        exit;
    }

    $place = $json[0];
    $lat = $place['lat'] ?? null;
    $lon = $place['lon'] ?? null;

    if (!$lat || !$lon) {
        $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([json_encode($place), $order['id']]);
        echo json_encode(['success' => false, 'message' => 'Geocoder returned no lat/lon', 'raw' => $place]);
        exit;
    }

    // Update order (success)
    $update = $pdo->prepare("UPDATE orders SET delivery_lat = ?, delivery_lng = ?, geocoded_at = NOW(), geocode_raw = ?, geocode_status = 'ok', geocode_attempts = 0, geocode_error = NULL WHERE id = ?");
    $update->execute([$lat, $lon, json_encode($place), $order['id']]);

    // If there is a geocode job, mark it done
    $pdo->prepare("UPDATE geocode_jobs SET status = 'done', updated_at = NOW() WHERE order_id = ?")->execute([$order['id']]);

    echo json_encode(['success' => true, 'order_id' => $order['id'], 'lat' => $lat, 'lon' => $lon, 'raw' => $place]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
