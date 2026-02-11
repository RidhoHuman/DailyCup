<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP working!\n\n";

// Test .env loading
$envPath = __DIR__ . '/.env';
echo "Looking for .env at: $envPath\n";
echo ".env exists: " . (file_exists($envPath) ? "YES" : "NO") . "\n\n";

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            if ($key === 'DB_HOST' || $key === 'DB_NAME' || $key === 'DB_USER' || $key === 'DB_PASSWORD') {
                echo "$key = '$value'\n";
            }
        }
    }
}

echo "\n\n--- Testing database connection ---\n";
require_once __DIR__ . '/../config/database.php';

echo "Database connected!\n";
echo "Connection object: " . get_class($conn) . "\n";

$result = $conn->query("SELECT COUNT(*) as total FROM products");
$row = $result->fetch();
echo "Products count: " . $row['total'] . "\n";
