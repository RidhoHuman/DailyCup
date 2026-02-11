<?php
// Database configuration for DailyCup backend API
// Check if config is already loaded (e.g., from root config/database.php)
if (!defined('DB_HOST')) {
    // Prefer environment variables, then check common .env locations
    $dbHost = getenv('DB_HOST') ?: null;
    $dbName = getenv('DB_NAME') ?: null;
    $dbUser = getenv('DB_USER') ?: null;
    $dbPass = getenv('DB_PASSWORD') ?: null;
    $appUrl = getenv('APP_URL') ?: null;
    $appName = getenv('APP_NAME') ?: null;
    $jwtSecret = getenv('JWT_SECRET') ?: null;

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
                $dbPass = $dbPass ?: ($env['DB_PASSWORD'] ?? null);
                $appUrl = $appUrl ?: ($env['APP_URL'] ?? null);
                $appName = $appName ?: ($env['APP_NAME'] ?? null);
                $jwtSecret = $jwtSecret ?: ($env['JWT_SECRET'] ?? null);
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
    
    // JWT configuration
    if (!defined('JWT_SECRET')) {
        define('JWT_SECRET', $jwtSecret ?: 'default-secret-key');
    }
}

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo = $conn; // Alias for compatibility
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}

// MySQLi connection helper class for legacy compatibility
if (!class_exists('Database')) {
    class Database {
        private static $mysqli_instance = null;
        
        public static function getConnection() {
            if (self::$mysqli_instance === null) {
                self::$mysqli_instance = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                
                if (self::$mysqli_instance->connect_error) {
                    die('Database connection failed: ' . self::$mysqli_instance->connect_error);
                }
                
                self::$mysqli_instance->set_charset('utf8mb4');
            }
            
            return self::$mysqli_instance;
        }
    }
}
?>