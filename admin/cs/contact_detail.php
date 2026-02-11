<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Contact Detail - Customer Service';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$contactId = $_GET['id'] ?? 0;

// Get contact
$stmt = $db->prepare("SELECT * FROM contact_submissions WHERE id = ?");
$stmt->execute([$contactId]);
$contact = $stmt->fetch();

if (!$contact) {
    $_SESSION['error'] = 'Pesan kontak tidak ditemukan!';
    header('Location: ' . SITE_URL . '/admin/cs/contacts.php');
    exit;
}

// Mark as read if status is new
if ($contact['status'] == 'new') {
    $stmt = $db->prepare("UPDATE contact_submissions SET status = 'read' WHERE id = ?");
    $stmt->execute([$contactId]);
    $contact['status'] = 'read';
}

// Handle reply
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_replied'])) {
    $stmt = $db->prepare("UPDATE contact_submissions SET status = 'replied', replied_at = NOW() WHERE id = ?");
    $stmt->execute([$contactId]);
    
    $_SESSION['success'] = 'Status berhasil diupdate menjadi "Replied"';
    header('Location: ' . SITE_URL . '/admin/cs/contact_detail.php?id=' . $contactId);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title"><i class="bi bi-envelope"></i> Detail Pesan Kontak</h1>
                <a href="<?php echo SITE_URL; ?>/admin/cs/contacts.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($contact['subject']); ?></h5>
                        <?php
                        $statusColors = ['new' => 'warning', 'read' => 'info', 'replied' => 'success'];
                        $statusLabels = ['new' => 'Baru', 'read' => 'Dibaca', 'replied' => 'Dibalas'];
                        ?>
                        <span class="badge bg-<?php echo $statusColors[$contact['status']]; ?>">
                            <?php echo $statusLabels[$contact['status']]; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="text-muted">Pesan:</label>
                            <p><?php echo nl2br(htmlspecialchars($contact['message'])); ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Tanggal Kirim:</strong></p>
                                <p><?php echo formatDate($contact['created_at']); ?></p>
                            </div>
                            <?php if ($contact['replied_at']): ?>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Tanggal Dibalas:</strong></p>
                                <p><?php echo formatDate($contact['replied_at']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Contact Info -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi Pengirim</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nama:</strong><br><?php echo htmlspecialchars($contact['name']); ?></p>
                        <p><strong>Email:</strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                                <?php echo htmlspecialchars($contact['email']); ?>
                            </a>
                        </p>
                        <p><strong>Phone:</strong><br><?php echo htmlspecialchars($contact['phone'] ?? '-'); ?></p>
                        
                        <?php if ($contact['phone']): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contact['phone']); ?>" 
                           target="_blank" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-whatsapp"></i> Chat via WhatsApp
                        </a>
                        <?php endif; ?>
                        
                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>?subject=Re: <?php echo urlencode($contact['subject']); ?>" 
                           class="btn btn-primary w-100">
                            <i class="bi bi-envelope"></i> Balas via Email
                        </a>
                    </div>
                </div>
                
                <!-- Actions -->
                <?php if ($contact['status'] != 'replied'): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <button type="submit" name="mark_replied" class="btn btn-success w-100">
                                <i class="bi bi-check-circle"></i> Tandai Sudah Dibalas
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
