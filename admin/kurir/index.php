<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Kurir';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Get all kurir
$stmt = $db->query("SELECT k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, k.vehicle_number,
                   k.status, k.rating, k.total_deliveries, k.is_active, k.created_at, k.updated_at,
                   COUNT(DISTINCT o.id) as active_deliveries
                   FROM kurir k
                   LEFT JOIN orders o ON k.id = o.kurir_id AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                   GROUP BY k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, k.vehicle_number,
                            k.status, k.rating, k.total_deliveries, k.is_active, k.created_at, k.updated_at
                   ORDER BY k.is_active DESC, k.status ASC, k.name ASC");
$kurirs = $stmt->fetchAll();

// Get statistics
$stmtStats = $db->query("SELECT 
    COUNT(*) as total_kurir,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_kurir,
    SUM(CASE WHEN status = 'busy' THEN 1 ELSE 0 END) as busy_kurir,
    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_kurir
    FROM kurir WHERE is_active = 1");
$stats = $stmtStats->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-bicycle"></i> Kurir Management</h1>
            <a href="create.php" class="btn btn-coffee">
                <i class="bi bi-plus-circle"></i> Tambah Kurir
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
                if ($_GET['success'] === 'created') echo 'Kurir berhasil ditambahkan!';
                elseif ($_GET['success'] === 'updated') echo 'Data kurir berhasil diperbarui!';
                elseif ($_GET['success'] === 'deleted') echo 'Kurir berhasil dinonaktifkan!';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
                if ($_GET['error'] === 'not_found') echo 'Kurir tidak ditemukan!';
                else echo 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['total_kurir']; ?></h3>
                        <small class="text-muted">Total Kurir</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['available_kurir']; ?></h3>
                        <small class="text-muted">Available</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-hourglass-split text-warning" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['busy_kurir']; ?></h3>
                        <small class="text-muted">Busy</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size: 2rem;"></i>
                        <h3 class="mt-2 mb-0"><?php echo $stats['offline_kurir']; ?></h3>
                        <small class="text-muted">Offline</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Kurir</th>
                            <th class="hide-mobile">Phone</th>
                            <th class="hide-mobile">Vehicle</th>
                            <th>Status</th>
                            <th class="hide-mobile">Rating</th>
                            <th class="hide-mobile">Total Deliveries</th>
                            <th>Active Orders</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kurirs as $kurir): ?>
                        <tr class="<?php echo !$kurir['is_active'] ? 'table-secondary' : ''; ?>">
                            <td data-label="ID"><?php echo $kurir['id']; ?></td>
                            <td data-label="Nama">
                                <strong><?php echo htmlspecialchars($kurir['name']); ?></strong>
                                <?php if (!$kurir['is_active']): ?>
                                <br><small class="badge bg-secondary">Inactive</small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Phone" class="hide-mobile">
                                <a href="tel:<?php echo $kurir['phone']; ?>"><?php echo $kurir['phone']; ?></a>
                            </td>
                            <td data-label="Vehicle" class="hide-mobile">
                                <i class="bi bi-<?php echo $kurir['vehicle_type'] === 'motor' ? 'bicycle' : ($kurir['vehicle_type'] === 'mobil' ? 'car-front' : 'bicycle'); ?>"></i>
                                <?php echo ucfirst($kurir['vehicle_type']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($kurir['vehicle_number']); ?></small>
                            </td>
                            <td data-label="Status">
                                <span class="badge bg-<?php 
                                    echo $kurir['status'] === 'available' ? 'success' : 
                                        ($kurir['status'] === 'busy' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($kurir['status']); ?>
                                </span>
                            </td>
                            <td data-label="Rating" class="hide-mobile">
                                <i class="bi bi-star-fill text-warning"></i> <?php echo number_format($kurir['rating'], 1); ?>
                            </td>
                            <td data-label="Deliveries" class="hide-mobile">
                                <?php echo number_format($kurir['total_deliveries']); ?>
                            </td>
                            <td data-label="Active">
                                <?php if ($kurir['active_deliveries'] > 0): ?>
                                    <span class="badge bg-info"><?php echo $kurir['active_deliveries']; ?> orders</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <a href="edit.php?id=<?php echo $kurir['id']; ?>" class="btn btn-sm btn-edit me-1">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="view.php?id=<?php echo $kurir['id']; ?>" class="btn btn-sm btn-view">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($kurirs) == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Belum ada kurir terdaftar
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
