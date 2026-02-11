<?php
/**
 * Notifications API Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Enable error logging
error_log("Notifications API called - Method: " . $_SERVER['REQUEST_METHOD']);

// Authenticate (supports both Session and JWT)
$userId = apiAuthenticate();
error_log("Notifications API: User ID = " . $userId);

try {
    $db = getDB();
} catch (Exception $e) {
    error_log("Notifications API: Database connection failed - " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Gagal terhubung ke database']);
    exit;
}

$action = $_GET['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    error_log("Notifications API: Raw input - " . $rawInput);
    
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Notifications API: JSON decode error - " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    $action = $data['action'] ?? $action;
}

error_log("Notifications API: Action = " . $action);

switch ($action) {
    case 'get':
        // Get all notifications
        try {
            $stmt = $db->prepare("SELECT * FROM notifications 
                                 WHERE user_id = ? 
                                 ORDER BY created_at DESC 
                                 LIMIT 50");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll();
            
            $unreadCount = getUnreadNotificationCount($userId);
            
            error_log("Notifications API: Retrieved " . count($notifications) . " notifications");
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (Exception $e) {
            error_log("Notifications API: Error getting notifications - " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengambil notifikasi',
                'notifications' => [],
                'unread_count' => 0
            ]);
        }
        break;
        
    
    case 'check_new':
        // Check for new notifications
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications 
                                 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            $unreadCount = $stmt->fetchColumn();
            
            $hasNew = $unreadCount > 0;
            
            // Get latest notification if exists
            $latestNotif = null;
            if ($hasNew) {
                $stmt = $db->prepare("SELECT * FROM notifications 
                                     WHERE user_id = ? AND is_read = 0 
                                     ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$userId]);
                $latestNotif = $stmt->fetch();
            }
            
            error_log("Notifications API: Check new - has_new=$hasNew, unread=$unreadCount");
            
            echo json_encode([
                'success' => true,
                'has_new' => $hasNew,
                'unread_count' => $unreadCount,
                'latest_notification' => $latestNotif
            ]);
        } catch (Exception $e) {
            error_log("Notifications API: Error checking new - " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Gagal memeriksa notifikasi baru',
                'has_new' => false,
                'unread_count' => 0
            ]);
        }
        break;
        
    case 'mark_read':
        // Mark single notification as read
        $notificationId = $data['notification_id'] ?? null;
        
        if (!$notificationId) {
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 
                             WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'mark_all_read':
        // Mark all notifications as read
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 
                             WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'delete':
        // Delete notification
        $notificationId = $data['notification_id'] ?? null;
        
        if (!$notificationId) {
            echo json_encode(['success' => false, 'message' => 'ID notifikasi tidak valid']);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM notifications 
                             WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode(['success' => true]);
        break;
        
    case 'create_test':
        // Create test notification (for debugging)
        $title = $data['title'] ?? 'Test Notification';
        $message = $data['message'] ?? 'This is a test notification';
        
        try {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) 
                                 VALUES (?, ?, ?, 'info')");
            $stmt->execute([$userId, $title, $message]);
            
            error_log("Notifications API: Test notification created for user $userId");
            
            echo json_encode([
                'success' => true,
                'message' => 'Test notification created',
                'notification_id' => $db->lastInsertId()
            ]);
        } catch (Exception $e) {
            error_log("Notifications API: Error creating test notification - " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Gagal membuat notifikasi: ' . $e->getMessage()
            ]);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
