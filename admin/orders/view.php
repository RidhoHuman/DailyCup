<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Order Details';
$isAdminPage = true;
requireAdmin();

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: ' . SITE_URL . '/admin/orders/');
    exit;
}

$db = getDB();

// Get order details FIRST (need user_id and order_number for notification)
$stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/admin/orders/');
    exit;
}

// Handle Manual Assign Kurir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_kurir'])) {
    $kurirId = intval($_POST['kurir_id'] ?? 0);
    
    if ($kurirId > 0) {
        // Update order with kurir
        $stmt = $db->prepare("UPDATE orders SET kurir_id = ?, assigned_at = NOW() WHERE id = ?");
        $stmt->execute([$kurirId, $orderId]);
        
        // Get kurir name
        $stmt = $db->prepare("SELECT name FROM kurir WHERE id = ?");
        $stmt->execute([$kurirId]);
        $kurirData = $stmt->fetch();
        
        // Log delivery history
        $stmt = $db->prepare("INSERT INTO delivery_history (order_id, kurir_id, status, notes) 
                             VALUES (?, ?, 'assigned', 'Manually assigned by admin')");
        $stmt->execute([$orderId, $kurirId]);
        
        // Notify customer
        createNotification(
            $order['user_id'],
            "Kurir Assigned!",
            "Order #{$order['order_number']} telah ditugaskan ke kurir: {$kurirData['name']}. Pesanan Anda akan segera diantar!",
            'order_update',
            $orderId
        );
        
        // Notify kurir
        $stmt = $db->prepare("INSERT INTO kurir_notifications (kurir_id, type, title, message, order_id) 
                             VALUES (?, 'new_order', 'Pesanan Baru!', ?, ?)");
        $stmt->execute([
            $kurirId,
            "Anda mendapat pesanan baru #{$order['order_number']} dari {$order['customer_name']}. Total: " . formatCurrency($order['final_amount']),
            $orderId
        ]);
        
        $success = "Kurir berhasil di-assign ke order ini!";
        
        // Refresh order data
        $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                           FROM orders o 
                           JOIN users u ON o.user_id = u.id 
                           WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
    }
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';
    $oldStatus = $order['status'] ?? '';
    
    if (array_key_exists($newStatus, ORDER_STATUS)) {
        $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $paymentStatus, $orderId]);
        
        // CREATE NOTIFICATION for customer when status changes
        if ($oldStatus !== $newStatus) {
            $statusLabel = ORDER_STATUS[$newStatus];
            $notifTitle = "Status Pesanan Diperbarui";
            $notifMessage = "Pesanan #{$order['order_number']} telah diperbarui menjadi: {$statusLabel}";
            $notifType = 'order_update';
            
            createNotification($order['user_id'], $notifTitle, $notifMessage, $notifType, $orderId);
            
            // GIVE LOYALTY POINTS when order completed
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $points = calculateLoyaltyPoints($order['final_amount']);
                if ($points > 0) {
                    updateUserPoints(
                        $order['user_id'], 
                        $points, 
                        'earned', 
                        $orderId, 
                        "Pembelian senilai " . formatCurrency($order['final_amount'])
                    );
                }
                
                // AUTO-SEND INVOICE EMAIL
                require_once __DIR__ . '/../../api/send_invoice_email.php';
                sendInvoiceEmail($orderId);
            }
        }
        
        $success = "Status pesanan berhasil diperbarui.";
        
        // Refresh order data after update
        $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                           FROM orders o 
                           JOIN users u ON o.user_id = u.id 
                           WHERE o.id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
    }
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Get available kurir list
$stmt = $db->prepare("SELECT id, name, phone, status FROM kurir WHERE is_active = 1 ORDER BY status ASC, name ASC");
$stmt->execute();
$kurirList = $stmt->fetchAll();

// Get assigned kurir info if exists
$assignedKurir = null;
if ($order['kurir_id']) {
    $stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
    $stmt->execute([$order['kurir_id']]);
    $assignedKurir = $stmt->fetch();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title"><i class="bi bi-receipt"></i> Order #<?php echo $order['order_number']; ?></h1>
            <a href="<?php echo SITE_URL; ?>/admin/orders/" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <!-- Order Items -->
                <div class="admin-table mb-4">
                    <div class="p-3 border-bottom bg-light">
                        <h5 class="mb-0">Item Pesanan</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Harga</th>
                                    <th class="text-center">Qty</th>
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
                            <tfoot class="bg-light">
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
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total Akhir</td>
                                    <td class="text-end text-coffee"><?php echo formatCurrency($order['final_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Customer & Delivery Info -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Informasi Pelanggan</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1 text-muted small">Nama:</p>
                                <p class="fw-bold mb-3"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                
                                <p class="mb-1 text-muted small">Email:</p>
                                <p class="mb-3"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                                
                                <p class="mb-1 text-muted small">Telepon:</p>
                                <p class="mb-0"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Pengiriman</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1 text-muted small">Metode:</p>
                                <p class="fw-bold mb-3 text-capitalize"><?php echo str_replace('-', ' ', $order['delivery_method']); ?></p>
                     Assign Kurir (Only for delivery orders) -->
                <?php if ($order['delivery_method'] === 'delivery'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Assign Kurir</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($assignedKurir): ?>
                            <!-- Already Assigned -->
                            <div class="alert alert-success mb-3">
                                <i class="bi bi-check-circle-fill"></i> Kurir sudah di-assign
                            </div>
                            <div class="mb-2">
                                <strong>Nama Kurir:</strong><br>
                                <?php echo htmlspecialchars($assignedKurir['name']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Phone:</strong><br>
                                <?php echo htmlspecialchars($assignedKurir['phone']); ?>
                            </div>
                            <div class="mb-0">
                                <strong>Status:</strong><br>
                                <span class="badge bg-<?php echo $assignedKurir['status'] === 'available' ? 'success' : ($assignedKurir['status'] === 'busy' ? 'warning' : 'secondary'); ?>">
                                    <?php echo strtoupper($assignedKurir['status']); ?>
                                </span>
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Assigned: <?php echo formatDate($order['assigned_at'], 'd M Y H:i'); ?>
                            </small>
                        <?php else: ?>
                            <!-- Not Assigned Yet -->
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle"></i> Kurir belum di-assign
                            </div>
                            <?php if (count($kurirList) > 0): ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Pilih Kurir:</label>
                                        <select name="kurir_id" class="form-select" required>
                                            <option value="">-- Pilih Kurir --</option>
                                            <?php foreach ($kurirList as $k): ?>
                                            <option value="<?php echo $k['id']; ?>">
                                                <?php echo htmlspecialchars($k['name']); ?> 
                                                (<?php echo ucfirst($k['status']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Total kurir aktif: <?php echo count($kurirList); ?></small>
                                    </div>
                                    <button type="submit" name="assign_kurir" class="btn btn-info w-100">
                                        <i class="bi bi-person-check"></i> Assign Kurir
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    Tidak ada kurir yang tersedia saat ini.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!--            
                                <p class="mb-1 text-muted small">Alamat:</p>
                                <p class="mb-3"><?php echo $order['delivery_address'] ?: '-'; ?></p>
                                
                                <p class="mb-1 text-muted small">Catatan:</p>
                                <p class="mb-0"><?php echo $order['customer_notes'] ?: '-'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Status Update -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-coffee text-white">
                        <h5 class="mb-0">Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Status Pesanan</label>
                                <select name="status" class="form-select">
                                    <?php foreach (ORDER_STATUS as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $order['status'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status Pembayaran</label>
                                <select name="payment_status" class="form-select">
                                    <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>PENDING</option>
                                    <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>PAID</option>
                                    <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>FAILED</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-coffee w-100">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Payment Proof -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Bukti Pembayaran</h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-2 text-muted small">Metode: <?php echo $order['payment_method']; ?></p>
                        <?php if ($order['payment_proof']): ?>
                            <a href="<?php echo SITE_URL; ?>/assets/images/payments/<?php echo $order['payment_proof']; ?>" target="_blank">
                                <img src="<?php echo SITE_URL; ?>/assets/images/payments/<?php echo $order['payment_proof']; ?>" 
                                     class="img-fluid rounded border mb-2" style="max-height: 300px;" alt="Bukti Pembayaran">
                            </a>
                            <p class="small text-muted">Klik gambar untuk memperbesar</p>
                        <?php else: ?>
                            <div class="py-4 text-muted">
                                <i class="bi bi-image-fill fs-1 d-block mb-2"></i>
                                Belum ada bukti pembayaran
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
