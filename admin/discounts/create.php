<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Add Discount Code';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
    $name = sanitizeInput($_POST['name'] ?? '');
    $type = $_POST['discount_type'] ?? 'percentage';
    $value = floatval($_POST['discount_value'] ?? 0);
    $limit = intval($_POST['usage_limit'] ?? 0);
    $startDate = $_POST['start_date'] ?? date('Y-m-d');
    $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 month'));

    if (empty($code) || $value <= 0) {
        $error = 'Please fill all required fields';
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO discounts (code, name, discount_type, discount_value, usage_limit, start_date, end_date, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$code, $name, $type, $value, $limit, $startDate, $endDate]);
            header('Location: index.php?success=created');
            exit;
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-plus-circle"></i> Add Discount Code</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Discount Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. COFFEE20" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Promo Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. New Year Promo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Discount Type</label>
                            <select name="discount_type" class="form-select">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (Rp)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Discount Value</label>
                            <input type="number" step="0.01" name="discount_value" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" value="0">
                            <small class="text-muted">0 for unlimited</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-coffee px-4">
                            <i class="bi bi-save"></i> Save Discount
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
