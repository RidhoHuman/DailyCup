<?php
require_once __DIR__ . '/../api/jwt.php';
$token = trim(shell_exec('php ' . __DIR__ . '/generate_ci_admin_token.php'));
var_dump($token);
var_dump(JWT::verify($token));
