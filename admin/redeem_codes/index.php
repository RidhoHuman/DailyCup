<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Redeem Codes';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Get redeem codes
$stmt = $db->query("SELECT * FROM redeem_codes ORDER BY created_at DESC");
$codes = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-ticket-perforated"></i> Redeem Codes</h1>
            <a href="create.php" class="btn btn-coffee">
                <i class="bi bi-plus-circle"></i> Generate Codes
            </a>
        </div>

        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Points</th>
                            <th>Status</th>
                            <th>Used By</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($codes as $code): ?>
                        <tr>
                            <td><code class="fw-bold"><?php echo htmlspecialchars($code['code']); ?></code></td>
                            <td><?php echo $code['points']; ?> Pts</td>
                            <td>
                                <?php if ($code['is_used']): ?>
                                <span class="badge bg-secondary">Used</span>
                                <?php else: ?>
                                <span class="badge bg-success">Available</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $code['used_by'] ?: '-'; ?></td>
                            <td><?php echo formatDate($code['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($codes) == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No redeem codes found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
