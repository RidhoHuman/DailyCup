<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Orders';
$isAdminPage = true;
requireAdmin();

require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Filter
$status = $_GET['status'] ?? '';

$sql = "SELECT o.*, u.name as customer_name FROM orders o 
        JOIN users u ON o.user_id = u.id";

if ($status) {
    $sql .= " WHERE o.status = ?";
    $params = [$status];
} else {
    $params = [];
}

$sql .= " ORDER BY o.created_at DESC LIMIT 50";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-bag"></i> Orders</h1>
        </div>
        
        <!-- Filter -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <?php foreach (ORDER_STATUS as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-coffee">Filter</button>
                    <a href="<?php echo SITE_URL; ?>/admin/orders/" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        
        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th class="hide-mobile">Customer</th>
                            <th>Total</th>
                            <th class="hide-mobile">Method</th>
                            <th>Status</th>
                            <th class="hide-mobile">Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Order #">
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </td>
                            <td data-label="Customer" class="hide-mobile"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td data-label="Total"><?php echo formatCurrency($order['final_amount']); ?></td>
                            <td data-label="Method" class="hide-mobile"><?php echo ucfirst($order['delivery_method']); ?></td>
                            <td data-label="Status">
                                <span class="badge status-<?php echo $order['status']; ?>">
                                    <?php echo ORDER_STATUS[$order['status']]; ?>
                                </span>
                            </td>
                            <td data-label="Date" class="hide-mobile"><?php echo formatDate($order['created_at'], 'd M Y H:i'); ?></td>
                            <td data-label="Actions">
                                <a href="<?php echo SITE_URL; ?>/admin/orders/view.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-view">
                                    <i class="bi bi-eye"></i> <span>View Detail</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($orders) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No orders found
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
