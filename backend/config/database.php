<?php
// Database configuration for DailyCup backend API
// Load .env file if exists (for production hosting)
$envPath = __DIR__ . '/../api/.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $env['DB_NAME'] ?? 'dailycup_db');
    define('DB_USER', $env['DB_USER'] ?? 'root');
    define('DB_PASS', $env['DB_PASS'] ?? '');
    define('APP_URL', rtrim($env['APP_URL'] ?? '', '/'));
    define('APP_NAME', $env['APP_NAME'] ?? 'DailyCup');
} else {
    // Fallback for local development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'dailycup_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('APP_URL', 'http://localhost');
    define('APP_NAME', 'DailyCup');
}

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