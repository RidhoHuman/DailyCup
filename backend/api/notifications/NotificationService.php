<?php
/**
 * NotificationService - Core notification management
 * Handles creating, fetching, and managing notifications
 */

class NotificationService
{
    private $pdo;
    
    // Notification types with icons and default messages
    private const TYPES = [
        'order_created' => [
            'icon' => 'cart-check',
            'title' => 'Pesanan Dibuat'
        ],
        'payment_received' => [
            'icon' => 'credit-card-2-front',
            'title' => 'Pembayaran Diterima'
        ],
        'order_processing' => [
            'icon' => 'box-seam',
            'title' => 'Pesanan Diproses'
        ],
        'order_shipped' => [
            'icon' => 'truck',
            'title' => 'Pesanan Dikirim'
        ],
        'order_delivered' => [
            'icon' => 'house-check',
            'title' => 'Pesanan Sampai'
        ],
        'order_cancelled' => [
            'icon' => 'x-circle',
            'title' => 'Pesanan Dibatalkan'
        ],
        'promo' => [
            'icon' => 'tag',
            'title' => 'Promo Spesial'
        ],
        'system' => [
            'icon' => 'bell',
            'title' => 'Pemberitahuan'
        ],
        'review_reminder' => [
            'icon' => 'star',
            'title' => 'Beri Ulasan'
        ]
    ];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new notification
     */
    public function create(int $userId, string $type, string $message, array $data = [], ?string $actionUrl = null): ?int
    {
        try {
            $typeInfo = self::TYPES[$type] ?? self::TYPES['system'];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, data, icon, action_url, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $type,
                $typeInfo['title'],
                $message,
                json_encode($data),
                $typeInfo['icon'],
                $actionUrl
            ]);
            
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("NotificationService::create error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create order status notification
     */
    public function createOrderNotification(int $userId, string $orderId, string $status, ?string $customMessage = null): ?int
    {
        $statusMap = [
            'pending' => 'order_created',
            'paid' => 'payment_received',
            'processing' => 'order_processing',
            'shipped' => 'order_shipped',
            'delivered' => 'order_delivered',
            'cancelled' => 'order_cancelled',
            'failed' => 'order_cancelled'
        ];

        $type = $statusMap[$status] ?? 'system';
        
        $messageMap = [
            'pending' => "Pesanan #{$orderId} berhasil dibuat. Silakan lakukan pembayaran.",
            'paid' => "Pembayaran untuk pesanan #{$orderId} sudah diterima!",
            'processing' => "Pesanan #{$orderId} sedang diproses.",
            'shipped' => "Pesanan #{$orderId} sedang dalam pengiriman!",
            'delivered' => "Pesanan #{$orderId} telah sampai. Terima kasih!",
            'cancelled' => "Pesanan #{$orderId} telah dibatalkan.",
            'failed' => "Pembayaran untuk pesanan #{$orderId} gagal."
        ];

        $message = $customMessage ?? ($messageMap[$status] ?? "Update untuk pesanan #{$orderId}");
        $actionUrl = "/orders/{$orderId}";
        $data = ['order_id' => $orderId, 'status' => $status];

        return $this->create($userId, $type, $message, $data, $actionUrl);
    }

    /**
     * Get notifications for a user
     */
    public function getByUser(int $userId, int $limit = 20, int $offset = 0, bool $unreadOnly = false): array
    {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ?";
            $params = [$userId];
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse JSON data
            foreach ($notifications as &$notif) {
                $notif['data'] = json_decode($notif['data'], true) ?? [];
            }
            
            return $notifications;
        } catch (PDOException $e) {
            error_log("NotificationService::getByUser error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("NotificationService::getUnreadCount error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1, read_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("NotificationService::markAsRead error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("NotificationService::markAllAsRead error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOld(int $daysOld = 30): int
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysOld]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("NotificationService::deleteOld error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get notification by ID
     */
    public function getById(int $notificationId, int $userId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            $notif = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($notif) {
                $notif['data'] = json_decode($notif['data'], true) ?? [];
            }
            
            return $notif ?: null;
        } catch (PDOException $e) {
            error_log("NotificationService::getById error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a notification
     */
    public function delete(int $notificationId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("NotificationService::delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification types with info
     */
    public static function getTypes(): array
    {
        return self::TYPES;
    }
}
