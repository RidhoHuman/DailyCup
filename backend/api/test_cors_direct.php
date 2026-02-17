<?php
// Direct CORS test - minimal file
require_once __DIR__ . '/cors.php';
// CORS handled centrally (cors.php / .htaccess)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

echo json_encode(['test' => 'CORS headers sent']);
