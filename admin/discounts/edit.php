<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Edit Discount';
$isAdminPage = true;
requireAdmin();

$discountId = intval($_GET['id'] ?? 0);
if (!$discountId) {
    header('Location: ' . SITE_URL . '/admin/discounts/');
    exit;
}

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
    $name = sanitizeInput($_POST['name'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $discountType = sanitizeInput($_POST['discount_type'] ?? 'percentage');
    $discountValue = floatval($_POST['discount_value'] ?? 0);
    $minPurchase = floatval($_POST['min_purchase'] ?? 0);
    $maxDiscount = $_POST['max_discount'] ? floatval($_POST['max_discount']) : null;
    $usageLimit = $_POST['usage_limit'] ? intval($_POST['usage_limit']) : null;
    $startDate = sanitizeInput($_POST['start_date'] ?? '');
    $endDate = sanitizeInput($_POST['end_date'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    
    // Validation
    if (empty($code)) {
        $errors[] = "Kode diskon wajib diisi";
    }
    if (empty($name)) {
        $errors[] = "Nama diskon wajib diisi";
    }
    if ($discountValue <= 0) {
        $errors[] = "Nilai diskon harus lebih dari 0";
    }
    if ($discountType == 'percentage' && $discountValue > 100) {
        $errors[] = "Persentase diskon tidak boleh lebih dari 100%";
    }
    if (empty($startDate) || empty($endDate)) {
        $errors[] = "Tanggal mulai dan berakhir wajib diisi";
    }
    
    // Check if code already exists (except current discount)
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM discounts WHERE code = ? AND id != ?");
        $stmt->execute([$code, $discountId]);
        if ($stmt->fetch()) {
            $errors[] = "Kode diskon sudah digunakan";
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE discounts SET 
                code = ?, 
                name = ?, 
                description = ?, 
                discount_type = ?, 
                discount_value = ?, 
                min_purchase = ?, 
                max_discount = ?, 
                usage_limit = ?, 
                start_date = ?, 
                end_date = ?, 
                is_active = ?,
                updated_at = NOW()
                WHERE id = ?");
            
            $stmt->execute([
                $code,
                $name,
                $description,
                $discountType,
                $discountValue,
                $minPurchase,
                $maxDiscount,
                $usageLimit,
                $startDate,
                $endDate,
                $isActive,
                $discountId
            ]);
            
            header('Location: index.php?success=updated');
            exit;
        } catch (Exception $e) {
            $errors[] = "Gagal menyimpan: " . $e->getMessage();
        }
    }
}

// Get discount data
$stmt = $db->prepare("SELECT * FROM discounts WHERE id = ?");
$stmt->execute([$discountId]);
$discount = $stmt->fetch();

if (!$discount) {
    header('Location: ' . SITE_URL . '/admin/discounts/');
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-pencil"></i> Edit Discount</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Discount Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" 
                                       value="<?php echo htmlspecialchars($discount['code']); ?>" 
                                       required style="text-transform: uppercase;">
                                <small class="text-muted">Gunakan huruf kapital, contoh: DISKON50</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Discount Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($discount['name']); ?>" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($discount['description']); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-select" required>
                                    <option value="percentage" <?php echo $discount['discount_type'] == 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="fixed" <?php echo $discount['discount_type'] == 'fixed' ? 'selected' : ''; ?>>Fixed Amount (Rp)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                                <input type="number" name="discount_value" class="form-control" 
                                       value="<?php echo $discount['discount_value']; ?>" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Min Purchase (Rp)</label>
                                <input type="number" name="min_purchase" class="form-control" 
                                       value="<?php echo $discount['min_purchase']; ?>" 
                                       step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Max Discount (Rp)</label>
                                <input type="number" name="max_discount" class="form-control" 
                                       value="<?php echo $discount['max_discount']; ?>" 
                                       step="0.01" min="0">
                                <small class="text-muted">Kosongkan jika tidak ada batasan</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Usage Limit</label>
                                <input type="number" name="usage_limit" class="form-control" 
                                       value="<?php echo $discount['usage_limit']; ?>" 
                                       min="0">
                                <small class="text-muted">Kosongkan untuk unlimited. Current usage: <?php echo $discount['usage_count']; ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date" class="form-control" 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($discount['start_date'])); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_date" class="form-control" 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($discount['end_date'])); ?>" 
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" 
                                   id="is_active" <?php echo $discount['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-coffee">
                            <i class="bi bi-check-circle"></i> Update Discount
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Discount Info -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Discount Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Created:</strong> <?php echo date('d M Y H:i', strtotime($discount['created_at'])); ?></p>
                        <p class="mb-2"><strong>Last Updated:</strong> <?php echo date('d M Y H:i', strtotime($discount['updated_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Times Used:</strong> <?php echo $discount['usage_count']; ?></p>
                        <p class="mb-2"><strong>Usage Limit:</strong> <?php echo $discount['usage_limit'] ?: 'Unlimited'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
