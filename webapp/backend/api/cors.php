<?php
/**
 * CORS Handler - NOW HANDLED BY .htaccess
 * 
 * IMPORTANT: CORS headers are set in .htaccess to avoid duplicate headers
 * This file now only handles OPTIONS preflight exit
 */

// .htaccess already sets all CORS headers
// Keep this file lightweight but ensure OPTIONS preflight accepts the ngrok bypass header

// Handle OPTIONS - respond with necessary preflight headers
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Allow common headers + ngrok header so browser requests to ngrok do not fail
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma, ngrok-skip-browser-warning');
    http_response_code(204);
    exit();
}
