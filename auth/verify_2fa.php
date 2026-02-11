<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure we have a pending login
if (!isset($_SESSION['2fa_pending_user_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Verifikasi 2FA';
$error = '';
$userId = $_SESSION['2fa_pending_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate check done via session logic usually, or we can add IP check
    $rateLimitIdentifier = ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '_2fa_verify';
    checkRateLimit($rateLimitIdentifier, 5, 300); // 5 attempts per 5 mins

    $code = $_POST['code'] ?? '';
    
    // Clean code
    $code = str_replace(' ', '', $code);

    if (verifyUser2FA($userId, $code)) {
        // Verification Successful
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Complete Login Process (copied from login.php)
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email_verified'] = $user['email_verified'] ?? 0;
        $_SESSION['login_time'] = time();

        // Clear pending 2FA
        unset($_SESSION['2fa_pending_user_id']);
        $redirect = $_SESSION['2fa_redirect'] ?? (SITE_URL . '/customer/index.php');
        unset($_SESSION['2fa_redirect']);

        // Default redirect if empty
        if (empty($redirect) || $redirect === SITE_URL . '/auth/verify_2fa.php') {
             if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                $redirect = SITE_URL . '/admin/index.php';
            } else {
                $redirect = SITE_URL . '/customer/index.php';
            }
        }

        logSecurityEvent('login_success_2fa', $user['id'], ['email' => $user['email']]);
        logActivity('login', 'user', $user['id'], ['method' => '2fa']);
        syncCartToDatabase($user['id']);

        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Kode verifikasi salah. Silakan coba lagi.';
        logSecurityEvent('login_failed_2fa', $userId, ['reason' => 'invalid_code']);
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
                            <i class="bi bi-shield-lock text-coffee" style="font-size: 3rem;"></i>
                            <h3 class="fw-bold mt-2">Verifikasi Dua Langkah</h3>
                            <p class="text-muted">Masukkan kode 6 digit dari aplikasi authenticator Anda.</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label text-center w-100">Kode Authenticator</label>
                                <input type="text" name="code" class="form-control form-control-lg text-center letter-spacing-2" 
                                       placeholder="000 000" maxlength="7" autocomplete="one-time-code" autofocus required
                                       style="letter-spacing: 0.5em; font-size: 1.5rem;">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-coffee btn-lg">Verifikasi</button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none text-muted small">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>