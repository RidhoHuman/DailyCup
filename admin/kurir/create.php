<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Tambah Kurir';
$isAdminPage = true;
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $phone = sanitizeInput($_POST['phone']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $vehicleType = $_POST['vehicle_type'];
    $vehicleNumber = sanitizeInput($_POST['vehicle_number']);
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Nama kurir harus diisi";
    if (empty($phone)) $errors[] = "Nomor telepon harus diisi";
    if (empty($password)) $errors[] = "Password harus diisi";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    
    // Check if phone already exists
    if (!empty($phone)) {
        $stmt = $db->prepare("SELECT id FROM kurir WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = "Nomor telepon sudah terdaftar";
        }
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO kurir (name, phone, email, password, vehicle_type, vehicle_number) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $phone, $email, $hashedPassword, $vehicleType, $vehicleNumber])) {
            $_SESSION['success_message'] = "Kurir berhasil ditambahkan!";
            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Gagal menambahkan kurir";
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-plus-circle"></i> Tambah Kurir Baru</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong>
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
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" required 
                                   placeholder="08xxxxxxxxxx"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <small class="text-muted">Opsional, untuk login kurir dashboard</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jenis Kendaraan <span class="text-danger">*</span></label>
                            <select name="vehicle_type" class="form-select" required>
                                <option value="motor" <?php echo ($_POST['vehicle_type'] ?? '') === 'motor' ? 'selected' : ''; ?>>Motor</option>
                                <option value="mobil" <?php echo ($_POST['vehicle_type'] ?? '') === 'mobil' ? 'selected' : ''; ?>>Mobil</option>
                                <option value="sepeda" <?php echo ($_POST['vehicle_type'] ?? '') === 'sepeda' ? 'selected' : ''; ?>>Sepeda</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Plat Kendaraan</label>
                            <input type="text" name="vehicle_number" class="form-control" 
                                   placeholder="B 1234 XYZ"
                                   value="<?php echo htmlspecialchars($_POST['vehicle_number'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Note:</strong> Password ini akan digunakan kurir untuk login ke dashboard mobile. 
                        Pastikan password mudah diingat tapi tetap aman.
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-coffee">
                            <i class="bi bi-check"></i> Simpan Kurir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
