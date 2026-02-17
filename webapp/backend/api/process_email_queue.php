<?php
/**
 * Email Queue Processor API
 * 
 * Endpoint: GET/POST /api/process_email_queue.php
 * 
 * Processes pending emails from the queue
 * Can be called via:
 * - cron job: curl http://localhost/DailyCup/webapp/backend/api/process_email_queue.php
 * - PHP: php backend/api/process_email_queue.php
 * - API: fetch('/api/process_email_queue.php')
 */
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';
require_once __DIR__ . '/email/EmailQueue.php';

header('Content-Type: application/json');

try {
    EmailQueue::init();
    $pending = EmailQueue::getPending(10);
    
    if (empty($pending)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'No pending emails to process',
            'processed' => 0,
            'stats' => EmailQueue::getStats()
        ]);
        exit;
    }
    
    $processed = 0;
    $failed = 0;
    $results = [];
    
    foreach ($pending as $item) {
        try {
            $data = $item['data'];
            $file = $item['file'];
            
            // Disable queue to send directly
            EmailService::setUseQueue(false);
            EmailService::init();
            
            // Send email using direct method
            $success = EmailService::send(
                $data['to'],
                $data['subject'],
                $data['htmlBody']
            );
            
            if ($success) {
                EmailQueue::markSent($file);
                $processed++;
                $results[] = [
                    'to' => $data['to'],
                    'subject' => $data['subject'],
                    'status' => 'sent'
                ];
            } else {
                EmailQueue::markFailed($file);
                $failed++;
                $results[] = [
                    'to' => $data['to'],
                    'subject' => $data['subject'],
                    'status' => 'failed'
                ];
            }
            
        } catch (Exception $e) {
            if (isset($file)) {
                EmailQueue::markFailed($file);
            }
            $failed++;
            $results[] = [
                'to' => $data['to'] ?? 'unknown',
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
    
    $stats = EmailQueue::getStats();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Processed $processed emails successfully",
        'processed' => $processed,
        'failed' => $failed,
        'results' => $results,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
