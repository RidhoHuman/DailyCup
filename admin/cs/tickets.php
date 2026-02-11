<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Support Tickets - Customer Service';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Filter
$statusFilter = $_GET['status'] ?? 'all';

// Get tickets
$query = "SELECT st.*, u.name as customer_name, u.email as customer_email
          FROM support_tickets st
          JOIN users u ON st.user_id = u.id";

if ($statusFilter != 'all') {
    $query .= " WHERE st.status = :status";
}

$query .= " ORDER BY st.created_at DESC";

$stmt = $db->prepare($query);
if ($statusFilter != 'all') {
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt->execute();
}
$tickets = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title"><i class="bi bi-ticket-detailed"></i> Support Tickets</h1>
                <a href="<?php echo SITE_URL; ?>/admin/cs/index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="mb-3">
            <div class="btn-group" role="group">
                <a href="?status=all" class="btn btn-sm <?php echo $statusFilter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Semua
                </a>
                <a href="?status=open" class="btn btn-sm <?php echo $statusFilter == 'open' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Open
                </a>
                <a href="?status=in_progress" class="btn btn-sm <?php echo $statusFilter == 'in_progress' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    In Progress
                </a>
                <a href="?status=resolved" class="btn btn-sm <?php echo $statusFilter == 'resolved' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Resolved
                </a>
                <a href="?status=closed" class="btn btn-sm <?php echo $statusFilter == 'closed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Closed
                </a>
            </div>
        </div>
        
        <?php if (empty($tickets)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada ticket ditemukan.
        </div>
        <?php else: ?>
        <div class="admin-table">
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
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($ticket['customer_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($ticket['customer_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($ticket['category']); ?></span></td>
                            <td>
                                <?php
                                $priorityColors = ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger'];
                                ?>
                                <span class="badge bg-<?php echo $priorityColors[$ticket['priority']]; ?>">
                                    <?php echo ucfirst($ticket['priority']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $statusColors = ['open' => 'primary', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                                $statusLabels = ['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$ticket['status']]; ?>">
                                    <?php echo $statusLabels[$ticket['status']]; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($ticket['created_at']); ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/cs/ticket_detail.php?id=<?php echo $ticket['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Detail
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
