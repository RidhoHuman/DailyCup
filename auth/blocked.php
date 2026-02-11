<?php
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Akses Diblokir Sementara';
$retryAfter = intval($_GET['retry_after'] ?? 900);

// If already can retry (time passed), redirect back to login
if ($retryAfter <= 0) {
    header('Location: ' . SITE_URL . '/auth/login.php');
    exit;
}

$minutes = ceil($retryAfter / 60);

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .blocked-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .blocked-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 600px;
        margin: 0 auto;
    }
    .blocked-icon {
        font-size: 5rem;
        color: #f44336;
        animation: shake 0.5s;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }
    .countdown-timer {
        font-size: 3rem;
        font-weight: bold;
        color: #6F4E37;
        font-family: 'Courier New', monospace;
        text-align: center;
        margin: 20px 0;
        padding: 20px;
        background: #f5f5f5;
        border-radius: 10px;
    }
    .info-box {
        background: #e3f2fd;
        padding: 20px;
        border-left: 4px solid #2196F3;
        border-radius: 5px;
        margin: 20px 0;
    }
    .warning-box {
        background: #fff3cd;
        padding: 20px;
        border-left: 4px solid #ffc107;
        border-radius: 5px;
        margin: 20px 0;
    }
</style>

<div class="blocked-container">
    <div class="container">
        <div class="blocked-card">
            <div class="text-center">
                <div class="blocked-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h1 class="mt-3 mb-2">Akses Login Diblokir Sementara</h1>
                <p class="text-muted">Untuk keamanan akun Anda</p>
            </div>

            <div class="warning-box mt-4">
                <h5><i class="bi bi-exclamation-triangle-fill text-warning"></i> Apa yang Terjadi?</h5>
                <p class="mb-0">
                    Anda telah melakukan <strong>5 percobaan login gagal</strong> berturut-turut. 
                    Sistem keamanan kami secara otomatis memblokir akses login sementara untuk melindungi akun Anda dari percobaan akses tidak sah.
                </p>
            </div>

            <div class="text-center">
                <p class="mb-2">Anda dapat login kembali dalam:</p>
                <div class="countdown-timer" id="countdown">
                    <span id="minutes">--</span>:<span id="seconds">--</span>
                </div>
                <small class="text-muted">Halaman akan otomatis refresh setelah waktu habis</small>
            </div>

            <div class="info-box">
                <h5><i class="bi bi-info-circle-fill text-info"></i> Ini BUKAN Error!</h5>
                <p class="mb-2">
                    Pemblokiran ini adalah <strong>fitur keamanan</strong> untuk melindungi akun Anda dari serangan brute force. 
                    Setelah waktu tunggu selesai, Anda dapat login kembali seperti biasa.
                </p>
                <p class="mb-0">
                    <strong>Tips:</strong> Jika Anda lupa password, gunakan fitur "Lupa Password" daripada menebak-nebak password.
                </p>
            </div>

            <div class="d-grid gap-2 mt-4">
                <a href="<?php echo SITE_URL; ?>/auth/forgot_password.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-key-fill"></i> Lupa Password?
                </a>
                <a href="<?php echo SITE_URL; ?>/customer/contact.php" class="btn btn-outline-secondary">
                    <i class="bi bi-headset"></i> Hubungi Customer Service
                </a>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-coffee">
                    <i class="bi bi-house-fill"></i> Kembali ke Beranda
                </a>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <h6>Mengapa Ini Penting?</h6>
                <small class="text-muted">
                    Pemblokiran otomatis melindungi akun Anda dari hacker yang mencoba menebak password Anda berkali-kali. 
                    Ini adalah praktik keamanan standar yang digunakan oleh bank dan platform online terkemuka.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
    let totalSeconds = <?php echo $retryAfter; ?>;
    
    function updateCountdown() {
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
        
        if (totalSeconds <= 0) {
            // Redirect back to login
            window.location.href = '<?php echo SITE_URL; ?>/auth/login.php?unblocked=1';
        }
        
        totalSeconds--;
    }
    
    // Update every second
    updateCountdown();
    setInterval(updateCountdown, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
