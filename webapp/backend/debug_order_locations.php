<?php
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');
$orderNumber = $argv[1] ?? ($_GET['order_number'] ?? 'ORD-1770749813-1259');
try {
    // Get order info
    $stmt = $pdo->prepare("SELECT id, order_number, kurir_id, delivery_lat, delivery_lng, status FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['error' => 'Order not found', 'order_number' => $orderNumber]);
        exit;
    }

    $kurirId = $order['kurir_id'];

    $locations = [];
    if ($kurirId) {
        $locStmt = $pdo->prepare("SELECT latitude, longitude, updated_at, accuracy, speed FROM kurir_location WHERE kurir_id = ? ORDER BY updated_at DESC LIMIT 10");
        $locStmt->execute([$kurirId]);
        $locations = $locStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Also get latest value used by track_all_kurirs query (subselect)
    $lastLocStmt = $pdo->prepare("SELECT kl.latitude as lat, kl.longitude as lng, kl.updated_at FROM kurir_location kl WHERE kl.kurir_id = ? ORDER BY kl.updated_at DESC LIMIT 1");
    $lastLocStmt->execute([$kurirId]);
    $lastLoc = $lastLocStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'order' => $order,
        'last_location' => $lastLoc ?: null,
        'recent_locations' => $locations
    ], JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
