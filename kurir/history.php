<?php
require_once __DIR__ . '/../includes/functions.php';

// Kurir login check
if (!isset($_SESSION['kurir_id'])) {
    header('Location: ' . SITE_URL . '/kurir/login.php');
    exit;
}

$db = getDB();
$kurirId = $_SESSION['kurir_id'];

// Get kurir info
$stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
$stmt->execute([$kurirId]);
$kurir = $stmt->fetch();

if (!$kurir || !$kurir['is_active']) {
    session_destroy();
    header('Location: ' . SITE_URL . '/kurir/login.php?error=inactive');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$whereClause = "o.kurir_id = ?";
$params = [$kurirId];

if ($filter !== 'all') {
    $whereClause .= " AND o.status = ?";
    $params[] = $filter;
}

if ($dateFrom) {
    $whereClause .= " AND DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $whereClause .= " AND DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE $whereClause");
$stmt->execute($params);
$totalOrders = $stmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$params[] = $offset;
$params[] = $perPage;
$stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.address as customer_address
                     FROM orders o
                     JOIN users u ON o.user_id = u.id
                     WHERE $whereClause
                     ORDER BY o.created_at DESC
                     LIMIT ?, ?");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$stmt = $db->prepare("SELECT 
                        COUNT(*) as total_deliveries,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as total_revenue
                      FROM orders WHERE kurir_id = ?");
$stmt->execute([$kurirId]);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pengiriman - DailyCup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            background: #f8f9fa;
            padding-bottom: 80px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .kurir-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8B4513 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.85rem;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .order-number {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-completed { background: var(--success-color); color: white; }
        .status-cancelled { background: var(--danger-color); color: white; }
        .status-pending { background: #6c757d; color: white; }
        .status-confirmed { background: #17a2b8; color: white; }
        .status-processing { background: #007bff; color: white; }
        .status-ready { background: #fd7e14; color: white; }
        .status-delivering { background: #ffc107; color: #000; }
        
        .customer-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 0.9rem;
        }
        
        .order-details {
            font-size: 0.85rem;
            color: #666;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            padding: 10px 0;
            z-index: 1000;
        }
        
        .nav-link {
            text-align: center;
            color: #666;
            text-decoration: none;
            padding: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.75rem;
        }
        
        .nav-link i {
            font-size: 1.5rem;
            margin-bottom: 3px;
        }
        
        .nav-link.active {
            color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .pagination {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="kurir-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Riwayat Pengiriman
                    </h5>
                    <small><?php echo $kurir['name']; ?></small>
                </div>
                <a href="logout.php" class="btn btn-sm btn-light">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container mt-3">
        <!-- Statistics -->
        <div class="row">
            <div class="col-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['total_deliveries']); ?></div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-card">
                    <div class="stat-value text-success"><?php echo number_format($stats['completed']); ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-card">
                    <div class="stat-value text-danger"><?php echo number_format($stats['cancelled']); ?></div>
                    <div class="stat-label">Dibatalkan</div>
                </div>
            </div>
            <div class="col-3">
                <div class="stat-card">
                    <div class="stat-value text-primary" style="font-size: 1rem;">Rp <?php echo number_format($stats['total_revenue']); ?></div>
                    <div class="stat-label">Pendapatan</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-card">
            <form method="GET" action="">
                <div class="row g-2">
                    <div class="col-12 col-md-3">
                        <select name="filter" class="form-select form-select-sm">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            <option value="delivering" <?php echo $filter === 'delivering' ? 'selected' : ''; ?>>Sedang Dikirim</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $dateFrom; ?>" placeholder="Dari Tanggal">
                    </div>
                    <div class="col-6 col-md-3">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $dateTo; ?>" placeholder="Sampai Tanggal">
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>Belum Ada Riwayat</h5>
                <p class="text-muted">Riwayat pengiriman akan muncul di sini</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-number">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php 
                            $statusLabels = [
                                'pending' => 'Menunggu',
                                'confirmed' => 'Dikonfirmasi',
                                'processing' => 'Diproses',
                                'ready' => 'Siap',
                                'delivering' => 'Dikirim',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan'
                            ];
                            echo $statusLabels[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </div>

                    <div class="customer-info">
                        <div><i class="bi bi-person"></i> <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                        <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                        <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($order['customer_address']); ?></div>
                    </div>

                    <div class="order-details">
                        <div class="row">
                            <div class="col-6">
                                <i class="bi bi-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                            <div class="col-6 text-end">
                                <strong>Rp <?php echo number_format($order['final_amount']); ?></strong>
                            </div>
                        </div>
                        <?php if ($order['delivery_type'] === 'delivery'): ?>
                            <div class="mt-2">
                                <i class="bi bi-truck"></i> Pengiriman
                                <?php if ($order['pickup_time']): ?>
                                    | Pickup: <?php echo date('H:i', strtotime($order['pickup_time'])); ?>
                                <?php endif; ?>
                                <?php if ($order['delivery_time']): ?>
                                    | Selesai: <?php echo date('H:i', strtotime($order['delivery_time'])); ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-2">
                                <i class="bi bi-shop"></i> Ambil di Toko
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&filter=<?php echo $filter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&filter=<?php echo $filter; ?>&date_from=<?php echo $dateFrom; ?>&date_to=<?php echo $dateTo; ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <div class="container">
            <div class="row g-0">
                <div class="col">
                    <a href="index.php" class="nav-link">
                        <i class="bi bi-house-door"></i>
                        <span>Home</span>
                    </a>
                </div>
                <div class="col">
                    <a href="history.php" class="nav-link active">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat</span>
                    </a>
                </div>
                <div class="col">
                    <a href="profile.php" class="nav-link">
                        <i class="bi bi-person"></i>
                        <span>Profil</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
