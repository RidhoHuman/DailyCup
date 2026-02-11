<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Chat Detail - Customer Service';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$userId = $_GET['user_id'] ?? 0;

// Get user info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User tidak ditemukan!';
    header('Location: ' . SITE_URL . '/admin/cs/chat.php');
    exit;
}

// Get all messages
$stmt = $db->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
$stmt->execute([$userId]);
$messages = $stmt->fetchAll();

// Mark all as read
$stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE user_id = ? AND sender_type = 'customer'");
$stmt->execute([$userId]);

// Handle reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $adminId = $_SESSION['user_id'];
    
    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO chat_messages (user_id, admin_id, message, sender_type) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$userId, $adminId, $message]);
        
        // Create notification for customer
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'chat', 'Balasan Chat', ?)");
        $stmt->execute([$userId, 'Admin telah membalas chat Anda.']);
        
        $_SESSION['success'] = 'Pesan berhasil dikirim!';
        header('Location: ' . SITE_URL . '/admin/cs/chat_detail.php?user_id=' . $userId);
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="bi bi-chat-dots"></i> Chat dengan <?php echo htmlspecialchars($user['name']); ?>
                </h1>
                <a href="<?php echo SITE_URL; ?>/admin/cs/chat.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Chat Messages -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Percakapan</h5>
                    </div>
                    <div class="card-body" style="height: 500px; overflow-y: auto;" id="chatMessages">
                        <?php if (empty($messages)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-chat-dots" style="font-size: 48px;"></i>
                            <p class="mt-3">Belum ada percakapan</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <div class="mb-3 p-3 rounded <?php echo $msg['sender_type'] == 'admin' ? 'bg-light ms-5' : 'bg-primary bg-opacity-10 me-5'; ?>">
                            <div class="d-flex justify-content-between mb-2">
                                <strong><?php echo $msg['sender_type'] == 'customer' ? htmlspecialchars($user['name']) : 'Admin'; ?></strong>
                                <small class="text-muted"><?php echo formatDate($msg['created_at']); ?></small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <form method="POST" id="chatForm">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Ketik pesan..." required autofocus>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Customer Info -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Customer</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nama:</strong><br><?php echo htmlspecialchars($user['name']); ?></p>
                        <p><strong>Email:</strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </a>
                        </p>
                        <p><strong>Phone:</strong><br><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                        <p><strong>Bergabung:</strong><br><?php echo formatDate($user['created_at']); ?></p>
                        
                        <hr>
                        
                        <a href="<?php echo SITE_URL; ?>/admin/users/detail.php?id=<?php echo $user['id']; ?>" 
                           class="btn btn-info w-100 mb-2">
                            <i class="bi bi-person"></i> Lihat Profile
                        </a>
                        
                        <?php if ($user['phone']): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user['phone']); ?>" 
                           target="_blank" class="btn btn-success w-100">
                            <i class="bi bi-whatsapp"></i> Chat via WhatsApp
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto scroll to bottom
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Auto-scroll after form submit
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function() {
            setTimeout(function() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 100);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
