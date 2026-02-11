<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Admin Dashboard';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$todayOrders = $stmt->fetchColumn();

$stmt = $db->query("SELECT SUM(final_amount) FROM orders 
                   WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
$todayRevenue = $stmt->fetchColumn() ?? 0;

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$totalCustomers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
$totalProducts = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM returns WHERE status = 'pending'");
$pendingReturns = $stmt->fetchColumn();

// Get recent orders
$stmt = $db->query("SELECT o.*, u.name as customer_name 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   ORDER BY o.created_at DESC 
                   LIMIT 10");
$recentOrders = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-speedometer2"></i> Dashboard</h1>
            <div class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="quick-stats">
            <div class="stat-card stat-orders">
                <div class="stat-icon">
                    <i class="bi bi-bag-check"></i>
                </div>
                <div class="stat-value"><?php echo $todayOrders; ?></div>
                <div class="stat-label">Pesanan Hari Ini</div>
            </div>
            
            <div class="stat-card stat-revenue">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($todayRevenue); ?></div>
                <div class="stat-label">Revenue Hari Ini</div>
            </div>
            
            <div class="stat-card stat-customers">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-value"><?php echo $totalCustomers; ?></div>
                <div class="stat-label">Total Pelanggan</div>
            </div>
            
            <div class="stat-card stat-products">
                <div class="stat-icon">
                    <i class="bi bi-cup-straw"></i>
                </div>
                <div class="stat-value"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Produk Aktif</div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="admin-table">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Grafik Penjualan (7 Hari Terakhir)</h5>
                    </div>
                    <div class="p-3">
                        <canvas id="salesChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="admin-table">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Status Order</h5>
                    </div>
                    <div class="p-3">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="admin-table">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="bi bi-cup-hot"></i> Top 5 Produk Terlaris</h5>
                    </div>
                    <div class="p-3">
                        <canvas id="productsChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="admin-table">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="bi bi-bicycle"></i> Performa Kurir</h5>
                    </div>
                    <div class="p-3">
                        <canvas id="kurirChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mb-4">
            <div class="col-lg-12">
                <div class="admin-table">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Customer Baru (6 Bulan Terakhir)</h5>
                    </div>
                    <div class="p-3">
                        <canvas id="customersChart" height="60"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if ($pendingOrders > 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Ada <strong><?php echo $pendingOrders; ?></strong> pesanan menunggu konfirmasi.
            <a href="<?php echo SITE_URL; ?>/admin/orders/" class="alert-link">Lihat Pesanan</a>
        </div>
        <?php endif; ?>
        
        <?php if ($pendingReturns > 0): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            Ada <strong><?php echo $pendingReturns; ?></strong> permintaan retur menunggu review.
            <a href="<?php echo SITE_URL; ?>/admin/returns/" class="alert-link">Lihat Retur</a>
        </div>
        <?php endif; ?>
        
        <!-- Recent Orders -->
        <div class="admin-table">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">Pesanan Terbaru</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo formatCurrency($order['final_amount']); ?></td>
                            <td>
                                <span class="badge status-<?php echo $order['status']; ?>">
                                    <?php echo ORDER_STATUS[$order['status']]; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/orders/view.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-view">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($recentOrders) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Belum ada pesanan
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/admin/orders/" class="btn btn-coffee">
                Lihat Semua Pesanan <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Chart colors
const colors = {
    primary: '#6F4E37',
    success: '#28a745',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#17a2b8',
    secondary: '#6c757d'
};

// Initialize all charts
document.addEventListener('DOMContentLoaded', function() {
    loadSalesChart();
    loadOrdersChart();
    loadProductsChart();
    loadKurirChart();
    loadCustomersChart();
});

// 1. Sales Chart (Line)
function loadSalesChart() {
    fetch('<?php echo SITE_URL; ?>/api/dashboard_stats.php?type=sales')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('salesChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [{
                        label: 'Revenue (Rp)',
                        data: data.map(d => d.revenue),
                        borderColor: colors.primary,
                        backgroundColor: colors.primary + '20',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + (value / 1000) + 'k';
                                }
                            }
                        }
                    }
                }
            });
        });
}

// 2. Orders Chart (Pie)
function loadOrdersChart() {
    fetch('<?php echo SITE_URL; ?>/api/dashboard_stats.php?type=orders')
        .then(res => res.json())
        .then(data => {
            const statusLabels = {
                'pending': 'Pending',
                'processing': 'Processing',
                'ready': 'Ready',
                'delivering': 'Delivering',
                'completed': 'Completed',
                'cancelled': 'Cancelled'
            };
            
            const statusColors = {
                'pending': colors.warning,
                'processing': colors.info,
                'ready': colors.primary,
                'delivering': '#fd7e14',
                'completed': colors.success,
                'cancelled': colors.danger
            };
            
            const ctx = document.getElementById('ordersChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => statusLabels[d.status] || d.status),
                    datasets: [{
                        data: data.map(d => d.count),
                        backgroundColor: data.map(d => statusColors[d.status] || colors.secondary)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
}

// 3. Products Chart (Horizontal Bar)
function loadProductsChart() {
    fetch('<?php echo SITE_URL; ?>/api/dashboard_stats.php?type=products')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('productsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.name),
                    datasets: [{
                        label: 'Terjual',
                        data: data.map(d => d.total_sold),
                        backgroundColor: colors.primary,
                        borderRadius: 5
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });
        });
}

// 4. Kurir Performance Chart (Bar)
function loadKurirChart() {
    fetch('<?php echo SITE_URL; ?>/api/dashboard_stats.php?type=kurir')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('kurirChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.name),
                    datasets: [{
                        label: 'Jumlah Delivery',
                        data: data.map(d => d.deliveries),
                        backgroundColor: colors.success,
                        borderRadius: 5
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: { beginAtZero: true }
                    }
                }
            });
        });
}

// 5. Customers Chart (Line)
function loadCustomersChart() {
    fetch('<?php echo SITE_URL; ?>/api/dashboard_stats.php?type=customers')
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('customersChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'Customer Baru',
                        data: data.map(d => d.count),
                        borderColor: colors.info,
                        backgroundColor: colors.info + '20',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
