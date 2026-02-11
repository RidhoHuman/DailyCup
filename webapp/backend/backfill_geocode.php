<?php
/**
 * Backfill geocode for orders without coords (PoC)
 * Usage: php backfill_geocode.php [limit]
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/admin_notifier.php';

$limit = isset($argv[1]) ? intval($argv[1]) : 10;
$notifyThreshold = 3;

try {
    $stmt = $pdo->prepare("SELECT id, order_number, delivery_address FROM orders WHERE (delivery_lat IS NULL OR delivery_lng IS NULL) AND delivery_address IS NOT NULL AND delivery_address != '' LIMIT ?");
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$orders) {
        echo "No orders found for backfill.\n";
        exit;
    }

    echo "Found " . count($orders) . " orders to geocode.\n";

    foreach ($orders as $order) {
        echo "Geocoding order {$order['order_number']}... ";

        $address = trim($order['delivery_address']);
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
            echo "FAILED (request)\n";
            // mark failed attempt
            $err = 'Request failed';
            $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([$err, $order['id']]);
            
            // Notify admins if threshold reached
            $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
            $stmt2->execute([$order['id']]);
            $attempts = (int)$stmt2->fetchColumn();
            notifyAdmins($pdo, $order['id'], $err, $attempts, $notifyThreshold);
            
            sleep(1);
            continue;
        }

        $json = json_decode($result, true);
        if (!is_array($json) || count($json) === 0) {
            echo "NO_RESULT\n";
            $err = 'No results';
            $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([$err, $order['id']]);
            
            // Notify admins if threshold reached
            $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
            $stmt2->execute([$order['id']]);
            $attempts = (int)$stmt2->fetchColumn();
            notifyAdmins($pdo, $order['id'], $err, $attempts, $notifyThreshold);
            
            sleep(1);
            continue;
        }

        $place = $json[0];
        $lat = $place['lat'] ?? null;
        $lon = $place['lon'] ?? null;

        if (!$lat || !$lon) {
            echo "NO_LATLON\n";
            $err = 'Missing lat/lon';
            $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([$err, $order['id']]);
            
             // Notify admins if threshold reached
             $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
             $stmt2->execute([$order['id']]);
             $attempts = (int)$stmt2->fetchColumn();
             notifyAdmins($pdo, $order['id'], $err, $attempts, $notifyThreshold);

            sleep(1);
            continue;
        }

        $update = $pdo->prepare("UPDATE orders SET delivery_lat = ?, delivery_lng = ?, geocoded_at = NOW(), geocode_raw = ?, geocode_status = 'ok', geocode_attempts = 0, geocode_error = NULL WHERE id = ?");
        $update->execute([$lat, $lon, json_encode($place), $order['id']]);
        echo "OK ($lat,$lon)\n";

        // Sleep 1 second to be kind to Nominatim
        sleep(1);
    }

    echo "Backfill complete.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
