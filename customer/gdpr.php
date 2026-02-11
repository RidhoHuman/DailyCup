<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Data & Privasi (GDPR)';
requireLogin();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$currentUser = getCurrentUser();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Data Export Request
if (isset($_POST['request_export'])) {
    if (logGDPRRequest($userId, 'export', 'User requested data export via profile.')) {
        $userData = exportUserData($userId);
        if ($userData) {
            $jsonData = json_encode($userData, JSON_PRETTY_PRINT);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="dailycup_data_export_' . date('Ymd') . '.json"');
            echo $jsonData;
            exit;
        } else {
            $error = 'Gagal mengekspor data. Silakan coba lagi.';
        }
    }
}

// Handle Deletion Request
if (isset($_POST['request_deletion'])) {
    if (hasPendingDeletionRequest($userId)) {
        $error = 'Anda sudah memiliki permintaan penghapusan akun yang sedang diproses.';
    } else {
        if (logGDPRRequest($userId, 'delete', 'User requested account deletion.')) {
            $success = 'Permintaan penghapusan akun Anda telah dikirim. Admin akan meninjau permintaan Anda dalam 24-48 jam.';
            
            // Log security event
            logSecurityEvent('account_deletion_requested', $userId);
        } else {
            $error = 'Gagal mengirim permintaan. Silakan hubungi dukungan.';
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
                        <i class="bi bi-shield-check" style="font-size: 5rem; color: var(--primary-color);"></i>
                    </div>
                    <h5>Privasi & Data</h5>
                    <p class="text-muted small">Kelola data pribadi Anda sesuai regulasi GDPR.</p>
                </div>
                
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/customer/profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/gdpr.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-shield-lock"></i> Data & Privasi
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-coffee fw-bold"><i class="bi bi-download me-2"></i> Ekspor Data Anda (Portabilitas Data)</h5>
                </div>
                <div class="card-body">
                    <p>Sesuai dengan hak Anda atas portabilitas data, Anda dapat mengunduh salinan semua data pribadi Anda yang kami simpan dalam format JSON yang dapat dibaca mesin.</p>
                    <p class="text-muted small">Data yang disertakan: Profil, riwayat pesanan, ulasan, dan tiket dukungan.</p>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="request_export" class="btn btn-outline-coffee">
                            <i class="bi bi-cloud-download me-2"></i> Unduh Data Saya (.json)
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-coffee fw-bold"><i class="bi bi-eraser me-2"></i> Hak untuk Dilupakan (Hapus Akun)</h5>
                </div>
                <div class="card-body">
                    <p>Anda memiliki hak untuk meminta penghapusan permanen akun dan semua data pribadi Anda dari sistem kami. Proses ini tidak dapat dibatalkan.</p>
                    
                    <div class="alert alert-warning border-0 bg-light-warning">
                        <i class="bi bi-info-circle me-2"></i> 
                        Setelah permintaan diajukan, tim admin kami akan memverifikasi dan menghapus data Anda dalam waktu maksimal 30 hari sesuai ketentuan GDPR.
                    </div>

                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-person-x me-2"></i> Ajukan Penghapusan Akun
                    </button>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-coffee fw-bold"><i class="bi bi-journal-text me-2"></i> Kebijakan Privasi</h5>
                </div>
                <div class="card-body">
                    <p>Kami berkomitmen untuk melindungi data Anda. Silakan baca Kebijakan Privasi lengkap kami untuk memahami bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi Anda.</p>
                    <a href="<?php echo SITE_URL; ?>/pages/privacy_policy.php" class="btn btn-link p-0 text-coffee">Lihat Kebijakan Privasi <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Penghapusan Akun</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus akun Anda? Semua riwayat pesanan, poin loyalty, dan data Anda akan dihapus secara permanen.</p>
                <div class="mb-3">
                    <label class="form-label small">Tuliskan alasan Anda (Opsional)</label>
                    <textarea class="form-control" name="delete_reason" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="request_deletion" class="btn btn-danger">Ya, Ajukan Penghapusan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
