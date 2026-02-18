<?php
/**
 * CORS handler for API endpoints
 * - If a centralized backend `cors.php` exists, delegate to it (preferred).
 * - Otherwise fall back to a safe, origin-aware CORS response (handles OPTIONS + normal requests).
 */

// Prefer the centralized cors.php if available (webapp/backend/cors.php)
$centralCors = __DIR__ . '/../cors.php';
if (file_exists($centralCors)) {
    require_once $centralCors;
    // central cors may exit() on OPTIONS — stop further processing here
    return;
}

// Fallback behavior (keeps behavior compatible with central cors.php):
// - validate origin patterns (localhost, vercel, ngrok)
// - send Access-Control-Allow-Origin + credentials when allowed
// - respond to OPTIONS with proper Allow-* headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isAllowed = false;
if ($origin) {
    $isAllowed = preg_match('/(^https?:\/\/localhost(:\d+)?$)|\.vercel\.app$|\.ngrok(-free)?\.dev$/', $origin);
}

if ($isAllowed) {
    header("Access-Control-Allow-Origin: $origin", true);
    header('Access-Control-Allow-Credentials: true', true);
    header('Access-Control-Max-Age: 86400', true);
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma, ngrok-skip-browser-warning', true);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH', true);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code($isAllowed ? 204 : 403);
    exit();
}

