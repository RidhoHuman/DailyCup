<?php
// Quick tester to run admin/analytics.php with a fake Authorization header
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'summary';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ci-admin-token';
require __DIR__ . '/../api/admin/analytics.php';
