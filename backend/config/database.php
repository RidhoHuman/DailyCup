<?php
// Database configuration for DailyCup backend API
// Prefer environment variables, then check common .env locations
$dbHost = getenv('DB_HOST') ?: null;
$dbName = getenv('DB_NAME') ?: null;
$dbUser = getenv('DB_USER') ?: null;
$dbPass = getenv('DB_PASS') ?: null;
$appUrl = getenv('APP_URL') ?: null;
$appName = getenv('APP_NAME') ?: null;

if (!$dbHost || !$dbUser) {
    $candidateFiles = [
        __DIR__ . '/../api/.env',
        __DIR__ . '/../.env',
        __DIR__ . '/../../.env',
        '/home/' . (getenv('USER') ?: '') . '/.env'
    ];

    foreach ($candidateFiles as $f) {
        if (file_exists($f)) {
            $env = parse_ini_file($f);
            $dbHost = $dbHost ?: ($env['DB_HOST'] ?? null);
            $dbName = $dbName ?: ($env['DB_NAME'] ?? null);
            $dbUser = $dbUser ?: ($env['DB_USER'] ?? null);
            $dbPass = $dbPass ?: ($env['DB_PASS'] ?? null);
            $appUrl = $appUrl ?: ($env['APP_URL'] ?? null);
            $appName = $appName ?: ($env['APP_NAME'] ?? null);
            break;
        }
    }
}

// Final defaults
define('DB_HOST', $dbHost ?: 'localhost');
define('DB_NAME', $dbName ?: 'dailycup_db');
define('DB_USER', $dbUser ?: 'root');
define('DB_PASS', $dbPass ?: '');
define('APP_URL', rtrim($appUrl ?: '', '/'));
define('APP_NAME', $appName ?: 'DailyCup');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}
?>