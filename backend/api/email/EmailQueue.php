<?php
/**
 * Email Queue - Async Email Handling
 * 
 * Stores emails in queue instead of sending immediately
 * Reduces API response time from ~5-10s to <100ms
 */

class EmailQueue {
    private static $queueDir;
    
    public static function init() {
        self::$queueDir = __DIR__ . '/../queue';
        
        // Create queue directory if not exists
        if (!is_dir(self::$queueDir)) {
            mkdir(self::$queueDir, 0755, true);
        }
    }
    
    /**
     * Add email to queue
     * @return bool Success status
     */
    public static function add($to, $subject, $htmlBody, $templateName = null) {
        self::init();
        
        $emailData = [
            'to' => $to,
            'subject' => $subject,
            'htmlBody' => $htmlBody,
            'templateName' => $templateName,
            'timestamp' => time(),
            'attempts' => 0,
            'status' => 'pending'
        ];
        
        $queueFile = self::$queueDir . '/' . uniqid('email_', true) . '.json';
        $success = file_put_contents($queueFile, json_encode($emailData));
        
        if ($success) {
            error_log("Email queued: $to - $subject");
            return true;
        } else {
            error_log("Failed to queue email: $to");
            return false;
        }
    }
    
    /**
     * Get pending emails from queue
     */
    public static function getPending($limit = 10) {
        self::init();
        
        $files = glob(self::$queueDir . '/*.json');
        $pending = [];
        
        foreach (array_slice($files, 0, $limit) as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['status'] === 'pending') {
                $pending[] = [
                    'file' => $file,
                    'data' => $data
                ];
            }
        }
        
        return $pending;
    }
    
    /**
     * Mark email as sent
     */
    public static function markSent($file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Mark email as failed
     */
    public static function markFailed($file) {
        $data = json_decode(file_get_contents($file), true);
        $data['attempts']++;
        $data['last_error'] = date('Y-m-d H:i:s');
        
        // Retry max 3 times
        if ($data['attempts'] >= 3) {
            $data['status'] = 'failed';
        }
        
        file_put_contents($file, json_encode($data));
    }
    
    /**
     * Get queue stats
     */
    public static function getStats() {
        self::init();
        
        $files = glob(self::$queueDir . '/*.json');
        $pending = 0;
        $failed = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data['status'] === 'pending') $pending++;
            elseif ($data['status'] === 'failed') $failed++;
        }
        
        return [
            'total' => count($files),
            'pending' => $pending,
            'failed' => $failed
        ];
    }
}
?>
