<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: ' . SITE_URL . '/customer/orders.php');
    exit;
}

$db = getDB();

// Get order details
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/customer/orders.php');
    exit;
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Check if order is completed - for review section
$isCompleted = $order['status'] === 'completed';
$reviewableProducts = [];

// Check if refund is allowed (within 3 days & not already requested)
$canRequestRefund = false;
$refundTimeExpired = false;

if ($isCompleted) {
    // Check refund eligibility
    $completedTime = strtotime($order['updated_at']); // Assuming updated_at changes when completed
    $daysSinceCompletion = (time() - $completedTime) / (60 * 60 * 24);
    
    if ($daysSinceCompletion <= 3) {
        // Check if refund already requested
        $stmtRefund = $db->prepare("SELECT id, status FROM returns WHERE order_id = ? AND user_id = ?");
        $stmtRefund->execute([$orderId, $_SESSION['user_id']]);
        $existingRefund = $stmtRefund->fetch();
        
        if (!$existingRefund) {
            $canRequestRefund = true;
        } else {
            $refundRequested = $existingRefund;
        }
    } else {
        $refundTimeExpired = true;
    }
    
    // Check reviewable products
    foreach ($items as $item) {
        // Check if already reviewed
        $stmtReview = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $stmtReview->execute([$_SESSION['user_id'], $item['product_id'], $orderId]);
        $existingReview = $stmtReview->fetch();
        
        if (!$existingReview) {
            $reviewableProducts[] = $item;
        }
    }
}

$pageTitle = 'Detail Pesanan #' . $order['order_number'];
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt"></i> Detail Pesanan</h2>
        <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="btn btn-outline-coffee">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
    
    <!-- Review Prompt Card - Banner Ungu di paling atas -->
    <?php if ($isCompleted && count($reviewableProducts) > 0): ?>
    <div class="card shadow-lg mb-4 border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body p-4 text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-2"><i class="bi bi-heart-fill"></i> Bagaimana Pengalaman Anda?</h4>
                    <p class="mb-0">Kami sangat menghargai pendapat Anda! Bantu customer lain dengan membagikan pengalaman Anda tentang produk kami. <strong>Dapatkan bonus 10 poin</strong> untuk setiap review!</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-light btn-lg px-4" onclick="scrollToReviewSection()">
                        <i class="bi bi-star-fill text-warning"></i> Tulis Review Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Bukti pembayaran berhasil diunggah. Pesanan Anda akan segera diproses.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Gagal mengunggah bukti pembayaran. Pastikan file berupa gambar (JPG, PNG).
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['refund_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <strong>Refund berhasil diajukan!</strong> <?php echo $_GET['message'] ?? 'Kami akan segera memproses permintaan Anda.'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['refund_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_GET['message'] ?? 'Gagal mengajukan refund. Silakan coba lagi.'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Refund Status Card (if refund requested) -->
    <?php if (isset($refundRequested)): ?>
    <div class="card shadow-sm mb-4 border-<?php echo $refundRequested['status'] === 'approved' ? 'success' : ($refundRequested['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-arrow-return-left fs-1 text-<?php echo $refundRequested['status'] === 'approved' ? 'success' : ($refundRequested['status'] === 'rejected' ? 'danger' : 'warning'); ?>"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Refund Request Status: 
                        <span class="badge bg-<?php echo $refundRequested['status'] === 'approved' ? 'success' : ($refundRequested['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                            <?php echo ucfirst($refundRequested['status']); ?>
                        </span>
                    </h5>
                    <p class="mb-0 text-muted">
                        <?php if ($refundRequested['status'] === 'pending'): ?>
                            Permintaan refund Anda sedang ditinjau oleh admin.
                        <?php elseif ($refundRequested['status'] === 'approved'): ?>
                            Refund Anda telah disetujui dan diproses.
                        <?php else: ?>
                            Permintaan refund Anda ditolak.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0">Item Pesanan</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <small class="text-muted">
                                            <?php 
                                            $details = [];
                                            if ($item['size']) $details[] = 'Size: ' . $item['size'];
                                            if ($item['temperature']) $details[] = 'Temp: ' . $item['temperature'];
                                            echo implode(', ', $details);
                                            ?>
                                        </small>
                                    </td>
                                    <td class="text-center"><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end"><?php echo formatCurrency($item['subtotal']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal</td>
                                    <td class="text-end"><?php echo formatCurrency($order['total_amount']); ?></td>
                                </tr>
                                <?php if ($order['discount_amount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end text-success">Diskon</td>
                                    <td class="text-end text-success">-<?php echo formatCurrency($order['discount_amount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['points_value'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end text-primary">Poin Digunakan</td>
                                    <td class="text-end text-primary">-<?php echo formatCurrency($order['points_value']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="fw-bold fs-5">
                                    <td colspan="3" class="text-end">Total Akhir</td>
                                    <td class="text-end text-coffee"><?php echo formatCurrency($order['final_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0">Informasi Pengiriman & Catatan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small d-block">Metode Pengiriman</label>
                            <span class="fw-bold text-capitalize"><?php echo str_replace('-', ' ', $order['delivery_method']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small d-block">Alamat Pengiriman</label>
                            <span><?php echo $order['delivery_address'] ?: '-'; ?></span>
                        </div>
                        <div class="col-md-12">
                            <label class="text-muted small d-block">Catatan Pesanan</label>
                            <span><?php echo $order['customer_notes'] ?: '-'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small d-block">Nomor Pesanan</label>
                        <h5 class="fw-bold"><?php echo $order['order_number']; ?></h5>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Tanggal Pesanan</label>
                        <span><?php echo formatDate($order['created_at']); ?></span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Status Pesanan</label>
                        <span class="badge status-<?php echo $order['status']; ?> fs-6">
                            <?php echo ORDER_STATUS[$order['status']]; ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small d-block">Status Pembayaran</label>
                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'failed' ? 'danger' : 'warning'); ?> fs-6">
                            <?php echo strtoupper($order['payment_status']); ?>
                        </span>
                    </div>
                    
                    <!-- Download Invoice Button -->
                    <?php if ($order['payment_status'] === 'paid'): ?>
                    <div class="mb-3">
                        <a href="<?php echo SITE_URL; ?>/api/generate_invoice.php?order_id=<?php echo $order['id']; ?>" 
                           target="_blank" 
                           class="btn btn-outline-coffee w-100 btn-sm">
                            <i class="bi bi-file-earmark-pdf"></i> Download Invoice
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Track Order Button -->
                    <?php if (in_array($order['status'], ['ready', 'delivering']) && $order['delivery_type'] === 'delivery'): ?>
                    <div class="mb-0">
                        <a href="<?php echo SITE_URL; ?>/customer/track_order.php?id=<?php echo $order['id']; ?>" 
                           class="btn btn-success w-100 btn-sm">
                            <i class="bi bi-geo-alt-fill"></i> Track Live Delivery
                        </a>
                        <small class="text-muted d-block text-center mt-2">
                            <i class="bi bi-broadcast"></i> Real-time GPS tracking
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Refund Button -->
                    <?php if ($canRequestRefund): ?>
                    <div class="mt-4 pt-3 border-top">
                        <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#refundModal">
                            <i class="bi bi-arrow-return-left"></i> Request Refund
                        </button>
                        <small class="text-muted d-block text-center mt-2">
                            <i class="bi bi-clock"></i> Tersisa <?php echo ceil(3 - $daysSinceCompletion); ?> hari untuk refund
                        </small>
                    </div>
                    <?php elseif ($refundTimeExpired && !isset($refundRequested)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <div class="alert alert-warning mb-0 small">
                            <i class="bi bi-info-circle"></i> Periode refund telah berakhir (max 3 hari setelah completed)
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="card shadow-sm">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0">Informasi Pembayaran</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small d-block">Metode Pembayaran</label>
                        <span class="fw-bold"><?php echo $order['payment_method']; ?></span>
                    </div>
                    
                    <?php if ($order['payment_status'] === 'pending' && $order['status'] !== 'cancelled'): ?>
                        <div class="alert alert-warning small">
                            Silakan lakukan pembayaran dan upload bukti pembayaran untuk diproses.
                        </div>
                        <form action="<?php echo SITE_URL; ?>/customer/upload_payment.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label small">Upload Bukti Pembayaran</label>
                                <input type="file" name="payment_proof" class="form-control form-control-sm" required accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-coffee btn-sm w-100">
                                <i class="bi bi-upload"></i> Upload Bukti
                            </button>
                        </form>
                    <?php elseif ($order['payment_proof']): ?>
                        <div class="text-center">
                            <label class="text-muted small d-block mb-2">Bukti Pembayaran</label>
                            <a href="<?php echo SITE_URL; ?>/assets/images/payments/<?php echo $order['payment_proof']; ?>" target="_blank">
                                <img src="<?php echo SITE_URL; ?>/assets/images/payments/<?php echo $order['payment_proof']; ?>" 
                                     class="img-fluid rounded border" style="max-height: 200px;" alt="Bukti Pembayaran">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Review Section - Lebih Prominent dan Engaging -->
    <?php if ($isCompleted && count($reviewableProducts) > 0): ?>
    <div class="card shadow-sm mt-4" id="reviewSection">
        <div class="card-header bg-coffee text-white">
            <h5 class="mb-0"><i class="bi bi-star-fill"></i> Berikan Review Anda</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Setiap review membantu kami meningkatkan kualitas layanan dan membantu customer lain membuat keputusan yang tepat. <strong class="text-coffee">Bonus 10 poin loyalty untuk setiap review!</strong></p>
            
            <div class="row g-3">
                <?php foreach ($reviewableProducts as $item): ?>
                <div class="col-md-6">
                    <div class="card h-100 border-coffee hover-shadow" style="transition: all 0.3s; cursor: pointer;" 
                         onclick="openReviewModal(<?php echo $item['product_id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>', <?php echo $orderId; ?>)">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-cup-hot-fill text-coffee" style="font-size: 3rem;"></i>
                            <h6 class="mt-3 mb-2"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                            <small class="text-muted d-block mb-3">
                                <?php 
                                $details = [];
                                if ($item['size']) $details[] = $item['size'];
                                if ($item['temperature']) $details[] = $item['temperature'];
                                echo implode(' • ', $details);
                                ?>
                            </small>
                            <button class="btn btn-coffee btn-sm">
                                <i class="bi bi-star"></i> Tulis Review
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php elseif ($isCompleted): ?>
    <div class="alert alert-success mt-4">
        <i class="bi bi-check-circle-fill"></i> <strong>Terima kasih!</strong> Anda sudah memberikan review untuk semua produk di pesanan ini.
    </div>
    <?php endif; ?>
</div>

<!-- Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-arrow-return-left"></i> Request Refund</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="refundForm" action="<?php echo SITE_URL; ?>/api/refund.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="request_refund">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> <strong>Kebijakan Refund:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Refund hanya untuk kesalahan dari pihak cafe (produk salah/rusak/kurang/tidak sesuai SOP)</li>
                            <li>Foto bukti produk WAJIB dilampirkan</li>
                            <li>Refund akan dikembalikan dalam bentuk loyalty points</li>
                            <li>Proses refund <Rp 50.000 otomatis disetujui (instant)</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alasan Refund <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="">Pilih alasan...</option>
                            <option value="wrong_order">Produk Salah / Tidak Sesuai Pesanan</option>
                            <option value="damaged">Produk Rusak / Tumpah</option>
                            <option value="quality_issue">Kualitas Tidak Sesuai SOP</option>
                            <option value="missing_items">Ada Item yang Kurang</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi Detail <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required 
                                  placeholder="Jelaskan secara detail masalah yang Anda alami dengan produk..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Upload Foto Bukti <span class="text-danger">*</span></label>
                        <input type="file" name="proof_images[]" class="form-control" accept="image/*" multiple required>
                        <small class="text-muted">Upload foto produk yang bermasalah (max 3 foto, masing-masing max 2MB)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Metode Refund</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="refund_method" id="refund_loyalty" value="loyalty_points" checked>
                            <label class="form-check-label" for="refund_loyalty">
                                <strong>Loyalty Points</strong> (Instant - Recommended!)
                                <br><small class="text-muted">Points langsung masuk ke akun Anda</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="refund_method" id="refund_bank" value="bank_transfer">
                            <label class="form-check-label" for="refund_bank">
                                <strong>Transfer Bank</strong> (Manual - 1-3 hari kerja)
                                <br><small class="text-muted">Uang akan ditransfer ke rekening Anda</small>
                            </label>
                        </div>
                    </div>
                    
                    <div id="bankAccountSection" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="mb-3">Informasi Rekening Bank</h6>
                                <div class="mb-2">
                                    <label class="form-label">Nama Bank</label>
                                    <input type="text" name="bank_name" class="form-control" placeholder="Contoh: BCA, Mandiri, BNI">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Nomor Rekening</label>
                                    <input type="text" name="bank_account_number" class="form-control" placeholder="1234567890">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Nama Pemilik Rekening</label>
                                    <input type="text" name="bank_account_name" class="form-control" placeholder="Sesuai KTP">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i> 
                        <strong>Jumlah yang akan di-refund:</strong> <?php echo formatCurrency($order['final_amount']); ?>
                    </div>
                    
                    <div id="refundMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-send"></i> Submit Refund Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-coffee text-white">
                <h5 class="modal-title">Tulis Review Produk</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="review_order_id">
                    <input type="hidden" name="product_id" id="review_product_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Produk:</label>
                        <p id="review_product_name" class="text-muted"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rating <span class="text-danger">*</span></label>
                        <div class="rating-stars text-center my-3">
                            <i class="bi bi-star fs-2 star-rating" data-rating="1"></i>
                            <i class="bi bi-star fs-2 star-rating" data-rating="2"></i>
                            <i class="bi bi-star fs-2 star-rating" data-rating="3"></i>
                            <i class="bi bi-star fs-2 star-rating" data-rating="4"></i>
                            <i class="bi bi-star fs-2 star-rating" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="rating_value" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Review Anda</label>
                        <textarea name="review_text" class="form-control" rows="4" 
                                  placeholder="Ceritakan pengalaman Anda dengan produk ini..."></textarea>
                    </div>
                    
                    <div id="reviewMessage"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-coffee">Kirim Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.star-rating {
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}
.star-rating:hover,
.star-rating.active {
    color: #ffc107;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}
</style>

<script>
// Review system
let selectedRating = 0;

function scrollToReviewSection() {
    const section = document.getElementById('reviewSection');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function openReviewModal(productId, productName, orderId) {
    document.getElementById('review_product_id').value = productId;
    document.getElementById('review_product_name').textContent = productName;
    document.getElementById('review_order_id').value = orderId;
    document.getElementById('rating_value').value = '';
    selectedRating = 0;
    
    // Reset stars
    document.querySelectorAll('.star-rating').forEach(star => {
        star.classList.remove('active', 'bi-star-fill');
        star.classList.add('bi-star');
    });
    
    // Show modal
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

// Star rating interaction
document.querySelectorAll('.star-rating').forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        document.getElementById('rating_value').value = selectedRating;
        
        // Update stars
        document.querySelectorAll('.star-rating').forEach((s, index) => {
            if (index < selectedRating) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill', 'active');
            } else {
                s.classList.remove('bi-star-fill', 'active');
                s.classList.add('bi-star');
            }
        });
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        document.querySelectorAll('.star-rating').forEach((s, index) => {
            if (index < rating) {
                s.classList.add('active');
            } else {
                s.classList.remove('active');
            }
        });
    });
});

document.querySelector('.rating-stars')?.addEventListener('mouseleave', function() {
    document.querySelectorAll('.star-rating').forEach((s, index) => {
        if (index < selectedRating) {
            s.classList.add('active');
        } else {
            s.classList.remove('active');
        }
    });
});

// Submit review
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedRating) {
        alert('Silakan pilih rating terlebih dahulu');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('action', 'submit');
    
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengirim...';
    
    fetch('<?php echo SITE_URL; ?>/api/reviews.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reviewMessage').innerHTML = 
                '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            document.getElementById('reviewMessage').innerHTML = 
                '<div class="alert alert-danger">' + data.message + '</div>';
            btn.disabled = false;
            btn.innerHTML = 'Kirim Review';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('reviewMessage').innerHTML = 
            '<div class="alert alert-danger">Terjadi kesalahan. Silakan coba lagi.</div>';
        btn.disabled = false;
        btn.innerHTML = 'Kirim Review';
    });
});

// Refund system
document.querySelectorAll('input[name="refund_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const bankSection = document.getElementById('bankAccountSection');
        if (this.value === 'bank_transfer') {
            bankSection.style.display = 'block';
            // Make bank fields required
            bankSection.querySelectorAll('input').forEach(input => input.required = true);
        } else {
            bankSection.style.display = 'none';
            // Make bank fields not required
            bankSection.querySelectorAll('input').forEach(input => input.required = false);
        }
    });
});

// Submit refund request
document.getElementById('refundForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    
    fetch('<?php echo SITE_URL; ?>/api/refund.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect with success message
            window.location.href = '<?php echo SITE_URL; ?>/customer/order_detail.php?id=<?php echo $orderId; ?>&refund_success=1&message=' + encodeURIComponent(data.message);
        } else {
            document.getElementById('refundMessage').innerHTML = 
                '<div class="alert alert-danger">' + data.message + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Submit Refund Request';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('refundMessage').innerHTML = 
            '<div class="alert alert-danger">Terjadi kesalahan. Silakan coba lagi.</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Submit Refund Request';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
