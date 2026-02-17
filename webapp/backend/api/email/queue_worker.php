<?php
require_once __DIR__ . '/../cors.php';
/**
 * Email Queue Worker
 * 
 * Run this via cron job or manually:
 * php backend/api/email/queue_worker.php
 * 
 * Processes pending emails from queue
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/EmailQueue.php';

class EmailQueueWorker {
    public static function process() {
        EmailQueue::init();
        $pending = EmailQueue::getPending(10);
        
        $processed = 0;
        $failed = 0;
        
        foreach ($pending as $item) {
            try {
                $data = $item['data'];
                $file = $item['file'];
                
                // Send email using EmailService
                $success = EmailService::send(
                    $data['to'],
                    $data['subject'],
                    $data['htmlBody']
                );
                
                if ($success) {
                    EmailQueue::markSent($file);
                    $processed++;
                    echo "✓ Sent to: {$data['to']}\n";
                } else {
                    EmailQueue::markFailed($file);
                    $failed++;
                    echo "✗ Failed to: {$data['to']}\n";
                }
                
            } catch (Exception $e) {
                EmailQueue::markFailed($file);
                $failed++;
                echo "✗ Error: {$e->getMessage()}\n";
            }
        }
        
        $stats = EmailQueue::getStats();
        echo "\n=== Queue Stats ===\n";
        echo "Processed: $processed\n";
        echo "Failed: $failed\n";
        echo "Still Pending: {$stats['pending']}\n";
        echo "Total Failed: {$stats['failed']}\n";
        
        return $processed;
    }
}

// Run worker if executed directly
if (php_sapi_name() === 'cli') {
    echo "=== Email Queue Worker ===\n";
    EmailQueueWorker::process();
    echo "Done.\n";
}
?>
