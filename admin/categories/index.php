<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Categories';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        header('Location: ' . SITE_URL . '/admin/categories/?success=deleted');
        exit;
    }
}

// Get all categories
$stmt = $db->query("SELECT * FROM categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-tags"></i> Categories</h1>
            <a href="<?php echo SITE_URL; ?>/admin/categories/create.php" class="btn btn-coffee">
                <i class="bi bi-plus-circle"></i> Add Category
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                if ($_GET['success'] == 'created') echo "Category created successfully!";
                if ($_GET['success'] == 'updated') echo "Category updated successfully!";
                if ($_GET['success'] == 'deleted') echo "Category deleted successfully!";
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($category['description']); ?></td>
                            <td><?php echo $category['display_order']; ?></td>
                            <td>
                                <?php if ($category['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/categories/edit.php?id=<?php echo $category['id']; ?>" 
                                   class="btn btn-sm btn-edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?php echo $category['id']; ?>" 
                                   class="btn btn-sm btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this category? This may affect products in this category.')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($categories) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No categories found
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
