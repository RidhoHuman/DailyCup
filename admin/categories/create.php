<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Add Category';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, description, display_order, is_active) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $displayOrder, $isActive])) {
                clearAllCache();
                header('Location: ' . SITE_URL . '/admin/categories/?success=created');
                exit;
            } else {
                $error = 'Failed to create category';
            }
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-plus-circle"></i> Add New Category</h1>
            <a href="<?php echo SITE_URL; ?>/admin/categories/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="0">
                        <small class="text-muted">Higher numbers appear later in the list.</small>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Active / Visible</label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-coffee btn-lg px-5">
                            <i class="bi bi-save"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
