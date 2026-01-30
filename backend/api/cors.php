<?php
/**
 * CORS Handler - Centralized CORS configuration
 * Include this file at the VERY TOP of every API endpoint
 */

// IMPORTANT: This must be called BEFORE any output
function handleCors() {
    // Define allowed origins
    $allowed_origins = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://localhost',
        'http://127.0.0.1',
        'https://dailycup.vercel.app',
        'https://a21636405cf4.ngrok-free.app',
        'https://6005270bff1d.ngrok-free.app',
        'https://e3fccf16677f.ngrok-free.app'
    ];

    // Get origin from request header
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

    // Check if origin is allowed
    if (in_array($origin, $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        // Default to localhost:3001 for development (Next.js default when 3000 is busy)
        header('Access-Control-Allow-Origin: http://localhost:3001');
    }

    // Allow credentials (cookies, auth headers)
    header('Access-Control-Allow-Credentials: true');
    
    // Allowed methods
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
    
    // Allowed headers - include ngrok skip header so ngrok returns JSON instead of warning HTML
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, Pragma, ngrok-skip-browser-warning');

    // Vary by Origin to avoid caching cross-origin responses
    header('Vary: Origin');
    
    // Cache preflight for 1 hour
    header('Access-Control-Max-Age: 3600');

    // Handle preflight OPTIONS request immediately
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204); // No Content
        exit();
    }
}

// Auto-execute when included
handleCors();
