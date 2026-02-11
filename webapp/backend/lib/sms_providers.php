<?php
/**
 * Simple SMS/Message provider adapter
 * Supports multiple providers through a single function.
 */

function send_sms_via_provider(string $provider, array $params): array {
    // params: to, from, body, extra
    $to = $params['to'] ?? null;
    $from = $params['from'] ?? null;
    $body = $params['body'] ?? '';

    if (!$to || !$from) {
        return ['success'=>false, 'error'=>'Missing to/from'];
    }

    if ($provider === 'twilio') {
        $sid = $params['account_sid'] ?? getenv('TWILIO_ACCOUNT_SID') ?: null;
        $auth = $params['auth_token'] ?? getenv('TWILIO_AUTH_TOKEN') ?: null;
        if (!$sid || !$auth) {
            return ['success'=>false, 'error'=>'Twilio credentials missing'];
        }
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $post = http_build_query(['To'=>$to, 'From'=>$from, 'Body'=>$body]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            $payload = json_decode($resp, true) ?: [];
            return ['success'=>true, 'sid'=>$payload['sid'] ?? null, 'status'=>$payload['status'] ?? null, 'payload'=>$payload, 'raw'=>$resp];
        }

        return ['success'=>false, 'error'=>$err ?: 'HTTP ' . $http, 'http'=>$http, 'raw'=>$resp];
    }

    // Mock provider for testing
    if ($provider === 'mock') {
        return ['success'=>true, 'sid'=>'MOCK-' . uniqid(), 'status'=>'sent', 'payload'=>['mock'=>true]];
    }

    return ['success'=>false, 'error'=>'Unknown provider'];
}

/**
 * Test provider credentials (lightweight)
 * Returns ['success'=>true] on valid creds or error info
 */
function test_provider_credentials(string $provider, array $params = []): array {
    if ($provider === 'twilio') {
        $sid = $params['account_sid'] ?? getenv('TWILIO_ACCOUNT_SID') ?: null;
        $auth = $params['auth_token'] ?? getenv('TWILIO_AUTH_TOKEN') ?: null;
        if (!$sid || !$auth) {
            return ['success'=>false, 'error'=>'Twilio credentials missing'];
        }
        // Lightweight account fetch
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}.json";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $auth);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($http >= 200 && $http < 300) {
            return ['success'=>true, 'provider'=>'twilio', 'account_sid'=>$sid];
        }
        return ['success'=>false, 'provider'=>'twilio', 'http'=>$http, 'error'=>$err ?: 'HTTP ' . $http, 'raw'=>$resp];
    }

    if ($provider === 'mock') {
        return ['success'=>true, 'provider'=>'mock'];
    }

    return ['success'=>false, 'error'=>'Unknown provider'];
}
