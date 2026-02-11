<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// Check if kurir is logged in
if (!isset($_SESSION['kurir_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db = getDB();
$kurirId = $_SESSION['kurir_id'];
$action = $_GET['action'] ?? 'get';

try {
    switch ($action) {
        case 'get':
            // Get all notifications for this kurir
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] == 'true';
            
            $sql = "SELECT kn.*, o.order_number, o.status as order_status
                    FROM kurir_notifications kn
                    LEFT JOIN orders o ON kn.order_id = o.id
                    WHERE kn.kurir_id = ?";
            
            if ($unreadOnly) {
                $sql .= " AND kn.is_read = 0";
            }
            
            $sql .= " ORDER BY kn.created_at DESC LIMIT ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$kurirId, $limit]);
            $notifications = $stmt->fetchAll();
            
            // Get unread count
            $stmt = $db->prepare("SELECT COUNT(*) FROM kurir_notifications 
                                 WHERE kurir_id = ? AND is_read = 0");
            $stmt->execute([$kurirId]);
            $unreadCount = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            break;
            
        case 'mark_read':
            // Mark notification as read
            $notificationId = $_POST['notification_id'] ?? null;
            
            if (!$notificationId) {
                echo json_encode(['success' => false, 'message' => 'Notification ID required']);
                exit;
            }
            
            $stmt = $db->prepare("UPDATE kurir_notifications 
                                 SET is_read = 1 
                                 WHERE id = ? AND kurir_id = ?");
            $stmt->execute([$notificationId, $kurirId]);
            
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $stmt = $db->prepare("UPDATE kurir_notifications 
                                 SET is_read = 1 
                                 WHERE kurir_id = ? AND is_read = 0");
            $stmt->execute([$kurirId]);
            $affected = $stmt->rowCount();
            
            echo json_encode([
                'success' => true, 
                'message' => "$affected notifications marked as read"
            ]);
            break;
            
        case 'delete':
            // Delete notification
            $notificationId = $_POST['notification_id'] ?? null;
            
            if (!$notificationId) {
                echo json_encode(['success' => false, 'message' => 'Notification ID required']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM kurir_notifications 
                                 WHERE id = ? AND kurir_id = ?");
            $stmt->execute([$notificationId, $kurirId]);
            
            echo json_encode(['success' => true, 'message' => 'Notification deleted']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
