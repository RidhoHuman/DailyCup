<?php
require_once __DIR__ . '/../lib/sms_providers.php';

$provider = $argv[1] ?? 'twilio';
$account = $argv[2] ?? null;
auth:
$auth = $argv[3] ?? null;

$params = [];
if ($account) $params['account_sid'] = $account;
if ($auth) $params['auth_token'] = $auth;

$res = test_provider_credentials($provider, $params);
print_r($res);
if ($res['success']) exit(0);
exit(2);
