<?php
// Lightweight auth helper used by API endpoints
require_once __DIR__ . '/jwt.php';

/**
 * Validate Authorization header and return decoded user payload or null
 */
function validateToken() {
    // Dev-only bypass (set DEV_AUTH_BYPASS=true for local testing) - returns an admin user
    if (strtolower(getenv('DEV_AUTH_BYPASS') ?: '') === 'true') {
        return ['id' => 'dev', 'role' => 'admin', 'email' => 'dev@example.com'];
    }

    $user = JWT::getUser();
    if ($user) return $user;

    // Fallback for local/CI tests: accept token via GET/POST param or BACKEND_AUTH_TOKEN env var when header not present
    $tok = $_GET['token'] ?? $_POST['token'] ?? (getenv('BACKEND_AUTH_TOKEN') ?: null);
    if ($tok) {
        $u = JWT::verify($tok);
        if ($u) return $u;
    }

    return null;
}

/**
 * Require admin role and return user payload (will exit with 401/403 on failure)
 */
function requireAdmin() {
    return JWT::requireAdmin();
}
