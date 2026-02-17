<?php
// Simple probe to check whether PHP executes for OPTIONS in admin folder
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('X-Method: OPTIONS');
    header('Access-Control-Allow-Origin: *');
    http_response_code(204);
    exit;
}
header('X-Method: '.$_SERVER['REQUEST_METHOD']);
echo json_encode(['method'=>$_SERVER['REQUEST_METHOD']]);
