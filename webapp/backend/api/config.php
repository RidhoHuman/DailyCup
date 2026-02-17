<?php
require_once __DIR__ . '/cors.php';
// Simple .env loader for local dev. Loads KEY=VALUE pairs into environment variables.
function load_env($path = __DIR__ . '/.env') {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Strip optional quotes
        $val = preg_replace('/^"(.*)"$/', '$1', $val);
        $val = preg_replace("/^'(.*)'$/", '$1', $val);
        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}
load_env();

// Also define JWT_SECRET constant for use by JWT class if not already defined
if (!defined('JWT_SECRET') && getenv('JWT_SECRET')) {
    define('JWT_SECRET', getenv('JWT_SECRET'));
}
