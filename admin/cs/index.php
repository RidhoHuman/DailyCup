<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Customer Service';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Get stats
$stmt = $db->query("SELECT COUNT(*) FROM chat_messages WHERE sender_type='customer' AND is_read=0");
$unreadChats = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM support_tickets WHERE status IN ('open', 'in_progress')");
$openTickets = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM contact_submissions WHERE status='new'");
$newContacts = $stmt->fetchColumn();

// Get recent tickets
$stmt = $db->query("SELECT st.*, u.name as customer_name 
                   FROM support_tickets st
                   JOIN users u ON st.user_id = u.id
                   ORDER BY st.created_at DESC
                   LIMIT 10");
$recentTickets = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-headset"></i> Customer Service</h1>
        </div>
        
        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $unreadChats; ?></div>
                            <div class="stat-label">Pesan Chat Belum Dibaca</div>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/admin/cs/chat.php" class="btn btn-sm btn-outline-primary mt-3 w-100">
                        <i class="bi bi-eye"></i> Lihat Chat
                    </a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $openTickets; ?></div>
                            <div class="stat-label">Ticket Aktif</div>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-ticket-detailed"></i>
                        </div>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/admin/cs/tickets.php" class="btn btn-sm btn-outline-warning mt-3 w-100">
                        <i class="bi bi-eye"></i> Lihat Tickets
                    </a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-value"><?php echo $newContacts; ?></div>
                            <div class="stat-label">Pesan Kontak Baru</div>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-envelope"></i>
                        </div>
                    </div>
                    <a href="<?php echo SITE_URL; ?>/admin/cs/contacts.php" class="btn btn-sm btn-outline-success mt-3 w-100">
                        <i class="bi bi-eye"></i> Lihat Kontak
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Tickets -->
        <div class="admin-table">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">Ticket Terbaru</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTickets as $ticket): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($ticket['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($ticket['subject'], 0, 40)); ?>...</td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($ticket['category']); ?></span></td>
                            <td>
                                <span class="badge bg-<?php echo $ticket['priority'] == 'high' ? 'danger' : ($ticket['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                    <?php echo strtoupper($ticket['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $ticket['status'] == 'closed' ? 'secondary' : ($ticket['status'] == 'resolved' ? 'success' : 'primary'); ?>">
                                    <?php 
                                    $statusLabels = ['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
                                    echo $statusLabels[$ticket['status']];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/cs/ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-view">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
