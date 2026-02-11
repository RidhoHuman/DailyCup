<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Reset Password';
$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$db = getDB();

$isValidToken = false;
$isLegacyToken = false;
$userId = null;

// 1. Try Secure Token (Phase 2 - Email)
// Note: verifyPasswordResetToken checks password_reset_tokens table
$verification = verifyPasswordResetToken($token);
if ($verification['success']) {
    $isValidToken = true;
    $userId = $verification['user_id'];
} else {
    // 2. Try Legacy Token (Phase 1/WhatsApp fallback)
    // Note: Checks users table
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $isValidToken = true;
        $isLegacyToken = true;
        $userId = $user['id'];
    }
}

if (!$isValidToken) {
    $error = 'Link reset password tidak valid atau telah kadaluarsa.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Use centralized validation
    if (!validatePassword($password)) {
        $error = 'Password harus memiliki minimal 8 karakter, huruf besar, huruf kecil, dan angka.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        if ($isLegacyToken) {
            // Legacy Update (Users Table)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            if ($updateStmt->execute([$hashedPassword, $userId])) {
                $success = 'Password berhasil diperbarui! Silakan login dengan password baru Anda.';
                // Invalidate session to be safe
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_destroy();
                }
            } else {
                $error = 'Gagal memperbarui password (Legacy). Silakan coba lagi.';
            }
        } else {
            // Secure Update (Password Reset Tokens Table)
            $result = resetPasswordWithToken($token, $password);
            if ($result['success']) {
                $success = 'Password berhasil diperbarui! Silakan login dengan password baru Anda.';
            } else {
                 $error = 'Gagal memperbarui password: ' . $result['error'];
            }
        }
    }
}


require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #6F4E37 0%, #D4A574 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-key text-coffee" style="font-size: 3rem;"></i>
                            <h3 class="fw-bold mt-2">Reset Password</h3>
                            <p class="text-muted">Masukkan password baru Anda</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <div class="mt-3 d-grid">
                                <a href="login.php" class="btn btn-coffee">Login Sekarang</a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$success && $isValidToken): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="password" class="form-control" required minlength="8">
                                <small class="text-muted">Min. 8 karakter, huruf besar, kecil, & angka.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="8">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-coffee btn-lg">Simpan Password</button>
                            </div>
                        </form>
                        <?php endif; ?>
                        
                        <?php if (!$isValidToken && !$success): ?>
                        <div class="text-center">
                            <a href="forgot_password.php" class="btn btn-outline-coffee">Minta Link Baru</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
