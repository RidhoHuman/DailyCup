<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Ticket Detail - Customer Service';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$ticketId = $_GET['id'] ?? 0;

// Get ticket
$stmt = $db->prepare("SELECT st.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
                     FROM support_tickets st
                     JOIN users u ON st.user_id = u.id
                     WHERE st.id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = 'Ticket tidak ditemukan!';
    header('Location: ' . SITE_URL . '/admin/cs/tickets.php');
    exit;
}

// Get messages
$stmt = $db->prepare("SELECT tm.*, u.name as sender_name
                     FROM ticket_messages tm
                     JOIN users u ON tm.user_id = u.id
                     WHERE tm.ticket_id = ?
                     ORDER BY tm.created_at ASC");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'reply') {
        $message = trim($_POST['message']);
        $adminId = $_SESSION['user_id'];
        
        if (!empty($message)) {
            $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin) VALUES (?, ?, ?, 1)");
            $stmt->execute([$ticketId, $adminId, $message]);
            
            // Update ticket status to in_progress if it's open
            if ($ticket['status'] == 'open') {
                $stmt = $db->prepare("UPDATE support_tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$ticketId]);
            }
            
            // Create notification for customer
            $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'ticket', 'Balasan Ticket', ?)");
            $stmt->execute([$ticket['user_id'], "Admin membalas ticket #{$ticket['ticket_number']}."]);
            
            $_SESSION['success'] = 'Balasan berhasil dikirim!';
            header('Location: ' . SITE_URL . '/admin/cs/ticket_detail.php?id=' . $ticketId);
            exit;
        }
    } elseif ($action == 'update_status') {
        $newStatus = $_POST['status'];
        $stmt = $db->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $ticketId]);
        
        if ($newStatus == 'resolved') {
            $stmt = $db->prepare("UPDATE support_tickets SET resolved_at = NOW() WHERE id = ?");
            $stmt->execute([$ticketId]);
        }
        
        $_SESSION['success'] = 'Status ticket berhasil diupdate!';
        header('Location: ' . SITE_URL . '/admin/cs/ticket_detail.php?id=' . $ticketId);
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
                    <i class="bi bi-ticket-detailed"></i> Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
                </h1>
                <a href="<?php echo SITE_URL; ?>/admin/cs/tickets.php" class="btn btn-secondary">
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
                <!-- Ticket Info -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Category:</strong> <span class="badge bg-info"><?php echo ucfirst($ticket['category']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Priority:</strong> 
                                <?php
                                $priorityColors = ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger'];
                                ?>
                                <span class="badge bg-<?php echo $priorityColors[$ticket['priority']]; ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Created:</strong> <?php echo formatDate($ticket['created_at']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Updated:</strong> <?php echo formatDate($ticket['updated_at']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Percakapan</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php foreach ($messages as $msg): ?>
                        <div class="mb-3 p-3 rounded <?php echo $msg['is_admin'] ? 'bg-light' : 'bg-primary bg-opacity-10'; ?>">
                            <div class="d-flex justify-content-between mb-2">
                                <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                <small class="text-muted"><?php echo formatDate($msg['created_at']); ?></small>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($ticket['status'] != 'closed'): ?>
                    <div class="card-footer">
                        <form method="POST">
                            <input type="hidden" name="action" value="reply">
                            <div class="mb-3">
                                <label class="form-label">Balas Ticket:</label>
                                <textarea name="message" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Kirim Balasan
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Customer Info -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Customer</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nama:</strong><br><?php echo htmlspecialchars($ticket['customer_name']); ?></p>
                        <p><strong>Email:</strong><br><?php echo htmlspecialchars($ticket['customer_email']); ?></p>
                        <p><strong>Phone:</strong><br><?php echo htmlspecialchars($ticket['customer_phone'] ?? '-'); ?></p>
                    </div>
                </div>
                
                <!-- Status Update -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <div class="mb-3">
                                <label class="form-label">Status:</label>
                                <select name="status" class="form-select" required>
                                    <option value="open" <?php echo $ticket['status'] == 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $ticket['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $ticket['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
