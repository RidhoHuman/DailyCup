<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Lupa Password';
$error = '';
$success = '';
$waUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitizeInput($_POST['identifier'] ?? '');
    $method = $_POST['method'] ?? 'email'; // 'email' or 'whatsapp'
    
    if (empty($identifier)) {
        $error = 'Email atau Nomor WhatsApp harus diisi';
    } else {
        $db = getDB();
        
        // Check if user exists by email or phone
        if ($method === 'email') {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND is_active = 1");
        }
        
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($method === 'email') {
                $result = generatePasswordResetToken($user['email']);
                if ($result['success']) {
                    $success = 'Link reset password telah dikirim ke email Anda.';
                } else {
                    $error = 'Gagal mengirim email reset: ' . $result['error'];
                }
            } else {
                // WhatsApp method legacy support
                $token = generateToken();
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $updateStmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $updateStmt->execute([$token, $expires, $user['id']]);
                $resetLink = SITE_URL . "/auth/reset_password.php?token=" . $token;

                $message = "Halo {$user['name']}, berikut adalah link untuk mereset password DailyCup Anda: {$resetLink}. Link ini berlaku selama 1 jam.";
                $waUrl = sendWhatsApp($user['phone'], $message);
                $success = 'Klik tombol di bawah untuk mengirim link reset melalui WhatsApp.';
            }
        } else {
            // For security, mimic behavior for user not found
            if ($method === 'email') {
                 $success = 'Link reset password telah dikirim ke email Anda.';
            } else {
                 $error = 'Akun tidak ditemukan atau tidak aktif.';
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
                            <i class="bi bi-shield-lock text-coffee" style="font-size: 3rem;"></i>
                            <h3 class="fw-bold mt-2">Lupa Password?</h3>
                            <p class="text-muted">Masukkan email atau nomor WhatsApp Anda untuk mereset password</p>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <?php if ($waUrl): ?>
                            <div class="mt-3 d-grid">
                                <a href="<?php echo $waUrl; ?>" target="_blank" class="btn btn-success">
                                    <i class="bi bi-whatsapp"></i> Kirim via WhatsApp
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$success || $error): ?>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Metode Reset</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="method" id="methodEmail" value="email" checked>
                                        <label class="form-check-label" for="methodEmail">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="method" id="methodWA" value="whatsapp">
                                        <label class="form-check-label" for="methodWA">WhatsApp</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="inputContainer">
                                <label class="form-label" id="identifierLabel">Email</label>
                                <input type="text" name="identifier" id="identifierInput" class="form-control" required 
                                       placeholder="Masukkan email Anda">
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-coffee btn-lg">Kirim Link Reset</button>
                            </div>
                        </form>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none text-coffee">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const label = document.getElementById('identifierLabel');
        const input = document.getElementById('identifierInput');
        if (this.value === 'email') {
            label.textContent = 'Email';
            input.placeholder = 'Masukkan email Anda';
            input.type = 'email';
        } else {
            label.textContent = 'Nomor WhatsApp';
            input.placeholder = 'Contoh: 08123456789';
            input.type = 'text';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
