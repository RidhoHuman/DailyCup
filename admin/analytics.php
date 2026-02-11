<?php
/**
 * Advanced Analytics Dashboard
 * Phase 3: Enhanced Functionality
 */

require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Enterprise Analytics';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../includes/header.php';

// Fetch analytics data using Phase 3 functions
$monthlySales = getSalesAnalytics('monthly', 6);
$topProducts = getProductPerformance(5);
$segmentation = getCustomerSegmentation();
$clv = getCustomerLifetimeValue();
$lowStock = getLowStockAlerts(10);
$allTimeStats = getSalesStats('all_time');
$dormantCount = getDormantCustomersCount(30);

?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-graph-up-arrow text-primary"></i> Enterprise Analytics</h1>
                <p class="text-muted">Comprehensive business performance metrics and customer insights.</p>
            </div>
            <div class="text-end">
                <div class="h4 mb-0 text-success"><?php echo formatCurrency($allTimeStats['total_revenue']); ?></div>
                <div class="small text-muted">All-Time Revenue</div>
            </div>
        </div>

        <!-- Top Overview Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Customer Lifetime Value</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($clv); ?></h3>
                        <small>Avg. revenue per customer</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Loyal Customers</h6>
                        <?php 
                            $loyalCount = 0;
                            foreach($segmentation as $s) if($s['segment'] == 'VIP' || $s['segment'] == 'Loyal') $loyalCount += $s['count'];
                        ?>
                        <h3 class="mb-0"><?php echo $loyalCount; ?></h3>
                        <small>VIP & Loyal segments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Total Transactions</h6>
                        <h3 class="mb-0"><?php echo $allTimeStats['total_orders']; ?></h3>
                        <small>Completed orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Avg. Order Value</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($allTimeStats['avg_order_value']); ?></h3>
                        <small>Gross average</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-secondary text-white">
                    <div class="card-body">
                        <h6 class="card-title opacity-75">Dormant Customers</h6>
                        <h3 class="mb-0"><?php echo $dormantCount; ?></h3>
                        <small>No orders in 30 days</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sales Trends Chart -->
            <div class="col-lg-8">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <h5 class="card-title mb-0">Revenue & Order Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueTrendChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>

            <!-- Inventory Alerts -->
            <div class="col-lg-4">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-header bg-white border-bottom-0 py-3 d-flex justify-content-between">
                        <h5 class="card-title mb-0">Stock Alerts</h5>
                        <span class="badge bg-danger rounded-pill"><?php echo count($lowStock); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($lowStock)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-check-circle h1 text-success"></i>
                                <p>All items in stock</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($lowStock as $item): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <small class="text-muted">Product ID: #<?php echo $item['id']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-danger fw-bold"><?php echo $item['stock']; ?></div>
                                            <small class="text-muted">Remaining</small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="p-3 text-center border-top">
                                <a href="products/index.php" class="btn btn-sm btn-outline-primary">Manage Inventory</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Segmentation -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Customer Segmentation</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Segment</th>
                                        <th class="text-center">Size</th>
                                        <th class="text-end">Avg. Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($segmentation as $seg): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle me-2" style="width: 12px; height: 12px; background-color: <?php 
                                                        echo $seg['segment'] == 'VIP' ? '#ffc107' : 
                                                            ($seg['segment'] == 'Loyal' ? '#0d6efd' : 
                                                            ($seg['segment'] == 'Occasional' ? '#198754' : '#6c757d')); 
                                                    ?>"></div>
                                                    <span class="fw-medium"><?php echo $seg['segment']; ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center"><?php echo $seg['count']; ?></td>
                                            <td class="text-end fw-bold"><?php echo formatCurrency($seg['avg_spent']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0">Top Performing Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Sold</th>
                                        <th class="text-end">Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProducts as $p): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-medium"><?php echo htmlspecialchars($p['product_name']); ?></div>
                                                <small class="text-muted"><?php echo $p['unique_customers']; ?> unique buyers</small>
                                            </td>
                                            <td class="text-center"><?php echo $p['total_sold']; ?></td>
                                            <td class="text-end fw-bold text-success"><?php echo formatCurrency($p['total_revenue']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueTrendChart').getContext('2d');
    const chartData = <?php echo json_encode($monthlySales); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.label),
            datasets: [{
                label: 'Revenue (Rp)',
                data: chartData.map(d => d.revenue),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Orders',
                data: chartData.map(d => d.orders),
                borderColor: '#198754',
                borderWidth: 2,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Revenue'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    title: {
                        display: true,
                        text: 'Order Count'
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
