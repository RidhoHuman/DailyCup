<?php
/**
 * Email Queue Dashboard
 * 
 * Endpoint: GET /api/admin/email_queue_dashboard.php
 * Shows queue status and allows manual processing
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../email/EmailService.php';
require_once __DIR__ . '/../email/EmailQueue.php';

// Get action from query string
$action = $_GET['action'] ?? 'status';

try {
    EmailQueue::init();
    
    switch ($action) {
        case 'status':
            // Get queue status
            $stats = EmailQueue::getStats();
            $pending = EmailQueue::getPending(20);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'action' => 'status',
                'stats' => $stats,
                'pending_emails' => array_map(function($item) {
                    return [
                        'file' => basename($item['file']),
                        'to' => $item['data']['to'],
                        'subject' => $item['data']['subject'],
                        'timestamp' => date('Y-m-d H:i:s', $item['data']['timestamp'] ?? 0)
                    ];
                }, $pending)
            ]);
            break;
            
        case 'process':
            // Process pending emails
            $pending = EmailQueue::getPending(10);
            $processed = 0;
            $failed = 0;
            $results = [];
            
            EmailService::setUseQueue(false);
            EmailService::init();
            
            foreach ($pending as $item) {
                try {
                    $data = $item['data'];
                    $file = $item['file'];
                    
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
                            'status' => 'sent',
                            'timestamp' => date('Y-m-d H:i:s')
                        ];
                    } else {
                        EmailQueue::markFailed($file);
                        $failed++;
                        $results[] = [
                            'to' => $data['to'],
                            'status' => 'failed',
                            'error' => 'mail() returned false'
                        ];
                    }
                } catch (Exception $e) {
                    if (isset($file)) {
                        EmailQueue::markFailed($file);
                    }
                    $failed++;
                    $results[] = [
                        'to' => $data['to'] ?? 'unknown',
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $stats = EmailQueue::getStats();
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'action' => 'process',
                'processed' => $processed,
                'failed' => $failed,
                'results' => $results,
                'stats' => $stats
            ]);
            break;
            
        case 'clear':
            // Clear all queue files (dangerous!)
            $pending = EmailQueue::getPending(1000);
            $cleared = 0;
            
            foreach ($pending as $item) {
                if (file_exists($item['file'])) {
                    unlink($item['file']);
                    $cleared++;
                }
            }
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'action' => 'clear',
                'cleared' => $cleared,
                'message' => "Cleared $cleared queue files"
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Unknown action: ' . $action,
                'available_actions' => ['status', 'process', 'clear']
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
