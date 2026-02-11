<?php
/**
 * Rate Limiter
 * 
 * Simple file-based rate limiting to protect API endpoints.
 * In production, use Redis for better performance.
 */

class RateLimiter {
    private static $storageDir;
    
    // Default limits
    private static $limits = [
        'default' => ['requests' => 100, 'window' => 60],      // 100 requests per minute
        'auth' => ['requests' => 5, 'window' => 60],           // 5 login attempts per minute
        'order' => ['requests' => 10, 'window' => 60],         // 10 orders per minute
        'webhook' => ['requests' => 50, 'window' => 60],       // 50 webhooks per minute
        'strict' => ['requests' => 3, 'window' => 60],         // 3 requests per minute (sensitive ops)
    ];

    public static function init() {
        self::$storageDir = __DIR__ . '/../data/rate_limits/';
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0755, true);
        }
    }

    /**
     * Check if request is allowed
     * 
     * @param string $identifier User IP or ID
     * @param string $type Limit type (default, auth, order, etc.)
     * @return bool True if allowed, false if rate limited
     */
    public static function check(string $identifier, string $type = 'default'): bool {
        self::init();
        
        $limit = self::$limits[$type] ?? self::$limits['default'];
        $key = md5($identifier . '_' . $type);
        $file = self::$storageDir . $key . '.json';
        
        $now = time();
        $data = [];
        
        // Load existing data
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: [];
        }
        
        // Clean old entries
        $windowStart = $now - $limit['window'];
        $data = array_filter($data, fn($timestamp) => $timestamp > $windowStart);
        
        // Check limit
        if (count($data) >= $limit['requests']) {
            self::setRateLimitHeaders($limit, count($data), $now - min($data));
            return false;
        }
        
        // Add current request
        $data[] = $now;
        file_put_contents($file, json_encode($data));
        
        self::setRateLimitHeaders($limit, count($data), $limit['window']);
        return true;
    }

    /**
     * Enforce rate limit (check and exit if exceeded)
     */
    public static function enforce(string $identifier, string $type = 'default'): void {
        if (!self::check($identifier, $type)) {
            http_response_code(429);
            header('Retry-After: 60');
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => 60
            ]);
            exit;
        }
    }

    /**
     * Get client IP address
     */
    public static function getClientIP(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Direct
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1';
    }

    /**
     * Clean up old rate limit files
     */
    public static function cleanup(): void {
        self::init();
        
        $files = glob(self::$storageDir . '*.json');
        $now = time();
        
        foreach ($files as $file) {
            // Delete files older than 1 hour
            if (filemtime($file) < $now - 3600) {
                unlink($file);
            }
        }
    }

    private static function setRateLimitHeaders(array $limit, int $used, int $reset): void {
        header('X-RateLimit-Limit: ' . $limit['requests']);
        header('X-RateLimit-Remaining: ' . max(0, $limit['requests'] - $used));
        header('X-RateLimit-Reset: ' . $reset);
    }
}
