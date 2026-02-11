<?php
// Force clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared<br>";
} else {
    echo "ℹ️ OPcache not enabled<br>";
}

// Force reload config
require_once __DIR__ . '/config.php';
load_env(__DIR__ . '/.env');

echo "<h2>Current Environment Variables:</h2>";
echo "XENDIT_CALLBACK_URL: " . getenv('XENDIT_CALLBACK_URL') . "<br>";
echo "XENDIT_SECRET_KEY: " . (getenv('XENDIT_SECRET_KEY') ? '✅ Set' : '❌ Not set') . "<br>";

echo "<h2>Direct File Read:</h2>";
$envContent = file_get_contents(__DIR__ . '/.env');
preg_match('/XENDIT_CALLBACK_URL=(.+)/', $envContent, $matches);
echo "From file: " . ($matches[1] ?? 'Not found') . "<br>";
