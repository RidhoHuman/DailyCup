<?php
/**
 * Direct Analytics Endpoint Test
 * Test JWT verification and analytics endpoint
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImFkbWluQGdtYWlsLmNvbSIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTc3MDMyNDk0NSwiZXhwIjoxNzcwNDExMzQ1fQ.MmBzgOVyHEP77zNvngyKvpBTlWmemmeyWb_PZMM02mc';
$_GET['period'] = '30days';

echo "===========================================\n";
echo "🧪 Direct Analytics Endpoint Test\n";
echo "===========================================\n\n";

echo "1️⃣  Setup:\n";
echo "   METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "   AUTH: " . substr($_SERVER['HTTP_AUTHORIZATION'], 0, 50) . "...\n";
echo "   PERIOD: " . $_GET['period'] . "\n\n";

echo "2️⃣  Starting Analytics Endpoint...\n\n";

// Capture output
ob_start();

try {
    // Include the analytics endpoint
    include __DIR__ . '/api/analytics.php';
    
    $output = ob_get_clean();
    
    echo "3️⃣  Result:\n";
    echo "   HTTP Code: " . http_response_code() . "\n";
    echo "   Output Length: " . strlen($output) . " bytes\n\n";
    
    echo "4️⃣  Response:\n";
    // Pretty print JSON if possible
    $json = json_decode($output);
    if ($json) {
        echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $output . "\n";
    }
    
} catch (Throwable $e) {
    $output = ob_get_clean();
    
    echo "❌ ERROR:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n";
    echo $e->getTraceAsString() . "\n\n";
    
    if ($output) {
        echo "   Buffered Output:\n";
        echo $output . "\n";
    }
}

echo "\n===========================================\n";
