<?php
// Test access monitor page
echo "Testing admin/kurir/monitor.php access...\n\n";

// Simulate admin session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Capture output
ob_start();
try {
    include 'admin/kurir/monitor.php';
    $output = ob_get_clean();
    
    if (strpos($output, 'HTTP ERROR 500') !== false || strpos($output, 'Fatal error') !== false) {
        echo "❌ ERROR detected in output!\n";
        echo substr($output, 0, 500);
    } else {
        echo "✅ Page loaded successfully!\n";
        echo "Output length: " . strlen($output) . " bytes\n";
        echo "Contains map: " . (strpos($output, 'monitorMap') !== false ? 'YES' : 'NO') . "\n";
        echo "Contains Leaflet: " . (strpos($output, 'leaflet') !== false ? 'YES' : 'NO') . "\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ EXCEPTION CAUGHT:\n";
    echo $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
