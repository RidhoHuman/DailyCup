<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Discounts';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM discounts WHERE id = ?");
    $stmt->execute([$deleteId]);
    header('Location: index.php?success=deleted');
    exit;
}

// Get discounts
$stmt = $db->query("SELECT * FROM discounts ORDER BY created_at DESC");
$discounts = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-percent"></i> Discount Codes</h1>
            <a href="create.php" class="btn btn-coffee">
                <i class="bi bi-plus-circle"></i> Add Discount
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            if ($_GET['success'] == 'created') echo 'Discount berhasil ditambahkan!';
            elseif ($_GET['success'] == 'updated') echo 'Discount berhasil diperbarui!';
            elseif ($_GET['success'] == 'deleted') echo 'Discount berhasil dihapus!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td><code class="fw-bold"><?php echo htmlspecialchars($discount['code']); ?></code></td>
                            <td><?php echo htmlspecialchars($discount['name']); ?></td>
                            <td><?php echo ucfirst($discount['discount_type']); ?></td>
                            <td>
                                <?php echo $discount['discount_type'] == 'percentage' ? $discount['discount_value'] . '%' : formatCurrency($discount['discount_value']); ?>
                            </td>
                            <td><?php echo $discount['usage_count']; ?> / <?php echo $discount['usage_limit'] ?: '∞'; ?></td>
                            <td>
                                <?php if ($discount['is_active'] && strtotime($discount['end_date']) > time()): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive/Expired</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $discount['id']; ?>" class="btn btn-sm btn-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?php echo $discount['id']; ?>" class="btn btn-sm btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus discount ini?')" title="Delete">
                                    <i class="bi bi-trash"></i>
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
