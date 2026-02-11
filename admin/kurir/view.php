<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Detail Kurir';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$kurirId = intval($_GET['id'] ?? 0);

if (!$kurirId) {
    header('Location: ' . SITE_URL . '/admin/kurir/index.php');
    exit;
}

// Get kurir details
$stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
$stmt->execute([$kurirId]);
$kurir = $stmt->fetch();

if (!$kurir) {
    header('Location: ' . SITE_URL . '/admin/kurir/index.php?error=not_found');
    exit;
}

// Get delivery history
$stmt = $db->prepare("SELECT dh.*, o.order_number, o.final_amount, o.created_at as order_date
                      FROM delivery_history dh
                      JOIN orders o ON dh.order_id = o.id
                      WHERE dh.kurir_id = ?
                      ORDER BY dh.created_at DESC
                      LIMIT 20");
$stmt->execute([$kurirId]);
$deliveryHistory = $stmt->fetchAll();

// Get active orders
$stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone
                      FROM orders o
                      JOIN users u ON o.user_id = u.id
                      WHERE o.kurir_id = ? 
                      AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                      ORDER BY o.created_at ASC");
$stmt->execute([$kurirId]);
$activeOrders = $stmt->fetchAll();

// Get completed orders stats
$stmt = $db->prepare("SELECT 
                      COUNT(*) as total_completed,
                      SUM(final_amount) as total_revenue,
                      AVG(TIMESTAMPDIFF(MINUTE, pickup_time, delivery_time)) as avg_delivery_time,
                      SUM(CASE WHEN DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) as today_deliveries
                      FROM orders 
                      WHERE kurir_id = ? AND status = 'completed'");
$stmt->execute([$kurirId]);
$stats = $stmt->fetch();

// Get current location
$stmt = $db->prepare("SELECT * FROM kurir_location WHERE kurir_id = ? ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$kurirId]);
$location = $stmt->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.info-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.stat-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #6F4E37;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    color: #6F4E37;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
}

.timeline-item {
    border-left: 2px solid #e0e0e0;
    padding-left: 20px;
    padding-bottom: 20px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #6F4E37;
}

.timeline-item:last-child {
    border-left: none;
}

#mapView {
    height: 300px;
    width: 100%;
    border-radius: 10px;
}
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-person-badge"></i> Detail Kurir</h1>
            <div>
                <a href="edit.php?id=<?php echo $kurirId; ?>" class="btn btn-coffee me-2">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Kurir Profile -->
            <div class="col-lg-4">
                <div class="info-card">
                    <div class="text-center mb-3">
                        <?php if ($kurir['photo']): ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/kurir/<?php echo htmlspecialchars($kurir['photo']); ?>" 
                             class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;" alt="<?php echo htmlspecialchars($kurir['name']); ?>">
                        <?php else: ?>
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" 
                             style="width: 120px; height: 120px;">
                            <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="text-center mb-3"><?php echo htmlspecialchars($kurir['name']); ?></h4>
                    
                    <div class="mb-2">
                        <i class="bi bi-telephone-fill text-primary"></i>
                        <a href="tel:<?php echo $kurir['phone']; ?>"><?php echo htmlspecialchars($kurir['phone']); ?></a>
                    </div>
                    
                    <div class="mb-2">
                        <i class="bi bi-envelope-fill text-primary"></i>
                        <?php echo htmlspecialchars($kurir['email'] ?: '-'); ?>
                    </div>
                    
                    <div class="mb-2">
                        <i class="bi bi-<?php echo $kurir['vehicle_type'] === 'motor' ? 'bicycle' : ($kurir['vehicle_type'] === 'mobil' ? 'car-front' : 'bicycle'); ?> text-primary"></i>
                        <?php echo ucfirst($kurir['vehicle_type']); ?> - <?php echo htmlspecialchars($kurir['vehicle_number']); ?>
                    </div>
                    
                    <div class="mb-3">
                        <i class="bi bi-circle-fill <?php echo $kurir['status'] === 'available' ? 'text-success' : ($kurir['status'] === 'busy' ? 'text-warning' : 'text-secondary'); ?>"></i>
                        <span class="badge bg-<?php echo $kurir['status'] === 'available' ? 'success' : ($kurir['status'] === 'busy' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst($kurir['status']); ?>
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Rating:</span>
                        <strong>
                            <i class="bi bi-star-fill text-warning"></i> 
                            <?php echo number_format($kurir['rating'], 1); ?> / 5.0
                        </strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Deliveries:</span>
                        <strong><?php echo number_format($kurir['total_deliveries']); ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Status:</span>
                        <strong>
                            <?php if ($kurir['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Tidak Aktif</span>
                            <?php endif; ?>
                        </strong>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span>Bergabung:</span>
                        <strong><?php echo date('d M Y', strtotime($kurir['created_at'])); ?></strong>
                    </div>
                </div>

                <!-- Current Location -->
                <?php if ($location): ?>
                <div class="info-card">
                    <h6 class="mb-3"><i class="bi bi-geo-alt-fill"></i> Lokasi Saat Ini</h6>
                    <div id="mapView"></div>
                    <p class="text-muted small mt-2 mb-0">
                        <i class="bi bi-clock"></i> Update terakhir: 
                        <?php echo date('d M Y H:i', strtotime($location['updated_at'])); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistics & Orders -->
            <div class="col-lg-8">
                <!-- Statistics -->
                <div class="info-card">
                    <h5 class="mb-3"><i class="bi bi-graph-up"></i> Statistik</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo number_format($stats['total_completed']); ?></div>
                                <div class="stat-label">Total Selesai</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo formatCurrency($stats['total_revenue'] ?: 0); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo round($stats['avg_delivery_time'] ?: 0); ?></div>
                                <div class="stat-label">Avg Time (min)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-box">
                                <div class="stat-value"><?php echo $stats['today_deliveries']; ?></div>
                                <div class="stat-label">Hari Ini</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Orders -->
                <div class="info-card">
                    <h5 class="mb-3"><i class="bi bi-box-seam"></i> Pesanan Aktif (<?php echo count($activeOrders); ?>)</h5>
                    <?php if (count($activeOrders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo substr($order['order_number'], -8); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'confirmed' ? 'primary' : 
                                                ($order['status'] === 'processing' ? 'info' : 
                                                ($order['status'] === 'ready' ? 'warning' : 'success')); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatCurrency($order['final_amount']); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/orders/view.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-view">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-3 mb-0">Tidak ada pesanan aktif saat ini</p>
                    <?php endif; ?>
                </div>

                <!-- Delivery History -->
                <div class="info-card">
                    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Riwayat Pengantaran (20 Terakhir)</h5>
                    <?php if (count($deliveryHistory) > 0): ?>
                    <div class="timeline">
                        <?php foreach ($deliveryHistory as $history): ?>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>Order #<?php echo substr($history['order_number'], -8); ?></strong>
                                    <span class="badge bg-secondary ms-2"><?php echo ucfirst($history['status']); ?></span>
                                    <p class="mb-1 small text-muted">
                                        <?php echo htmlspecialchars($history['notes']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?php echo date('d M Y H:i', strtotime($history['created_at'])); ?>
                                    </small>
                                </div>
                                <strong class="text-success"><?php echo formatCurrency($history['final_amount']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-3 mb-0">Belum ada riwayat pengantaran</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php if ($location): ?>
<script>
// Initialize map
const map = L.map('mapView').setView([<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>], 15);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add kurir marker
const icon = L.divIcon({
    className: 'custom-icon',
    html: '<div style="background: #28a745; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3); border: 3px solid white;"><i class="bi bi-bicycle" style="font-size: 1.2rem;"></i></div>',
    iconSize: [40, 40]
});

L.marker([<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>], {icon: icon})
    .addTo(map)
    .bindPopup('<strong><?php echo htmlspecialchars($kurir['name']); ?></strong><br>Lokasi saat ini');
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
