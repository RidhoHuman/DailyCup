<?php
// Debug: Check what origin is received
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'NO_ORIGIN';

header('Content-Type: application/json');
echo json_encode([
    'HTTP_ORIGIN' => $origin,
    'ALL_HEADERS' => getallheaders(),
    'SERVER' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'HTTP_HOST' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'NO_HOST'
    ]
]);
