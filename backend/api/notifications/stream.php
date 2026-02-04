<?php
// SSE (Server-Sent Events) stream for real-time notifications
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

// CORS handled by .htaccess globally

// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Function to send SSE message
function sendSSE($id, $event, $data) {
    if (!empty($id)) {
        echo "id: $id\n";
    }
    if (!empty($event)) {
        echo "event: $event\n";
    }
    echo "data: " . json_encode($data) . "\n\n";
    flush();
}

// Get user from token
$token = isset($_GET['token']) ? $_GET['token'] : null;

if (!$token) {
    sendSSE(null, 'error', ['message' => 'No token provided']);
    exit;
}

// Verify JWT token
$userData = JWT::verify($token);
if (!$userData || !isset($userData['user_id'])) {
    sendSSE(null, 'error', ['message' => 'Invalid or expired token']);
    exit;
}
$userId = $userData['user_id'];

// Use $pdo from database.php (already connected)
if (!isset($pdo)) {
    sendSSE(null, 'error', ['message' => 'Database connection not available']);
    exit;
}
$db = $pdo;

// Send initial connection success message
sendSSE(time(), 'connected', [
    'message' => 'Connected to notification stream',
    'user_id' => $userId,
    'timestamp' => time()
]);

// Track last notification ID to avoid duplicates
$lastNotificationId = 0;

// Get the last notification ID from query param (for reconnection)
if (isset($_GET['lastEventId'])) {
    $lastNotificationId = (int)$_GET['lastEventId'];
}

// Keep-alive counter
$keepAliveCounter = 0;
$lastKeepAlive = time();

// Main event loop
while (true) {
    // Check if connection is still alive
    if (connection_aborted()) {
        break;
    }

    // Send keep-alive ping every 15 seconds
    if (time() - $lastKeepAlive >= 15) {
        sendSSE(time(), 'ping', ['timestamp' => time()]);
        $lastKeepAlive = time();
    }

    // Check for new notifications
    try {
        // Query only columns that exist in user_notifications table
        $query = "SELECT 
                    id,
                    type,
                    title,
                    message,
                    is_read,
                    created_at
                FROM user_notifications 
                WHERE user_id = :user_id 
                AND id > :last_id
                ORDER BY id ASC
                LIMIT 10";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':last_id', $lastNotificationId);
        $stmt->execute();

        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                // Add default values for missing columns (temporary fix until DB migration)
                $notification['data'] = null;
                $notification['icon'] = 'bell';
                $notification['action_url'] = null;
                
                // Send notification event
                sendSSE(
                    $notification['id'],
                    'notification',
                    [
                        'type' => 'notification',
                        'notification' => $notification
                    ]
                );

                // Update last notification ID
                $lastNotificationId = max($lastNotificationId, $notification['id']);

                // Send specific event type if needed
                if ($notification['type'] === 'order') {
                    sendSSE(
                        $notification['id'],
                        'order_update',
                        [
                            'order_id' => $notification['data']['order_id'] ?? null,
                            'status' => $notification['data']['status'] ?? null,
                            'message' => $notification['message']
                        ]
                    );
                } elseif ($notification['type'] === 'payment') {
                    sendSSE(
                        $notification['id'],
                        'payment_update',
                        [
                            'order_id' => $notification['data']['order_id'] ?? null,
                            'amount' => $notification['data']['amount'] ?? null,
                            'message' => $notification['message']
                        ]
                    );
                } elseif ($notification['type'] === 'promo') {
                    sendSSE(
                        $notification['id'],
                        'promo',
                        [
                            'title' => $notification['title'],
                            'message' => $notification['message'],
                            'url' => $notification['action_url'],
                            'image' => $notification['icon']
                        ]
                    );
                }
            }
        }

        // Send unread count periodically
        if ($keepAliveCounter % 4 == 0) { // Every ~20 seconds (4 * 5s)
            $countQuery = "SELECT COUNT(*) as count 
                          FROM user_notifications 
                          WHERE user_id = :user_id AND is_read = 0";
            $countStmt = $db->prepare($countQuery);
            $countStmt->bindParam(':user_id', $userId);
            $countStmt->execute();
            $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

            sendSSE(time(), 'unread_count', [
                'type' => 'unread_count',
                'count' => (int)$unreadCount
            ]);
        }

    } catch (PDOException $e) {
        sendSSE(time(), 'error', [
            'message' => 'Database error',
            'error' => $e->getMessage()
        ]);
    }

    $keepAliveCounter++;

    // Sleep for 5 seconds before checking again
    sleep(5);
}

// Connection closed
sendSSE(time(), 'disconnected', ['message' => 'Stream closed']);
