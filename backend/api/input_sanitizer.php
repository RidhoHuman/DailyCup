<?php
/**
 * Input Sanitizer & Validator
 * 
 * Comprehensive input validation and sanitization to prevent XSS, SQL injection, etc.
 */

class InputSanitizer {
    
    /**
     * Sanitize string input
     */
    public static function string(?string $input, int $maxLength = 255): string {
        if ($input === null) {
            return '';
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Strip tags
        $input = strip_tags($input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Trim whitespace
        $input = trim($input);
        
        // Limit length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Sanitize email
     */
    public static function email(?string $email): ?string {
        if (empty($email)) {
            return null;
        }
        
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        return strtolower($email);
    }
    
    /**
     * Sanitize phone number
     */
    public static function phone(?string $phone): ?string {
        if (empty($phone)) {
            return null;
        }
        
        // Remove all non-numeric characters except + for international format
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Validate length
        if (strlen($phone) < 8 || strlen($phone) > 20) {
            return null;
        }
        
        return $phone;
    }
    
    /**
     * Sanitize integer
     */
    public static function int($input, ?int $min = null, ?int $max = null): ?int {
        if ($input === null || $input === '') {
            return null;
        }
        
        $value = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            return null;
        }
        
        if ($min !== null && $value < $min) {
            return $min;
        }
        
        if ($max !== null && $value > $max) {
            return $max;
        }
        
        return $value;
    }
    
    /**
     * Sanitize float/decimal
     */
    public static function float($input, ?float $min = null, ?float $max = null): ?float {
        if ($input === null || $input === '') {
            return null;
        }
        
        $value = filter_var($input, FILTER_VALIDATE_FLOAT);
        
        if ($value === false) {
            return null;
        }
        
        if ($min !== null && $value < $min) {
            return $min;
        }
        
        if ($max !== null && $value > $max) {
            return $max;
        }
        
        return $value;
    }
    
    /**
     * Sanitize boolean
     */
    public static function bool($input): bool {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Sanitize URL
     */
    public static function url(?string $url): ?string {
        if (empty($url)) {
            return null;
        }
        
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Only allow http and https
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return null;
        }
        
        return $url;
    }
    
    /**
     * Sanitize array of strings
     */
    public static function stringArray(?array $input, int $maxLength = 255): array {
        if (!is_array($input)) {
            return [];
        }
        
        return array_map(fn($item) => self::string($item, $maxLength), $input);
    }
    
    /**
     * Sanitize date string
     */
    public static function date(?string $date, string $format = 'Y-m-d'): ?string {
        if (empty($date)) {
            return null;
        }
        
        $dateObj = \DateTime::createFromFormat($format, $date);
        
        if (!$dateObj || $dateObj->format($format) !== $date) {
            return null;
        }
        
        return $date;
    }
    
    /**
     * Sanitize datetime string
     */
    public static function datetime(?string $datetime): ?string {
        if (empty($datetime)) {
            return null;
        }
        
        $timestamp = strtotime($datetime);
        
        if ($timestamp === false) {
            return null;
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * Validate against allowed values (enum)
     */
    public static function enum(?string $value, array $allowed, ?string $default = null): ?string {
        if (empty($value) || !in_array($value, $allowed)) {
            return $default;
        }
        
        return $value;
    }
    
    /**
     * Sanitize ID (alphanumeric + underscore/dash)
     */
    public static function id(?string $id): ?string {
        if (empty($id)) {
            return null;
        }
        
        // Only allow alphanumeric, underscore, and dash
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            return null;
        }
        
        return $id;
    }
    
    /**
     * Sanitize JSON input and return as array
     */
    public static function json(?string $json): ?array {
        if (empty($json)) {
            return null;
        }
        
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Get and sanitize request body as JSON
     */
    public static function getJsonBody(): ?array {
        $input = file_get_contents('php://input');
        return self::json($input);
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        return $errors;
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
