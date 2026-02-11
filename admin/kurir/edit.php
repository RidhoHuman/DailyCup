<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Edit Kurir';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$kurirId = intval($_GET['id'] ?? 0);

if (!$kurirId) {
    header('Location: ' . SITE_URL . '/admin/kurir/index.php');
    exit;
}

// Get kurir details
$stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
$stmt->execute([$kurirId]);
$kurir = $stmt->fetch();

if (!$kurir) {
    header('Location: ' . SITE_URL . '/admin/kurir/index.php?error=not_found');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $vehicleType = sanitizeInput($_POST['vehicle_type']);
    $vehicleNumber = sanitizeInput($_POST['vehicle_number']);
    $status = sanitizeInput($_POST['status']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Update password if provided
    $passwordUpdate = '';
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passwordUpdate = ", password = ?";
        $params = [$name, $phone, $email, $vehicleType, $vehicleNumber, $status, $isActive, $password, $kurirId];
    } else {
        $params = [$name, $phone, $email, $vehicleType, $vehicleNumber, $status, $isActive, $kurirId];
    }
    
    try {
        $sql = "UPDATE kurir SET 
                name = ?, 
                phone = ?, 
                email = ?, 
                vehicle_type = ?, 
                vehicle_number = ?, 
                status = ?, 
                is_active = ?
                $passwordUpdate
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        header('Location: ' . SITE_URL . '/admin/kurir/index.php?success=updated');
        exit;
    } catch (Exception $e) {
        $error = "Gagal update kurir: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-pencil"></i> Edit Kurir</h1>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="admin-form">
            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" 
                                   value="<?php echo htmlspecialchars($kurir['name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">No. Telepon <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($kurir['phone']); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($kurir['email']); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="password" 
                                   placeholder="Kosongkan jika tidak ingin mengubah">
                            <small class="form-text text-muted">Minimal 6 karakter</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Jenis Kendaraan <span class="text-danger">*</span></label>
                            <select class="form-select" name="vehicle_type" required>
                                <option value="motor" <?php echo $kurir['vehicle_type'] === 'motor' ? 'selected' : ''; ?>>Motor</option>
                                <option value="mobil" <?php echo $kurir['vehicle_type'] === 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                                <option value="sepeda" <?php echo $kurir['vehicle_type'] === 'sepeda' ? 'selected' : ''; ?>>Sepeda</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nomor Kendaraan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="vehicle_number" 
                                   value="<?php echo htmlspecialchars($kurir['vehicle_number']); ?>" 
                                   placeholder="B 1234 XYZ" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="available" <?php echo $kurir['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="busy" <?php echo $kurir['status'] === 'busy' ? 'selected' : ''; ?>>Busy</option>
                                <option value="offline" <?php echo $kurir['status'] === 'offline' ? 'selected' : ''; ?>>Offline</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Status Aktif</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       id="isActive" <?php echo $kurir['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="isActive">
                                    Kurir Aktif (dapat menerima pesanan)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Info:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Rating saat ini: <strong><?php echo number_format($kurir['rating'], 1); ?></strong> / 5.0</li>
                        <li>Total pengantaran: <strong><?php echo number_format($kurir['total_deliveries']); ?></strong> pesanan</li>
                        <li>Terdaftar sejak: <strong><?php echo date('d M Y', strtotime($kurir['created_at'])); ?></strong></li>
                    </ul>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="bi bi-trash"></i> Hapus Kurir
                    </button>
                    <div>
                        <a href="index.php" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-coffee">
                            <i class="bi bi-check-circle"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="delete.php" style="display: none;">
    <input type="hidden" name="id" value="<?php echo $kurirId; ?>">
</form>

<script>
function confirmDelete() {
    if (confirm('Apakah Anda yakin ingin menghapus kurir ini? Data tidak dapat dikembalikan!')) {
        document.getElementById('deleteForm').submit();
    }
}

// Bootstrap form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
