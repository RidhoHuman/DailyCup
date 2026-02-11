<?php
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug: Show all headers received
$all_headers = getallheaders();
$server_auth = $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET';
$server_redirect_auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET';

$debug_info = [
    'getallheaders' => $all_headers,
    'SERVER_HTTP_AUTHORIZATION' => $server_auth,
    'REDIRECT_HTTP_AUTHORIZATION' => $server_redirect_auth,
    'all_SERVER' => array_filter($_SERVER, function($key) {
        return strpos($key, 'HTTP_') === 0 || strpos($key, 'REDIRECT_') === 0;
    }, ARRAY_FILTER_USE_KEY)
];

echo json_encode($debug_info, JSON_PRETTY_PRINT);
