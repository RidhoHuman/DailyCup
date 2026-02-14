<?php
require __DIR__ . '/../api/jwt.php';
$header = ['typ'=>'JWT','alg'=>'none'];
$payload = ['user_id'=>1,'role'=>'admin','email'=>'admin@example.com','iat'=>time(),'exp'=>time()+3600];
$h = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
$p = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
$token = "$h.$p.x"; // invalid signature intentionally
echo "TEST TOKEN: $token\n";
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
var_export(JWT::getUser());
echo PHP_EOL;