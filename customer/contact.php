<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Hubungi Kami';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (!empty($name) && !empty($email) && !empty($subject) && !empty($message)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contact_submissions (name, email, phone, subject, message) 
                             VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $subject, $message]);
        
        $_SESSION['success'] = 'Pesan Anda berhasil terkirim! Kami akan menghubungi Anda segera.';
        header('Location: ' . SITE_URL . '/customer/contact.php');
        exit;
    } else {
        $error = 'Semua field wajib diisi!';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb & Quick Navigation -->
<div class="bg-light py-3 border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Customer Service</li>
                </ol>
            </nav>
            <div>
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Kembali ke Home
                </a>
                <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-sm btn-coffee">
                    <i class="bi bi-cup-hot"></i> Lihat Menu
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h2 class="text-center mb-4">📧 Hubungi Kami</h2>
            <p class="text-center text-muted mb-4">
                Ada pertanyaan atau feedback? Kirim pesan kepada kami dan kami akan membalas secepatnya!
            </p>
            
            <?php if (isLoggedIn()): ?>
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">Halo, <?php echo htmlspecialchars($currentUser['name']); ?>!</h6>
                        <p class="mb-2">Untuk layanan yang lebih baik dan tracking lebih mudah, gunakan fitur Support Ticket.</p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo SITE_URL; ?>/customer/create_ticket.php" class="btn btn-info btn-sm me-2">
                        <i class="bi bi-ticket-detailed"></i> Buat Support Ticket
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/tickets.php" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-list-task"></i> Lihat Ticket Saya
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon (Opsional)</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" placeholder="Judul pesan Anda" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Pesan <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="6" placeholder="Tulis pesan Anda..." required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-coffee btn-lg w-100">
                            <i class="bi bi-send"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Alternative Contact Methods -->
            <div class="row g-3 mt-4">
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded h-100">
                        <i class="bi bi-whatsapp text-success" style="font-size: 40px;"></i>
                        <h6 class="mt-2">WhatsApp</h6>
                        <p class="small text-muted mb-2">Chat langsung dengan CS</p>
                        <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-whatsapp"></i> Chat
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded h-100">
                        <i class="bi bi-envelope" style="font-size: 40px; color: #6F4E37;"></i>
                        <h6 class="mt-2">Email</h6>
                        <p class="small text-muted mb-2">support@dailycup.com</p>
                        <a href="mailto:support@dailycup.com" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded h-100">
                        <i class="bi bi-ticket-detailed text-primary" style="font-size: 40px;"></i>
                        <h6 class="mt-2">Support Ticket</h6>
                        <p class="small text-muted mb-2">Buat ticket untuk tracking</p>
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/customer/create_ticket.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle"></i> Buat Ticket
                        </a>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php?redirect=customer/create_ticket.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links Section -->
            <div class="card mt-4 border-coffee">
                <div class="card-body">
                    <h6 class="card-title text-coffee mb-3">
                        <i class="bi bi-lightning-charge"></i> Akses Cepat
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-sm btn-outline-coffee">
                            <i class="bi bi-house"></i> Home
                        </a>
                        <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-sm btn-outline-coffee">
                            <i class="bi bi-cup-hot"></i> Menu
                        </a>
                        <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="btn btn-sm btn-outline-coffee">
                            <i class="bi bi-bag"></i> Pesanan Saya
                        </a>
                        <a href="<?php echo SITE_URL; ?>/customer/track_order.php" class="btn btn-sm btn-outline-coffee">
                            <i class="bi bi-geo-alt"></i> Track Order
                        </a>
                        <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-sm btn-outline-coffee">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-sm btn-outline-coffee">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Back to Home Button -->
<style>
/* Add padding to bottom of page to prevent floating button overlap */
.container {
    padding-bottom: 100px !important;
}

.floating-home-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #6F4E37 0%, #8B6F47 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.3);
    text-decoration: none;
    transition: all 0.3s ease;
    z-index: 999;
    border: 3px solid white;
}

.floating-home-btn:hover {
    background: linear-gradient(135deg, #8B6F47 0%, #6F4E37 100%);
    color: white;
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 6px 20px rgba(111, 78, 55, 0.5);
}

.floating-home-btn .tooltip-text {
    visibility: hidden;
    width: 120px;
    background-color: #333;
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 5px;
    position: absolute;
    z-index: 1;
    bottom: 100%;
    left: 50%;
    margin-left: -60px;
    margin-bottom: 10px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s;
}

.floating-home-btn:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

@media (max-width: 768px) {
    .container {
        padding-bottom: 120px !important;
    }
    
    .floating-home-btn {
        width: 50px;
        height: 50px;
        font-size: 20px;
        bottom: 20px;
        right: 20px;
    }
    
    /* Ensure alternative contact methods have enough space */
    .row.g-3.mt-4 {
        margin-bottom: 40px !important;
    }
}

/* Add margin to alternative contact section */
.row.g-3.mt-4 {
    margin-bottom: 30px;
}
</style>

<a href="<?php echo SITE_URL; ?>/index.php" class="floating-home-btn" title="Kembali ke Home">
    <i class="bi bi-house-fill"></i>
    <span class="tooltip-text">Kembali ke Home</span>
</a>

<!-- Quick Navigation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + H = Home
        if (e.altKey && e.key === 'h') {
            e.preventDefault();
            window.location.href = '<?php echo SITE_URL; ?>/index.php';
        }
        // Alt + M = Menu
        if (e.altKey && e.key === 'm') {
            e.preventDefault();
            window.location.href = '<?php echo SITE_URL; ?>/customer/menu.php';
        }
        // Escape = Back to Home
        if (e.key === 'Escape') {
            if (confirm('Kembali ke halaman Home?')) {
                window.location.href = '<?php echo SITE_URL; ?>/index.php';
            }
        }
    });
    
    // Smooth scroll for floating button
    const floatingBtn = document.querySelector('.floating-home-btn');
    if (floatingBtn) {
        // Show/hide based on scroll
        let lastScroll = 0;
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            if (currentScroll > 200) {
                floatingBtn.style.opacity = '1';
                floatingBtn.style.pointerEvents = 'auto';
            } else {
                floatingBtn.style.opacity = '0.7';
            }
            lastScroll = currentScroll;
        });
    }
    
    // Add tooltip info to page
    const infoBox = document.createElement('div');
    infoBox.className = 'alert alert-light border text-center mt-3';
    infoBox.innerHTML = '<small class="text-muted"><i class="bi bi-keyboard"></i> <strong>Tips:</strong> Tekan <kbd>Alt + H</kbd> untuk Home, <kbd>Alt + M</kbd> untuk Menu, atau <kbd>Esc</kbd> untuk kembali</small>';
    
    const container = document.querySelector('.container .col-lg-8');
    if (container && container.lastElementChild) {
        container.appendChild(infoBox);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
