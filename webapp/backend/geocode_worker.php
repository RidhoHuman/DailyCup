<?php
/**
 * Geocode worker: processes pending jobs in geocode_jobs
 * Run continuously or via cron: php geocode_worker.php
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/admin_notifier.php';

$maxAttempts = 5;
$batchSize = 5;
$notifyThreshold = 3; // when to notify admins about repeated failures

while (true) {
    try {
        // Fetch pending job
        $stmt = $pdo->prepare("SELECT j.id as job_id, j.order_id, o.delivery_address, o.geocode_attempts FROM geocode_jobs j JOIN orders o ON j.order_id = o.id WHERE j.status = 'pending' ORDER BY j.created_at ASC LIMIT ?");
        $stmt->bindValue(1, $batchSize, PDO::PARAM_INT);
        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$jobs) {
            // Sleep and retry
            sleep(5);
            continue;
        }

        foreach ($jobs as $job) {
            $jobId = $job['job_id'];
            $orderId = $job['order_id'];
            $address = trim($job['delivery_address']);

            // Mark processing
            $pdo->prepare("UPDATE geocode_jobs SET status = 'processing', updated_at = NOW() WHERE id = ?")->execute([$jobId]);

            if (empty($address)) {
                $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = 'No delivery address' WHERE id = ?")->execute([$orderId]);
                $pdo->prepare("UPDATE geocode_jobs SET status = 'failed', last_error = 'No delivery address', updated_at = NOW() WHERE id = ?")->execute([$jobId]);
                // Fetch attempts and possibly notify
                $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
                $stmt2->execute([$orderId]);
                $attempts = (int)$stmt2->fetchColumn();
                notifyAdmins($pdo, $orderId, 'No delivery address', $attempts, $notifyThreshold);
                continue;
            }

            // Geocode via Nominatim
            $q = http_build_query(['q' => $address, 'format' => 'json', 'limit' => 1, 'addressdetails' => 1]);
            $url = "https://nominatim.openstreetmap.org/search?$q";
            $opts = ['http' => ['method' => 'GET', 'header' => "User-Agent: DailyCup/1.0 (dev@dailycup.local)\r\nAccept: application/json\r\n"]];
            $context = stream_context_create($opts);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                $err = 'Request failed';
                $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([$err, $orderId]);
                $pdo->prepare("UPDATE geocode_jobs SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?")->execute([$err, $jobId]);
                // Fetch attempts and possibly notify admins
                $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
                $stmt2->execute([$orderId]);
                $attempts = (int)$stmt2->fetchColumn();
                notifyAdmins($pdo, $orderId, $err, $attempts, $notifyThreshold);
                sleep(1);
                continue;
            }
            $json = json_decode($result, true);
            if (!is_array($json) || count($json) === 0) {
                $err = 'No results';
                $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([$err, $orderId]);
                $pdo->prepare("UPDATE geocode_jobs SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?")->execute([$err, $jobId]);
                // Fetch attempts and possibly notify admins
                $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
                $stmt2->execute([$orderId]);
                $attempts = (int)$stmt2->fetchColumn();
                notifyAdmins($pdo, $orderId, $err, $attempts, $notifyThreshold);
                sleep(1);
                continue;
            }
            $place = $json[0];
            $lat = $place['lat'] ?? null;
            $lon = $place['lon'] ?? null;
            if (!$lat || !$lon) {
                $err = 'No lat/lon in result';
                $pdo->prepare("UPDATE orders SET geocode_status = 'failed', geocode_attempts = geocode_attempts + 1, geocode_error = ? WHERE id = ?")->execute([$err, $orderId]);
                $pdo->prepare("UPDATE geocode_jobs SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?")->execute([$err, $jobId]);
                // Fetch attempts and possibly notify admins
                $stmt2 = $pdo->prepare("SELECT geocode_attempts FROM orders WHERE id = ?");
                $stmt2->execute([$orderId]);
                $attempts = (int)$stmt2->fetchColumn();
                notifyAdmins($pdo, $orderId, $err, $attempts, $notifyThreshold);
                sleep(1);
                continue;
            }

            // Success: update order and mark job done
            $pdo->prepare("UPDATE orders SET delivery_lat = ?, delivery_lng = ?, geocoded_at = NOW(), geocode_raw = ?, geocode_status = 'ok', geocode_attempts = 0, geocode_error = NULL WHERE id = ?")->execute([$lat, $lon, json_encode($place), $orderId]);
            $pdo->prepare("UPDATE geocode_jobs SET status = 'done', updated_at = NOW() WHERE id = ?")->execute([$jobId]);
            echo "Job {$jobId} done: order {$orderId} -> {$lat},{$lon}\n";

            // Respect rate limits
            sleep(1);
        }
    } catch (Exception $e) {
        error_log("Geocode worker error: " . $e->getMessage());
        sleep(5);
    }
}
