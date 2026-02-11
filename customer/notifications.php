<?php
$pageTitle = 'Notifikasi';
require_once __DIR__ . '/../includes/header.php';

requireLogin();
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bell"></i> Notifikasi</h2>
        <button id="markAllReadBtn" class="btn btn-outline-coffee btn-sm">
            <i class="bi bi-check-all"></i> Tandai Semua Dibaca
        </button>
    </div>
    
    <div class="card shadow-sm">
        <div id="notificationsList" class="card-body p-0">
            <!-- Notifications will be loaded by JavaScript -->
            <div class="text-center py-5">
                <div class="spinner-coffee mx-auto"></div>
                <p class="mt-3 text-muted">Memuat notifikasi...</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
