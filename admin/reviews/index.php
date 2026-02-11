<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Reviews';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Handle Approval/Delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'approve') {
        $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ?")->execute([$id]);
    } elseif ($_GET['action'] == 'delete') {
        $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
    }
    header('Location: ' . SITE_URL . '/admin/reviews/');
    exit;
}

// Get reviews
$stmt = $db->query("SELECT r.*, u.name as customer_name, p.name as product_name 
                   FROM reviews r 
                   JOIN users u ON r.user_id = u.id 
                   JOIN products p ON r.product_id = p.id 
                   ORDER BY r.created_at DESC");
$reviews = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-star"></i> Product Reviews</h1>
        </div>

        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($review['customer_name']); ?></td>
                            <td>
                                <div class="text-warning">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($review['review_text']); ?></small>
                            </td>
                            <td>
                                <?php if ($review['is_approved']): ?>
                                <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($review['created_at']); ?></td>
                            <td>
                                <?php if (!$review['is_approved']): ?>
                                <a href="?action=approve&id=<?php echo $review['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-check"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $review['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Delete this review?')">
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
