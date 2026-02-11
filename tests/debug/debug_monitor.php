<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Monitor.php</h2><hr>";

try {
    require_once __DIR__ . '/includes/functions.php';
    echo "✅ functions.php loaded<br>";
    
    $db = getDB();
    echo "✅ Database connected<br>";
    
    // Test query 1: kurir with locations
    echo "<h3>Test Query 1: Kurir dengan lokasi</h3>";
    $stmt = $db->query("SELECT k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, k.vehicle_number, 
                       k.status, k.rating, k.total_deliveries, k.is_active, k.created_at,
                       kl.latitude, kl.longitude, kl.updated_at as last_location_update,
                       COUNT(DISTINCT o.id) as active_deliveries
                       FROM kurir k
                       LEFT JOIN kurir_location kl ON k.id = kl.kurir_id
                       LEFT JOIN orders o ON k.id = o.kurir_id 
                          AND o.status IN ('ready', 'delivering')
                       WHERE k.is_active = 1
                       GROUP BY k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, k.vehicle_number,
                                k.status, k.rating, k.total_deliveries, k.is_active, k.created_at,
                                kl.latitude, kl.longitude, kl.updated_at
                       ORDER BY k.status ASC, active_deliveries DESC");
    $kurirs = $stmt->fetchAll();
    echo "✅ Query 1 OK - Found " . count($kurirs) . " kurir<br>";
    
    // Test query 2: active orders
    echo "<h3>Test Query 2: Active orders</h3>";
    $stmt = $db->query("SELECT o.*, 
                       u.name as customer_name, u.phone as customer_phone,
                       k.name as kurir_name, k.phone as kurir_phone
                       FROM orders o
                       JOIN users u ON o.user_id = u.id
                       LEFT JOIN kurir k ON o.kurir_id = k.id
                       WHERE o.status IN ('ready', 'delivering')
                       ORDER BY o.status DESC, o.created_at ASC");
    $activeOrders = $stmt->fetchAll();
    echo "✅ Query 2 OK - Found " . count($activeOrders) . " active orders<br>";
    
    echo "<hr><h3>✅ All checks passed! Monitor.php should work.</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ ERROR:</h3>";
    echo "<pre style='background: #ffdddd; padding: 10px;'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
