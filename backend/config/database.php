<?php
// Database configuration for DailyCup backend API
define('DB_HOST', 'localhost');
define('DB_NAME', 'dailycup_db');
define('DB_USER', 'root'); // Adjust as per your Laragon setup
define('DB_PASS', ''); // Usually empty for Laragon

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>