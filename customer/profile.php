<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Profil Saya';
requireLogin();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$currentUser = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    if (empty($name)) {
        $error = 'Nama harus diisi';
    } else {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        
        if ($stmt->execute([$name, $phone, $address, $_SESSION['user_id']])) {
            $success = 'Profil berhasil diupdate';
            $_SESSION['name'] = $name;
            $currentUser = getCurrentUser();
        } else {
            $error = 'Gagal mengupdate profil';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword)) {
        $error = 'Password harus diisi';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password baru tidak cocok';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        if (password_verify($currentPassword, $currentUser['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                $success = 'Password berhasil diubah';
            } else {
                $error = 'Gagal mengubah password';
            }
        } else {
            $error = 'Password lama salah';
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-person-circle" style="font-size: 5rem; color: var(--primary-color);"></i>
                    </div>
                    <h5><?php echo htmlspecialchars($currentUser['name']); ?></h5>
                    <p class="text-muted small"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                    
                    <div class="points-badge mt-3">
                        <i class="bi bi-star-fill"></i>
                        <?php echo number_format($currentUser['loyalty_points']); ?> Poin
                    </div>
                </div>
                
                <div class="list-group list-group-flush">
                    <a href="#profile" class="list-group-item list-group-item-action active">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-bag"></i> Pesanan Saya
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/favorites.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-heart"></i> Favorit
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/loyalty_points.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-star"></i> Loyalty Points
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/gdpr.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-shield-lock"></i> Data & Privasi
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Profile Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Informasi Profil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?php echo htmlspecialchars($currentUser['name']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" disabled
                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>">
                                <small class="text-muted">Email tidak dapat diubah</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-coffee">
                            <i class="bi bi-check-circle"></i> Update Profil
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <?php if ($currentUser['password']): // Only show if not OAuth user ?>
            <div class="card shadow-sm">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0"><i class="bi bi-lock"></i> Ubah Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-coffee">
                            <i class="bi bi-check-circle"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
