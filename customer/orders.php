<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Pesanan Saya';
requireLogin();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Check for completed orders with reviewable products
$hasReviewableOrders = false;
foreach ($orders as $order) {
    if ($order['status'] === 'completed') {
        $stmt = $db->prepare("SELECT oi.product_id FROM order_items oi 
                              LEFT JOIN reviews r ON r.product_id = oi.product_id AND r.order_id = oi.order_id AND r.user_id = ?
                              WHERE oi.order_id = ? AND r.id IS NULL LIMIT 1");
        $stmt->execute([$_SESSION['user_id'], $order['id']]);
        if ($stmt->fetch()) {
            $hasReviewableOrders = true;
            break;
        }
    }
}
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-bag"></i> Pesanan Saya</h2>
    
    <?php if ($hasReviewableOrders): ?>
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-star-fill text-warning fs-3 me-3"></i>
            <div>
                <h6 class="mb-1">Sudah Terima Pesanan?</h6>
                <p class="mb-0">Berikan review untuk produk yang sudah Anda coba dan dapatkan <strong>10 poin bonus</strong> setiap review!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> Pesanan Anda telah dibuat. Silakan lakukan pembayaran.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): 
            // Check if order is completed and has reviewable products
            $reviewableCount = 0;
            if ($order['status'] === 'completed') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM order_items oi 
                                      LEFT JOIN reviews r ON r.product_id = oi.product_id AND r.order_id = oi.order_id AND r.user_id = ?
                                      WHERE oi.order_id = ? AND r.id IS NULL");
                $stmt->execute([$_SESSION['user_id'], $order['id']]);
                $reviewableCount = $stmt->fetchColumn();
            }
        ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5><?php echo htmlspecialchars($order['order_number']); ?></h5>
                        <p class="mb-0 text-muted"><?php echo formatDate($order['created_at']); ?></p>
                    </div>
                    <div class="text-end">
                        <div class="mb-2">
                            <span class="badge status-<?php echo $order['status']; ?>">
                                <?php echo ORDER_STATUS[$order['status']]; ?>
                            </span>
                        </div>
                        <h5 class="mb-0 text-coffee"><?php echo formatCurrency($order['final_amount']); ?></h5>
                    </div>
                </div>
                
                <?php if ($reviewableCount > 0): ?>
                <div class="alert alert-warning mb-0 mt-3 py-2">
                    <i class="bi bi-star text-warning"></i> <strong><?php echo $reviewableCount; ?> produk</strong> menunggu review Anda
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="<?php echo SITE_URL; ?>/customer/order_detail.php?id=<?php echo $order['id']; ?>" 
                       class="btn btn-outline-coffee btn-sm">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                    <?php if ($reviewableCount > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/customer/order_detail.php?id=<?php echo $order['id']; ?>#reviewSection" 
                       class="btn btn-coffee btn-sm">
                        <i class="bi bi-star"></i> Tulis Review
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-bag-x" style="font-size: 4rem; color: #ccc;"></i>
            <h4 class="mt-3">Belum Ada Pesanan</h4>
            <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-coffee mt-3">Mulai Belanja</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
