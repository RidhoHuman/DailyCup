<?php
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/customer/index.php');
    exit;
}

$pageTitle = 'Login';
$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = 'Registrasi berhasil! Silakan login.';
}

if (isset($_GET['unblocked'])) {
    $success = 'Pemblokiran sudah dibuka. Anda sekarang dapat login kembali.';
}

if (isset($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
} else {
    $redirect = SITE_URL . '/customer/index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting - 5 attempts per 15 minutes
    $rateLimitIdentifier = ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '_login';
    $rateLimit = checkRateLimit($rateLimitIdentifier, 5, 900);
    
    $email = validateInput($_POST['email'] ?? '', 'email');
    $password = $_POST['password'] ?? '';
    
    if (!$email || empty($password)) {
        $error = 'Email dan password harus diisi dengan benar';
        logSecurityEvent('login_failed', null, ['email' => $_POST['email'] ?? '', 'reason' => 'invalid_input']);
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Clear rate limit on successful login
            clearRateLimit($rateLimitIdentifier);
            
            // Check if 2FA is enabled
            if (is2FAEnabled($user['id'])) {
                // Store temp user ID for 2FA verification
                // Do NOT set logged in session yet
                $_SESSION['2fa_pending_user_id'] = $user['id'];
                
                // Keep the intended redirect URL
                if (isset($_GET['redirect'])) {
                    $_SESSION['2fa_redirect'] = $_GET['redirect'];
                } elseif (isset($redirect)) {
                     $_SESSION['2fa_redirect'] = $redirect;
                }
                
                header('Location: ' . SITE_URL . '/auth/verify_2fa.php');
                exit;
            }

            // Successful login - regenerate session
            session_regenerate_id(true);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email_verified'] = $user['email_verified'] ?? 0;
            $_SESSION['login_time'] = time();
            
            // Log successful login
            logSecurityEvent('login_success', $user['id'], ['email' => $email]);
            logActivity('login', 'user', $user['id'], ['method' => 'email']);
            
            // Load cart from database (PERSISTENT CART)
            syncCartToDatabase($user['id']);
            
            // Redirect based on role if no specific redirect is set
            if (!isset($_GET['redirect'])) {
                if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                    $redirect = SITE_URL . '/admin/index.php';
                } else {
                    $redirect = SITE_URL . '/customer/index.php';
                }
            }
            
            // Redirect
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email atau password salah';
            
            // Progressive warning based on attempts
            if ($rateLimit['remaining'] == 2) {
                $error .= ' <br><small class="text-warning">⚠️ Sisa 2 percobaan lagi sebelum akun terblokir sementara!</small>';
            } elseif ($rateLimit['remaining'] == 1) {
                $error .= ' <br><small class="text-danger"><strong>⚠️ PERHATIAN: Ini percobaan terakhir Anda! Setelah ini akun akan terblokir 15 menit.</strong></small>';
            } elseif ($rateLimit['remaining'] == 0) {
                $error .= ' <br><small class="text-muted">Gunakan tombol "Lupa Password" jika Anda tidak ingat password Anda.</small>';
            }
            
            logSecurityEvent('login_failed', null, ['email' => $email, 'reason' => 'invalid_credentials']);
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
                            <i class="bi bi-cup-hot-fill text-coffee" style="font-size: 3rem;"></i>
                            <h3 class="fw-bold mt-2">Welcome Back!</h3>
                            <p class="text-muted">Login ke akun DailyCup Anda</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0"></label>
                                    <a href="forgot_password.php" class="text-coffee small text-decoration-none">Lupa Password?</a>
                                </div>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-coffee btn-lg">Login</button>
                            </div>
                        </form>
                        
                        <div class="text-center mb-3">
                            <span class="text-muted">Atau login dengan</span>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>/auth/google_login.php" class="btn btn-outline-danger">
                                <i class="bi bi-google"></i> Login dengan Google
                            </a>
                            <a href="<?php echo SITE_URL; ?>/auth/facebook_login.php" class="btn btn-outline-primary">
                                <i class="bi bi-facebook"></i> Login dengan Facebook
                            </a>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Belum punya akun? 
                                <a href="<?php echo SITE_URL; ?>/auth/register.php" class="text-coffee fw-bold">Daftar Sekarang</a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="text-white">
                        <i class="bi bi-arrow-left"></i> Kembali ke Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
