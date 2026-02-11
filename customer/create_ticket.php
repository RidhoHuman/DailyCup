<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Buat Support Ticket';
requireLogin();

if ($_SESSION['role'] !== 'customer') {
    header('Location: ' . SITE_URL);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = trim($_POST['subject']);
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $message = trim($_POST['message']);
    
    if (!empty($subject) && !empty($message)) {
        // Generate ticket number
        $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        
        $db->beginTransaction();
        try {
            // Create ticket
            $stmt = $db->prepare("INSERT INTO support_tickets (user_id, ticket_number, subject, category, priority, status) 
                                 VALUES (?, ?, ?, ?, ?, 'open')");
            $stmt->execute([$userId, $ticketNumber, $subject, $category, $priority]);
            $ticketId = $db->lastInsertId();
            
            // Add first message
            $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin) 
                                 VALUES (?, ?, ?, 0)");
            $stmt->execute([$ticketId, $userId, $message]);
            
            // Notify admin - get admin user_id first
            $adminStmt = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
            $adminId = $adminStmt->fetchColumn();
            
            if ($adminId) {
                $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                     VALUES (?, 'ticket', 'Ticket Baru', ?)");
                $stmt->execute([$adminId, "Ticket #{$ticketNumber}: " . substr($subject, 0, 50)]);
            }
            
            $db->commit();
            
            $_SESSION['success'] = 'Ticket berhasil dibuat!';
            header('Location: ' . SITE_URL . '/customer/ticket_detail.php?id=' . $ticketId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Gagal membuat ticket: ' . $e->getMessage();
        }
    } else {
        $error = 'Semua field harus diisi!';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb & Quick Navigation -->
<div class="bg-light py-3 border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/customer/contact.php" class="text-decoration-none">
                            Customer Service
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/customer/tickets.php" class="text-decoration-none">
                            My Tickets
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Buat Ticket</li>
                </ol>
            </nav>
            <div class="mt-2 mt-md-0">
                <a href="<?php echo SITE_URL; ?>/customer/tickets.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Tickets
                </a>
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-sm btn-outline-coffee">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h2 class="mb-4"><i class="bi bi-ticket-detailed"></i> Buat Support Ticket</h2>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">Pilih kategori...</option>
                                <option value="order">Masalah Pesanan</option>
                                <option value="product">Masalah Produk</option>
                                <option value="delivery">Masalah Pengiriman</option>
                                <option value="payment">Masalah Pembayaran</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select" required>
                                <option value="low">Low - Pertanyaan umum</option>
                                <option value="medium" selected>Medium - Butuh bantuan</option>
                                <option value="high">High - Mendesak</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" placeholder="Ringkasan masalah Anda" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Pesan <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="6" placeholder="Jelaskan masalah Anda secara detail..." required></textarea>
                            <small class="text-muted">Berikan informasi selengkap mungkin agar kami bisa membantu lebih baik</small>
                        </div>
                        
                        <button type="submit" class="btn btn-coffee btn-lg w-100">
                            <i class="bi bi-send"></i> Submit Ticket
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <h6><i class="bi bi-info-circle"></i> Tips:</h6>
                <ul class="mb-0 small">
                    <li>Jelaskan masalah dengan detail</li>
                    <li>Sertakan nomor order jika terkait pesanan</li>
                    <li>Tim kami akan merespon dalam 1x24 jam</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Floating Back to Home Button -->
<style>
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
}
</style>

<a href="<?php echo SITE_URL; ?>/index.php" class="floating-home-btn" title="Kembali ke Home">
    <i class="bi bi-house-fill"></i>
</a>

<script>
// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'h') {
        e.preventDefault();
        window.location.href = '<?php echo SITE_URL; ?>/index.php';
    }
    if (e.altKey && e.key === 't') {
        e.preventDefault();
        window.location.href = '<?php echo SITE_URL; ?>/customer/tickets.php';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
