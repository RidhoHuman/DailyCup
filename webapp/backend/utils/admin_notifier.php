<?php
/**
 * Shared helper for notifying admins about system events/errors.
 * Works for both web requests (NotificationService) and background scripts (Emails).
 */
require_once __DIR__ . '/../api/notifications/NotificationService.php';
require_once __DIR__ . '/../api/email/EmailService.php';

// Helper: notify all admin users
function notifyAdmins($pdo, $orderId, $err, $attempts, $threshold = 3, $jobType = 'Geocoding') {
    if ($attempts !== (int)$threshold) {
        // only notify once when the threshold is reached
        return;
    }

    try {
        // Init services
        $notificationService = new NotificationService($pdo);
        EmailService::init();
        
        // Find admins
        $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE is_admin = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $actionUrl = "/admin/orders/{$orderId}";
        $message = "{$jobType} failed for order {$orderId}: {$err} (attempts: {$attempts})";
        
        foreach ($admins as $admin) {
            $adminId = (int)$admin['id'];
            $email = $admin['email'];
            
            // 1. System Notification
            $notificationService->create($adminId, 'system', $message, ['order_id' => $orderId, 'error' => $err], $actionUrl);
            
            // 2. Email Escalation
            $subject = "Action Required: {$jobType} Failure - Order #{$orderId}";
            $htmlBody = "
                <h2>{$jobType} Failure Alert</h2>
                <p><strong>Order ID:</strong> {$orderId}</p>
                <p><strong>Error:</strong> {$err}</p>
                <p><strong>Attempts:</strong> {$attempts}</p>
                <p>Please check the delivery address and correct it manually.</p>
                <p><a href='http://localhost:3000{$actionUrl}'>View Order in Admin Panel</a></p>
            ";
            
            // Use queue if possible, otherwise direct
            EmailService::send($email, $subject, $htmlBody);
        }
        
    } catch (Exception $e) {
        error_log("notifyAdmins error: " . $e->getMessage());
    }
}
