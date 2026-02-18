<?php
require_once __DIR__ . '/../cors.php';
/**
 * Process Email Queue
 * Send all pending emails
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/EmailQueue.php';
require_once __DIR__ . '/EmailService.php';

// Force direct sending (no queue recursion!)
EmailService::setUseQueue(false);

$processed = 0;
$failed = 0;
$errors = [];

echo "Processing email queue...\n\n";

try {
    $pending = EmailQueue::getPending(50); // Process up to 50 emails
    
    echo "Found " . count($pending) . " pending emails\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($pending as $item) {
        $file = $item['file'];
        $data = $item['data'];
        
        echo "\nProcessing: " . basename($file) . "\n";
        echo "To: {$data['to']}\n";
        echo "Subject: {$data['subject']}\n";
        echo "Attempts: {$data['attempts']}\n";
        
        try {
            // Send directly via EmailService
            $result = EmailService::send($data['to'], $data['subject'], $data['htmlBody']);
            
            if ($result) {
                echo "✅ Sent successfully!\n";
                EmailQueue::markSent($file);
                $processed++;
            } else {
                echo "❌ Failed to send\n";
                EmailQueue::markFailed($file);
                $failed++;
                $errors[] = "{$data['to']} - {$data['subject']}";
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
            EmailQueue::markFailed($file);
            $failed++;
            $errors[] = "{$data['to']} - {$data['subject']}: " . $e->getMessage();
        }
        
        // Sleep briefly to avoid rate limiting
        usleep(100000); // 0.1 second
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "SUMMARY:\n";
    echo "Processed: $processed emails\n";
    echo "Failed: $failed emails\n";
    
    if (!empty($errors)) {
        echo "\nFailed emails:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
