<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Refund Request Detail';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$refundId = intval($_GET['id'] ?? 0);

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminNotes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    if (in_array($action, ['approve', 'reject'])) {
        $stmt = $db->prepare("SELECT r.*, o.order_number, o.final_amount, u.name as customer_name, u.loyalty_points
                             FROM returns r 
                             JOIN orders o ON r.order_id = o.id 
                             JOIN users u ON r.user_id = u.id
                             WHERE r.id = ?");
        $stmt->execute([$refundId]);
        $refund = $stmt->fetch();
        
        if ($refund && $refund['status'] === 'pending') {
            $db->beginTransaction();
            
            try {
                $newStatus = $action === 'approve' ? 'approved' : 'rejected';
                
                // Update refund status
                $stmt = $db->prepare("UPDATE returns SET status = ?, admin_notes = ?, processed_by = ?, processed_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $adminNotes, $_SESSION['user_id'], $refundId]);
                
                if ($action === 'approve') {
                    // Process refund based on method
                    if ($refund['refund_method'] === 'loyalty_points') {
                        // Calculate points
                        $stmt = $db->prepare("SELECT rupiah_per_point FROM loyalty_settings WHERE is_active = 1 LIMIT 1");
                        $stmt->execute();
                        $setting = $stmt->fetch();
                        $rupiahPerPoint = $setting ? $setting['rupiah_per_point'] : 100;
                        
                        $pointsToReturn = intval($refund['refund_amount'] / $rupiahPerPoint);
                        
                        // Update user loyalty points
                        $stmt = $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
                        $stmt->execute([$pointsToReturn, $refund['user_id']]);
                        
                        // Log loyalty transaction
                        $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, transaction_type, points, description, order_id) 
                                             VALUES (?, 'earned', ?, ?, ?)");
                        $stmt->execute([
                            $refund['user_id'],
                            $pointsToReturn,
                            "Refund order #{$refund['order_number']} - Approved by admin",
                            $refund['order_id']
                        ]);
                        
                        // Mark as processed
                        $stmt = $db->prepare("UPDATE returns SET refund_processed = 1 WHERE id = ?");
                        $stmt->execute([$refundId]);
                        
                        // Notification
                        createNotification(
                            $refund['user_id'],
                            "Refund Approved!",
                            "Refund Anda sebesar " . formatCurrency($refund['refund_amount']) . " telah disetujui. {$pointsToReturn} loyalty points telah ditambahkan ke akun Anda.",
                            'refund_approved',
                            $refund['order_id']
                        );
                        
                        $_SESSION['success_message'] = "Refund approved & {$pointsToReturn} points telah ditambahkan ke customer.";
                    } else {
                        // Bank transfer - manual process
                        createNotification(
                            $refund['user_id'],
                            "Refund Approved - Transfer Bank",
                            "Refund Anda sebesar " . formatCurrency($refund['refund_amount']) . " telah disetujui. Transfer bank akan diproses dalam 1-3 hari kerja.",
                            'refund_approved',
                            $refund['order_id']
                        );
                        
                        $_SESSION['success_message'] = "Refund approved. Silakan proses transfer bank ke rekening customer.";
                    }
                } else {
                    // Rejected
                    createNotification(
                        $refund['user_id'],
                        "Refund Rejected",
                        "Maaf, permintaan refund Anda untuk order #{$refund['order_number']} ditolak. " . ($adminNotes ? "Alasan: {$adminNotes}" : ''),
                        'refund_rejected',
                        $refund['order_id']
                    );
                    
                    $_SESSION['success_message'] = "Refund rejected.";
                }
                
                $db->commit();
                header('Location: index.php?success=1');
                exit;
                
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Get refund details
$stmt = $db->prepare("SELECT r.*, o.order_number, o.final_amount, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
                     FROM returns r 
                     JOIN orders o ON r.order_id = o.id 
                     JOIN users u ON r.user_id = u.id
                     WHERE r.id = ?");
$stmt->execute([$refundId]);
$refund = $stmt->fetch();

if (!$refund) {
    header('Location: index.php');
    exit;
}

// Get processed by admin name
$processedByName = null;
if ($refund['processed_by']) {
    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$refund['processed_by']]);
    $processedByName = $stmt->fetchColumn();
}

// Parse proof images
$proofImages = json_decode($refund['proof_images'], true) ?: [];

$reasons = [
    'wrong_order' => 'Produk Salah / Tidak Sesuai Pesanan',
    'damaged' => 'Produk Rusak / Tumpah',
    'quality_issue' => 'Kualitas Tidak Sesuai SOP',
    'missing_items' => 'Ada Item yang Kurang',
    'other' => 'Lainnya'
];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-arrow-return-left"></i> Refund Request Detail</h2>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Refund Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-coffee text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Refund Information</h5>
                        <span class="badge bg-<?php echo $refund['status'] === 'pending' ? 'warning' : ($refund['status'] === 'approved' ? 'success' : 'danger'); ?> fs-6">
                            <?php echo ucfirst($refund['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Order Number</label>
                                <div class="fw-bold"><?php echo htmlspecialchars($refund['order_number']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Refund Amount</label>
                                <div class="fw-bold text-danger"><?php echo formatCurrency($refund['refund_amount']); ?></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Refund Method</label>
                                <div>
                                    <?php if ($refund['refund_method'] === 'loyalty_points'): ?>
                                        <span class="badge bg-info">Loyalty Points</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Bank Transfer</span>
                                    <?php endif; ?>
                                    <?php if ($refund['auto_approved']): ?>
                                        <span class="badge bg-success">Auto-Approved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Request Date</label>
                                <div><?php echo formatDate($refund['created_at'], 'd M Y H:i'); ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">Reason</label>
                            <div class="fw-bold"><?php echo $reasons[$refund['reason']] ?? $refund['reason']; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">Description</label>
                            <div class="p-3 bg-light rounded"><?php echo nl2br(htmlspecialchars($refund['description'])); ?></div>
                        </div>
                        
                        <?php if ($refund['refund_method'] === 'bank_transfer'): ?>
                        <div class="alert alert-primary">
                            <h6 class="mb-2"><i class="bi bi-bank"></i> Bank Account Info:</h6>
                            <div><strong>Bank:</strong> <?php echo htmlspecialchars($refund['bank_name']); ?></div>
                            <div><strong>Account Number:</strong> <?php echo htmlspecialchars($refund['bank_account_number']); ?></div>
                            <div><strong>Account Name:</strong> <?php echo htmlspecialchars($refund['bank_account_name']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Proof Images -->
                        <div>
                            <label class="text-muted small">Proof Images</label>
                            <div class="row g-2">
                                <?php foreach ($proofImages as $image): ?>
                                <div class="col-md-4">
                                    <a href="<?php echo SITE_URL; ?>/assets/images/returns/<?php echo $image; ?>" target="_blank">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/returns/<?php echo $image; ?>" 
                                             class="img-fluid rounded border" alt="Proof">
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($refund['status'] !== 'pending'): ?>
                <!-- Admin Response -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Admin Response</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($refund['admin_notes']): ?>
                        <div class="mb-3">
                            <label class="text-muted small">Admin Notes</label>
                            <div class="p-3 bg-light rounded"><?php echo nl2br(htmlspecialchars($refund['admin_notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="text-muted small">Processed By</label>
                                <div><?php echo htmlspecialchars($processedByName ?? 'N/A'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Processed At</label>
                                <div><?php echo $refund['processed_at'] ? formatDate($refund['processed_at'], 'd M Y H:i') : 'N/A'; ?></div>
                            </div>
                        </div>
                        
                        <?php if ($refund['status'] === 'approved' && $refund['refund_processed']): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="bi bi-check-circle-fill"></i> <strong>Refund has been processed</strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-4">
                <!-- Customer Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-coffee text-white">
                        <h5 class="mb-0">Customer Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="text-muted small">Name</label>
                            <div class="fw-bold"><?php echo htmlspecialchars($refund['customer_name']); ?></div>
                        </div>
                        <div class="mb-2">
                            <label class="text-muted small">Email</label>
                            <div><?php echo htmlspecialchars($refund['customer_email']); ?></div>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small">Phone</label>
                            <div><?php echo htmlspecialchars($refund['customer_phone'] ?: 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($refund['status'] === 'pending'): ?>
                <!-- Action Form -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Admin Action Required</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Admin Notes</label>
                                <textarea name="admin_notes" class="form-control" rows="4" 
                                          placeholder="Catatan untuk customer (opsional)..."></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Approve Refund
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg">
                                    <i class="bi bi-x-circle"></i> Reject Refund
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
