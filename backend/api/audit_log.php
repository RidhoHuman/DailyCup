<?php
/**
 * Audit Logger
 * 
 * Track important actions for security and compliance.
 */

class AuditLog {
    private static $logDir;
    
    // Action types
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';
    const ACTION_LOGIN_FAILED = 'LOGIN_FAILED';
    const ACTION_REGISTER = 'REGISTER';
    const ACTION_PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    const ACTION_PASSWORD_RESET = 'PASSWORD_RESET';
    const ACTION_ORDER_CREATE = 'ORDER_CREATE';
    const ACTION_ORDER_UPDATE = 'ORDER_UPDATE';
    const ACTION_ORDER_CANCEL = 'ORDER_CANCEL';
    const ACTION_PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    const ACTION_PAYMENT_FAILED = 'PAYMENT_FAILED';
    const ACTION_REFUND = 'REFUND';
    const ACTION_PRODUCT_CREATE = 'PRODUCT_CREATE';
    const ACTION_PRODUCT_UPDATE = 'PRODUCT_UPDATE';
    const ACTION_PRODUCT_DELETE = 'PRODUCT_DELETE';
    const ACTION_USER_UPDATE = 'USER_UPDATE';
    const ACTION_ADMIN_ACCESS = 'ADMIN_ACCESS';
    const ACTION_API_ERROR = 'API_ERROR';
    const ACTION_SECURITY_ALERT = 'SECURITY_ALERT';

    public static function init() {
        self::$logDir = __DIR__ . '/../data/audit_logs/';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    /**
     * Log an action
     * 
     * @param string $action Action type (use constants)
     * @param array $data Additional data to log
     * @param string|null $userId User ID if applicable
     * @param string $level Log level (info, warning, error, critical)
     */
    public static function log(
        string $action,
        array $data = [],
        ?string $userId = null,
        string $level = 'info'
    ): void {
        self::init();

        $entry = [
            'id' => uniqid('log_', true),
            'timestamp' => date('Y-m-d H:i:s.u'),
            'action' => $action,
            'level' => $level,
            'user_id' => $userId,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'data' => $data
        ];

        // Add geo info if available (simplified)
        $entry['country'] = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;

        // Write to daily log file
        $logFile = self::$logDir . date('Y-m-d') . '.json';
        $logs = [];
        
        if (file_exists($logFile)) {
            $logs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        
        $logs[] = $entry;
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

        // Also log to PHP error log for critical events
        if (in_array($level, ['error', 'critical'])) {
            error_log("AUDIT [$level] [$action]: " . json_encode($data));
        }

        // Send alert for critical security events
        if ($level === 'critical') {
            self::sendSecurityAlert($entry);
        }
    }

    /**
     * Log successful login
     */
    public static function logLogin(string $userId, string $email): void {
        self::log(self::ACTION_LOGIN, ['email' => $email], $userId);
    }

    /**
     * Log failed login attempt
     */
    public static function logLoginFailed(string $email, string $reason = 'Invalid credentials'): void {
        self::log(self::ACTION_LOGIN_FAILED, [
            'email' => $email,
            'reason' => $reason
        ], null, 'warning');
    }

    /**
     * Log order creation
     */
    public static function logOrderCreate(string $orderId, float $total, ?string $userId = null): void {
        self::log(self::ACTION_ORDER_CREATE, [
            'order_id' => $orderId,
            'total' => $total
        ], $userId);
    }

    /**
     * Log payment received
     */
    public static function logPaymentReceived(string $orderId, string $paymentId, float $amount, string $provider): void {
        self::log(self::ACTION_PAYMENT_RECEIVED, [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'provider' => $provider
        ]);
    }

    /**
     * Log security alert
     */
    public static function logSecurityAlert(string $type, array $data = []): void {
        self::log(self::ACTION_SECURITY_ALERT, array_merge(['type' => $type], $data), null, 'critical');
    }

    /**
     * Log API error
     */
    public static function logApiError(string $endpoint, string $error, int $statusCode = 500): void {
        self::log(self::ACTION_API_ERROR, [
            'endpoint' => $endpoint,
            'error' => $error,
            'status_code' => $statusCode
        ], null, 'error');
    }

    /**
     * Get logs for a specific date
     */
    public static function getLogs(string $date = null): array {
        self::init();
        
        $date = $date ?: date('Y-m-d');
        $logFile = self::$logDir . $date . '.json';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        return json_decode(file_get_contents($logFile), true) ?: [];
    }

    /**
     * Get logs by action type
     */
    public static function getLogsByAction(string $action, int $limit = 100): array {
        self::init();
        
        $allLogs = [];
        $files = glob(self::$logDir . '*.json');
        
        // Sort by newest first
        rsort($files);
        
        foreach ($files as $file) {
            $logs = json_decode(file_get_contents($file), true) ?: [];
            
            foreach ($logs as $log) {
                if ($log['action'] === $action) {
                    $allLogs[] = $log;
                    if (count($allLogs) >= $limit) {
                        return $allLogs;
                    }
                }
            }
        }
        
        return $allLogs;
    }

    /**
     * Get logs by user ID
     */
    public static function getLogsByUser(string $userId, int $limit = 100): array {
        self::init();
        
        $allLogs = [];
        $files = glob(self::$logDir . '*.json');
        
        rsort($files);
        
        foreach ($files as $file) {
            $logs = json_decode(file_get_contents($file), true) ?: [];
            
            foreach ($logs as $log) {
                if ($log['user_id'] === $userId) {
                    $allLogs[] = $log;
                    if (count($allLogs) >= $limit) {
                        return $allLogs;
                    }
                }
            }
        }
        
        return $allLogs;
    }

    /**
     * Clean up old logs (keep last 90 days)
     */
    public static function cleanup(int $daysToKeep = 90): int {
        self::init();
        
        $cutoff = strtotime("-{$daysToKeep} days");
        $files = glob(self::$logDir . '*.json');
        $deleted = 0;
        
        foreach ($files as $file) {
            $filename = basename($file, '.json');
            $fileDate = strtotime($filename);
            
            if ($fileDate && $fileDate < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Get summary statistics
     */
    public static function getStats(string $date = null): array {
        $logs = self::getLogs($date);
        
        $stats = [
            'total' => count($logs),
            'by_action' => [],
            'by_level' => [],
            'unique_users' => [],
            'unique_ips' => []
        ];
        
        foreach ($logs as $log) {
            $action = $log['action'] ?? 'unknown';
            $level = $log['level'] ?? 'info';
            
            $stats['by_action'][$action] = ($stats['by_action'][$action] ?? 0) + 1;
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            
            if (!empty($log['user_id'])) {
                $stats['unique_users'][$log['user_id']] = true;
            }
            if (!empty($log['ip'])) {
                $stats['unique_ips'][$log['ip']] = true;
            }
        }
        
        $stats['unique_users'] = count($stats['unique_users']);
        $stats['unique_ips'] = count($stats['unique_ips']);
        
        return $stats;
    }

    private static function getClientIP(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
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

    private static function sendSecurityAlert(array $entry): void {
        // In production, send to admin email or Slack/Discord webhook
        $alertEmail = getenv('SECURITY_ALERT_EMAIL');
        
        if ($alertEmail) {
            $subject = "[SECURITY ALERT] DailyCup - {$entry['action']}";
            $message = "Security Alert Detected\n\n";
            $message .= "Action: {$entry['action']}\n";
            $message .= "Time: {$entry['timestamp']}\n";
            $message .= "IP: {$entry['ip']}\n";
            $message .= "Data: " . json_encode($entry['data'], JSON_PRETTY_PRINT) . "\n";
            
            // Use mail() or PHPMailer in production
            error_log("SECURITY ALERT would be sent to $alertEmail: $subject");
        }
    }
}
