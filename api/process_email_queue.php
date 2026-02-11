<?php
/**
 * Email Queue Processor
 * Processes pending emails in the queue
 * Should be run as a cron job every 5-10 minutes
 */

require_once '../includes/functions.php';

// Only allow CLI or specific access
if (!defined('ALLOW_CRON') && php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Access denied');
}

define('ALLOW_CRON', true);

try {
    $db = getDB();

    // Get pending emails, ordered by priority and creation time
    $stmt = $db->prepare("
        SELECT * FROM email_queue
        WHERE status = 'pending'
        AND attempts < max_attempts
        ORDER BY
            CASE priority
                WHEN 'high' THEN 1
                WHEN 'normal' THEN 2
                WHEN 'low' THEN 3
            END,
            created_at ASC
        LIMIT 10
    ");

    $stmt->execute();
    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed = 0;
    $sent = 0;
    $failed = 0;

    foreach ($emails as $email) {
        // Mark as sending
        $updateStmt = $db->prepare("
            UPDATE email_queue
            SET status = 'sending', attempts = attempts + 1
            WHERE id = ?
        ");
        $updateStmt->execute([$email['id']]);

        // Send email
        $result = sendEmail(
            $email['recipient_email'],
            $email['subject'],
            $email['body'],
            $email['recipient_name']
        );

        if ($result['success']) {
            // Mark as sent
            $updateStmt = $db->prepare("
                UPDATE email_queue
                SET status = 'sent', sent_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$email['id']]);
            $sent++;
        } else {
            // Mark as failed
            $updateStmt = $db->prepare("
                UPDATE email_queue
                SET status = 'failed', error_message = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$result['error'], $email['id']]);
            $failed++;
        }

        $processed++;
    }

    // Log the results
    if ($processed > 0) {
        logActivity('system', 'email_queue_processed', [
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed
        ]);
    }

    // Output results (for cron job monitoring)
    echo "Email queue processed: $processed emails ($sent sent, $failed failed)\n";

} catch (Exception $e) {
    error_log("Email queue processor error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>