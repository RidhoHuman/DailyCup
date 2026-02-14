<?php
// Simulate an HTTP request to admin/analytics.php with a provided Bearer token
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'summary';
// token with admin role but invalid signature (for fallback test)
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJub25lIn0.eyJ1c2VyX2lkIjoxLCJyb2xlIjoiYWRtaW4iLCJlbWFpbCI6ImFkbWluQGV4YW1wbGUuY29tIiwiaWF0IjoxNjc5MDAwMDAwLCJleHAiOjE3NzA5MzcwNzR9.x';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
require __DIR__ . '/../api/admin/analytics.php';
