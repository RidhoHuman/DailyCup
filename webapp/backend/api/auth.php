<?php
require_once __DIR__ . '/cors.php';
// Lightweight auth helper used by API endpoints
require_once __DIR__ . '/jwt.php';

/**
 * Validate Authorization header and return decoded user payload or null
 */
function validateToken() {
    // Strict mode: hanya JWT valid yang diterima
    $user = JWT::getUser();
    if ($user) return $user;
    return null;
}

/**
 * Require admin role and return user payload (will exit with 401/403 on failure)
 */
function requireAdmin() {
    return JWT::requireAdmin();
}