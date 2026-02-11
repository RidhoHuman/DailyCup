<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Products';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . SITE_URL . '/admin/products/');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';

// Get all products
$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p 
                   JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-cup-straw"></i> Products</h1>
            <a href="<?php echo SITE_URL; ?>/admin/products/create.php" class="btn btn-coffee">
                <i class="bi bi-plus-circle"></i> Add New Product
            </a>
        </div>
        
        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <?php if ($product['is_featured']): ?>
                                <span class="badge bg-warning text-dark ms-2">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo formatCurrency($product['base_price']); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/products/edit.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="?delete=<?php echo $product['id']; ?>" 
                                   class="btn btn-sm btn-delete"
                                   onclick="return confirm('Delete this product?')">
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
