<?php
require_once __DIR__ . '/cors.php';
/**
 * CSRF Protection
 * 
 * Generate and validate CSRF tokens to protect against cross-site request forgery.
 */

class CSRF {
    private static $tokenName = 'csrf_token';
    private static $tokenLength = 32;
    private static $expiry = 3600; // 1 hour

    /**
     * Generate a new CSRF token
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(self::$tokenLength));
        $_SESSION[self::$tokenName] = [
            'token' => $token,
            'expires' => time() + self::$expiry
        ];

        return $token;
    }

    /**
     * Get current token or generate new one
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stored = $_SESSION[self::$tokenName] ?? null;
        
        // Generate new token if none exists or expired
        if (!$stored || $stored['expires'] < time()) {
            return self::generateToken();
        }

        return $stored['token'];
    }

    /**
     * Validate a CSRF token
     */
    public static function validate(?string $token): bool {
        if (empty($token)) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stored = $_SESSION[self::$tokenName] ?? null;

        if (!$stored) {
            return false;
        }

        // Check expiry
        if ($stored['expires'] < time()) {
            unset($_SESSION[self::$tokenName]);
            return false;
        }

        // Timing-safe comparison
        return hash_equals($stored['token'], $token);
    }

    /**
     * Get token from request (header or body)
     */
    public static function getFromRequest(): ?string {
        // Check header first
        $headers = getallheaders();
        $headerToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
        
        if ($headerToken) {
            return $headerToken;
        }

        // Check request body for JSON
        $input = file_get_contents('php://input');
        if ($input) {
            $data = json_decode($input, true);
            if (isset($data['_csrf'])) {
                return $data['_csrf'];
            }
        }

        // Check POST data
        if (isset($_POST['_csrf'])) {
            return $_POST['_csrf'];
        }

        return null;
    }

    /**
     * Enforce CSRF validation (validate and exit if invalid)
     */
    public static function enforce(): void {
        $token = self::getFromRequest();
        
        if (!self::validate($token)) {
            http_response_code(403);
            echo json_encode([
                'error' => 'CSRF Validation Failed',
                'message' => 'Invalid or missing CSRF token'
            ]);
            exit;
        }
    }

    /**
     * Regenerate token after successful validation (prevents replay)
     */
    public static function regenerate(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$tokenName]);
        return self::generateToken();
    }

    /**
     * Get HTML hidden input field
     */
    public static function getHiddenInput(): string {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get meta tag for JavaScript usage
     */
    public static function getMetaTag(): string {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
