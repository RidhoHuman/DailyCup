<?php
/**
 * JWT Token Debug Tool
 * Test JWT token validation and see detailed debug info
 * Usage: https://your-ngrok-url.ngrok-free.dev/DailyCup/webapp/backend/api/test_jwt.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/cors.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
];

// Check environment
$response['environment'] = [
    'jwt_secret_defined' => defined('JWT_SECRET'),
    'jwt_secret_source' => defined('JWT_SECRET') ? 'constant' : 'not defined',
    'jwt_secret_length' => defined('JWT_SECRET') ? strlen(JWT_SECRET) : 0,
    'jwt_secret_preview' => defined('JWT_SECRET') ? substr(JWT_SECRET, 0, 8) . '...' : 'N/A',
    'jwt_debug_env' => getenv('JWT_DEBUG'),
    'app_debug_env' => getenv('APP_DEBUG'),
    'db_host' => defined('DB_HOST') ? DB_HOST : 'not defined',
    'db_name' => defined('DB_NAME') ? DB_NAME : 'not defined',
];

// Check Authorization header
$headers = function_exists('getallheaders') ? getallheaders() : [];
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

$response['authorization'] = [
    'header_present' => !empty($authHeader),
    'header_format' => !empty($authHeader) ? (preg_match('/Bearer\s+/i', $authHeader) ? 'valid Bearer' : 'invalid format') : 'missing',
];

if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
    $response['authorization']['token_preview'] = substr($token, 0, 20) . '...';
    $response['authorization']['token_length'] = strlen($token);
    $response['authorization']['token_parts'] = count(explode('.', $token));
    
    // Try to decode token
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        try {
            $headerDecoded = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
            $payloadDecoded = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            
            $response['token_decoded'] = [
                'header' => $headerDecoded,
                'payload' => $payloadDecoded,
                'issued_at' => isset($payloadDecoded['iat']) ? date('Y-m-d H:i:s', $payloadDecoded['iat']) : 'N/A',
                'expires_at' => isset($payloadDecoded['exp']) ? date('Y-m-d H:i:s', $payloadDecoded['exp']) : 'N/A',
                'expired' => isset($payloadDecoded['exp']) ? ($payloadDecoded['exp'] < time()) : false,
            ];
        } catch (Exception $e) {
            $response['token_decoded'] = ['error' => $e->getMessage()];
        }
    }
    
    // Test JWT verification
    try {
        $user = JWT::getUser();
        if ($user) {
            $response['jwt_verification'] = [
                'success' => true,
                'user' => $user,
            ];
        } else {
            $response['jwt_verification'] = [
                'success' => false,
                'message' => 'Token validation failed (signature mismatch or expired)',
            ];
        }
    } catch (Exception $e) {
        $response['jwt_verification'] = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
} else {
    $response['authorization']['message'] = 'No Bearer token provided';
}

// Check PHP error log
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    $lastLines = array_slice(file($errorLogPath), -10);
    $jwtErrors = array_filter($lastLines, function($line) {
        return stripos($line, 'JWT') !== false;
    });
    $response['recent_jwt_errors'] = array_values($jwtErrors);
}

// Output
echo json_encode($response, JSON_PRETTY_PRINT);
