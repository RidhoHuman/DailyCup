<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'GDPR Requests Management';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Handle Actions (Approve/Reject/Process)
if (isset($_GET['action']) && isset($_GET['id']) && verifyCSRFToken($_GET['csrf_token'] ?? '')) {
    $requestId = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve_export') {
        // Mark as completed
        $stmt = $db->prepare("UPDATE gdpr_requests SET status = 'completed', completed_at = NOW(), processed_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $requestId]);
        header('Location: gdpr_requests.php?success=export_approved');
        exit;
        
    } elseif ($action === 'approve_delete') {
        // Get user info
        $stmt = $db->prepare("SELECT user_id FROM gdpr_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if ($request) {
            // Mark as completed
            $stmt = $db->prepare("UPDATE gdpr_requests SET status = 'completed', completed_at = NOW(), processed_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $requestId]);
            
            // Delete user account (CASCADE will handle related data)
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$request['user_id']]);
            
            header('Location: gdpr_requests.php?success=account_deleted');
            exit;
        }
        
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("UPDATE gdpr_requests SET status = 'rejected', completed_at = NOW(), processed_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $requestId]);
        header('Location: gdpr_requests.php?success=rejected');
        exit;
    }
}

// Get all GDPR requests
$stmt = $db->query("
    SELECT 
        gr.*,
        u.name as user_name,
        u.email as user_email,
        admin.name as processed_by_name
    FROM gdpr_requests gr
    LEFT JOIN users u ON gr.user_id = u.id
    LEFT JOIN users admin ON gr.processed_by = admin.id
    ORDER BY gr.requested_at DESC
");
$requests = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-shield-check"></i> GDPR Requests Management</h1>
            <div class="badge bg-info text-dark">GDPR Compliant</div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    $messages = [
                        'export_approved' => 'Permintaan ekspor data telah disetujui.',
                        'account_deleted' => 'Akun pengguna telah berhasil dihapus secara permanen.',
                        'rejected' => 'Permintaan telah ditolak.'
                    ];
                    echo $messages[$_GET['success']] ?? 'Aksi berhasil dijalankan.';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="display-6 text-warning mb-2"><i class="bi bi-clock-history"></i></div>
                        <h6 class="text-muted">Pending</h6>
                        <p class="h4 mb-0">
                            <?php echo count(array_filter($requests, fn($r) => $r['status'] === 'pending')); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="display-6 text-success mb-2"><i class="bi bi-check-circle"></i></div>
                        <h6 class="text-muted">Completed</h6>
                        <p class="h4 mb-0">
                            <?php echo count(array_filter($requests, fn($r) => $r['status'] === 'completed')); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="display-6 text-danger mb-2"><i class="bi bi-x-circle"></i></div>
                        <h6 class="text-muted">Rejected</h6>
                        <p class="h4 mb-0">
                            <?php echo count(array_filter($requests, fn($r) => $r['status'] === 'rejected')); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body">
                        <div class="display-6 text-info mb-2"><i class="bi bi-file-earmark-text"></i></div>
                        <h6 class="text-muted">Total Requests</h6>
                        <p class="h4 mb-0"><?php echo count($requests); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">All GDPR Requests</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Request Type</th>
                            <th>Status</th>
                            <th>Requested At</th>
                            <th>Processed By</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                Belum ada permintaan GDPR
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="fw-bold">#<?php echo $req['id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($req['user_name'] ?? 'User Deleted'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($req['user_email'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <?php if ($req['request_type'] === 'export'): ?>
                                        <span class="badge bg-info"><i class="bi bi-download"></i> Data Export</span>
                                    <?php elseif ($req['request_type'] === 'delete'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-trash"></i> Account Deletion</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo $req['request_type']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pending</span>
                                    <?php elseif ($req['status'] === 'completed'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check"></i> Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="bi bi-x"></i> Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('d M Y H:i', strtotime($req['requested_at'])); ?></small>
                                </td>
                                <td>
                                    <?php echo $req['processed_by_name'] ? htmlspecialchars($req['processed_by_name']) : '-'; ?>
                                    <?php if ($req['completed_at']): ?>
                                        <br><small class="text-muted"><?php echo date('d M Y', strtotime($req['completed_at'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars($req['notes'] ?: '-'); ?></small>
                                </td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <?php if ($req['request_type'] === 'export'): ?>
                                            <a href="?action=approve_export&id=<?php echo $req['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                               class="btn btn-sm btn-success me-1"
                                               onclick="return confirm('Approve data export request?')">
                                                <i class="bi bi-check"></i> Approve
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=approve_delete&id=<?php echo $req['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                               class="btn btn-sm btn-danger me-1"
                                               onclick="return confirm('⚠️ PERINGATAN: Ini akan menghapus akun pengguna secara PERMANEN! Lanjutkan?')">
                                                <i class="bi bi-trash"></i> Delete Account
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=reject&id=<?php echo $req['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           onclick="return confirm('Reject this request?')">
                                            <i class="bi bi-x"></i> Reject
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- GDPR Info Box -->
        <div class="alert alert-info mt-4 d-flex align-items-start" role="alert">
            <i class="bi bi-info-circle display-6 me-3"></i>
            <div>
                <h5 class="alert-heading">GDPR Compliance Note</h5>
                <p class="mb-0">
                    <strong>Data Export:</strong> Pengguna berhak mendapatkan salinan data mereka dalam format machine-readable (JSON).<br>
                    <strong>Right to be Forgotten:</strong> Pengguna dapat meminta penghapusan permanen akun dan semua data terkait.<br>
                    <strong>Processing Time:</strong> Anda harus memproses permintaan ini dalam waktu maksimal 30 hari sesuai regulasi GDPR.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
