<?php
require_once __DIR__ . '/cors.php';
// Minimal CORS test with explicit origin
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : ''; 

// Set CORS headers BEFORE any output
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Avoid wildcard here — rely on Apache/php central CORS in hosted envs
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
// Access-Control-Allow-Headers handled centrally if Apache sets CORS


// Handle OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// For GET, return test data
header('Content-Type: application/json');
echo json_encode(['message' => 'CORS test successful', 'origin' => $origin]);
