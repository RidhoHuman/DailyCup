<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Contact Submissions - Customer Service';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Filter
$statusFilter = $_GET['status'] ?? 'all';

// Get contacts
$query = "SELECT * FROM contact_submissions";

if ($statusFilter != 'all') {
    $query .= " WHERE status = :status";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if ($statusFilter != 'all') {
    $stmt->execute(['status' => $statusFilter]);
} else {
    $stmt->execute();
}
$contacts = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title"><i class="bi bi-envelope"></i> Contact Submissions</h1>
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
                <a href="?status=new" class="btn btn-sm <?php echo $statusFilter == 'new' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Baru
                </a>
                <a href="?status=read" class="btn btn-sm <?php echo $statusFilter == 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Dibaca
                </a>
                <a href="?status=replied" class="btn btn-sm <?php echo $statusFilter == 'replied' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    Dibalas
                </a>
            </div>
        </div>
        
        <?php if (empty($contacts)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Tidak ada pesan kontak ditemukan.
        </div>
        <?php else: ?>
        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                        <tr class="<?php echo $contact['status'] == 'new' ? 'table-warning' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($contact['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars($contact['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                            <td>
                                <?php
                                $statusColors = ['new' => 'warning', 'read' => 'info', 'replied' => 'success'];
                                $statusLabels = ['new' => 'Baru', 'read' => 'Dibaca', 'replied' => 'Dibalas'];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$contact['status']]; ?>">
                                    <?php echo $statusLabels[$contact['status']]; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($contact['created_at']); ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/cs/contact_detail.php?id=<?php echo $contact['id']; ?>" 
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
