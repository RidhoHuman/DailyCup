<?php
// Invoke notifications/count.php with a fake Authorization header for local testing
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ci-admin-token';
require __DIR__ . '/../api/notifications/count.php';
