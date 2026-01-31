<?php
/**
 * Simple DB test endpoint for deployment verification
 * Usage: curl -i https://api.dailycup.com/api/db_test.php
 * IMPORTANT: Remove or protect this file on production after testing.
 */

header('Content-Type: application/json');

// Try getenv() first, then parse .env files as fallback
$dbHost = getenv('DB_HOST') ?: null;
$dbName = getenv('DB_NAME') ?: null;
$dbUser = getenv('DB_USER') ?: null;
$dbPass = getenv('DB_PASS') ?: null;

if (!$dbHost || !$dbUser) {
    // Look for .env nearby (common locations)
    $candidateFiles = [__DIR__ . '/.env', __DIR__ . '/../.env', __DIR__ . '/../../.env', __DIR__ . '/../../.env.production', '/home/' . (getenv('USER') ?: '') . '/.env'];
    foreach ($candidateFiles as $f) {
        if (file_exists($f)) {
            $lines = file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'DB_HOST=') === 0) $dbHost = $dbHost ?: trim(substr($line, 8));
                if (strpos($line, 'DB_NAME=') === 0) $dbName = $dbName ?: trim(substr($line, 8));
                if (strpos($line, 'DB_USER=') === 0) $dbUser = $dbUser ?: trim(substr($line, 8));
                if (strpos($line, 'DB_PASS=') === 0) $dbPass = $dbPass ?: trim(substr($line, 8));
            }
            break;
        }
    }
}

$result = ['ok' => false, 'details' => []];

if (!$dbHost || !$dbUser) {
    http_response_code(500);
    $result['details'][] = 'DB credentials not found in environment or .env files.';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    http_response_code(500);
    $result['details'][] = 'Connection error: ' . $mysqli->connect_error;
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Minimal test query
$res = $mysqli->query("SELECT 1 as ok LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $result['ok'] = true;
    $result['details'][] = "DB connected (host={$dbHost}, db={$dbName})";
    $result['tables'] = [];
    $tablesRes = $mysqli->query("SHOW TABLES LIMIT 10");
    if ($tablesRes) {
        while ($r = $tablesRes->fetch_row()) $result['tables'][] = $r[0];
    }
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    $result['details'][] = 'Test query failed.';
    echo json_encode($result, JSON_PRETTY_PRINT);
}

$mysqli->close();
