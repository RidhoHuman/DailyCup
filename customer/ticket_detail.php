<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Ticket Detail';
requireLogin();

if ($_SESSION['role'] !== 'customer') {
    header('Location: ' . SITE_URL);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$ticketId = intval($_GET['id'] ?? 0);

// Get ticket
$stmt = $db->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticketId, $userId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: ' . SITE_URL . '/customer/tickets.php');
    exit;
}

// Handle reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $ticket['status'] != 'closed') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 0)");
        $stmt->execute([$ticketId, $userId, $message]);
        
        // Update ticket status if resolved
        if ($ticket['status'] == 'resolved') {
            $stmt = $db->prepare("UPDATE support_tickets SET status = 'open' WHERE id = ?");
            $stmt->execute([$ticketId]);
        }
        
        $_SESSION['success'] = 'Pesan terkirim!';
        header('Location: ' . SITE_URL . '/customer/ticket_detail.php?id=' . $ticketId);
        exit;
    }
}

// Get messages
$stmt = $db->prepare("SELECT tm.*, u.name as sender_name 
                     FROM ticket_messages tm
                     JOIN users u ON tm.user_id = u.id
                     WHERE tm.ticket_id = ?
                     ORDER BY tm.created_at ASC");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.ticket-message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.ticket-message.customer {
    background: #f8f9fa;
    margin-left: 50px;
}

.ticket-message.admin {
    background: #e8f4f8;
    margin-right: 50px;
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.sender-name {
    font-weight: 600;
}

.admin-badge {
    background: #6F4E37;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 5px;
}
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="mb-4">
                <a href="<?php echo SITE_URL; ?>/customer/tickets.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Kembali ke Tickets
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <!-- Ticket Header -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4>#<?php echo htmlspecialchars($ticket['ticket_number']); ?></h4>
                            <h5 class="mb-2"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                            <span class="badge bg-secondary"><?php echo ucfirst($ticket['category']); ?></span>
                            <span class="badge bg-<?php echo $ticket['priority'] == 'high' ? 'danger' : ($ticket['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                <?php echo strtoupper($ticket['priority']); ?>
                            </span>
                        </div>
                        <span class="badge bg-<?php echo $ticket['status'] == 'closed' ? 'danger' : ($ticket['status'] == 'resolved' ? 'success' : 'primary'); ?> p-2">
                            <?php 
                            $statusLabels = ['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
                            echo $statusLabels[$ticket['status']];
                            ?>
                        </span>
                    </div>
                    <div class="text-muted mt-3 small">
                        <i class="bi bi-calendar"></i> <?php echo formatDate($ticket['created_at']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Messages -->
            <div class="mb-4">
                <h5 class="mb-3">Percakapan</h5>
                <?php foreach ($messages as $msg): ?>
                <div class="ticket-message <?php echo $msg['is_admin'] ? 'admin' : 'customer'; ?>">
                    <div class="message-header">
                        <div>
                            <span class="sender-name"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                            <?php if ($msg['is_admin']): ?>
                            <span class="admin-badge">Admin</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-muted small"><?php echo formatDate($msg['created_at']); ?></span>
                    </div>
                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Reply Form -->
            <?php if ($ticket['status'] != 'closed'): ?>
            <div class="card" id="reply">
                <div class="card-body">
                    <h5 class="mb-3">Reply</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <textarea name="message" class="form-control" rows="4" placeholder="Tulis pesan Anda..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-coffee">
                            <i class="bi bi-send"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-info-circle"></i> Ticket ini sudah ditutup. Silakan buat ticket baru jika masih butuh bantuan.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
