<?php
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/customer/index.php');
    exit;
}

$pageTitle = 'Register';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan password harus diisi';
    } elseif ($password !== $confirmPassword) {
        $error = 'Password tidak cocok';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        $db = getDB();
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
            
            if ($stmt->execute([$name, $email, $phone, $hashedPassword])) {
                $userId = $db->lastInsertId();
                
                // Send email verification
                $emailResult = sendEmailVerification($userId, $email);
                
                if ($emailResult['success']) {
                    header('Location: ' . SITE_URL . '/auth/login.php?verification_sent=1');
                } else {
                    // Registration successful but email failed - still allow login
                    header('Location: ' . SITE_URL . '/auth/login.php?registered=1&email_error=1');
                }
                exit;
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #6F4E37 0%, #D4A574 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-cup-hot-fill text-coffee" style="font-size: 3rem;"></i>
                            <h3 class="fw-bold mt-2">Buat Akun Baru</h3>
                            <p class="text-muted">Bergabunglah dengan DailyCup sekarang!</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nomor Telepon (Opsional)</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required 
                                       minlength="6">
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-coffee btn-lg">Daftar</button>
                            </div>
                        </form>
                        
                        <div class="text-center mb-3">
                            <span class="text-muted">Atau daftar dengan</span>
                        </div>
                        
                        <div class="d-grid gap-2 mb-3">
                            <a href="<?php echo SITE_URL; ?>/auth/google_login.php" class="btn btn-outline-danger">
                                <i class="bi bi-google"></i> Daftar dengan Google
                            </a>
                            <a href="<?php echo SITE_URL; ?>/auth/facebook_login.php" class="btn btn-outline-primary">
                                <i class="bi bi-facebook"></i> Daftar dengan Facebook
                            </a>
                        </div>
                        
                        <div class="text-center">
                            <p class="mb-0">Sudah punya akun? 
                                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-coffee fw-bold">Login</a>
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
