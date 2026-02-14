<?php
require __DIR__ . '/../api/jwt.php';
// Simulate header
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ci-admin-token';
var_export(JWT::getUser());
echo PHP_EOL;