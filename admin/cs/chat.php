<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Live Chat - Customer Service';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Get all chat users with their latest message
$stmt = $db->query("SELECT 
                        u.id as user_id,
                        u.name,
                        u.email,
                        COUNT(CASE WHEN cm.is_read = 0 AND cm.sender_type = 'customer' THEN 1 END) as unread_count,
                        MAX(cm.created_at) as last_message_time,
                        (SELECT message FROM chat_messages WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message
                    FROM users u
                    INNER JOIN chat_messages cm ON u.id = cm.user_id
                    WHERE u.role = 'customer'
                    GROUP BY u.id, u.name, u.email
                    ORDER BY last_message_time DESC");
$chatUsers = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title"><i class="bi bi-chat-dots"></i> Live Chat</h1>
                <a href="<?php echo SITE_URL; ?>/admin/cs/index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <?php if (empty($chatUsers)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Belum ada percakapan chat.
        </div>
        <?php else: ?>
        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Pesan Terakhir</th>
                            <th>Waktu</th>
                            <th>Unread</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chatUsers as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars(substr($user['last_message'], 0, 50)) . (strlen($user['last_message']) > 50 ? '...' : ''); ?>
                                </small>
                            </td>
                            <td><?php echo formatDate($user['last_message_time']); ?></td>
                            <td>
                                <?php if ($user['unread_count'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $user['unread_count']; ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/cs/chat_detail.php?user_id=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Lihat
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
