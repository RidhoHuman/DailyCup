<?php
/**
 * CORS Handler - NOW HANDLED BY .htaccess
 * 
 * IMPORTANT: CORS headers are set in .htaccess to avoid duplicate headers
 * This file now only handles OPTIONS preflight exit
 */

// .htaccess already sets all CORS headers
// DO NOT set headers here to avoid conflicts!

// Handle OPTIONS - exit immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
