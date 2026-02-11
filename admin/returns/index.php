<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Manage Returns';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Get returns
$stmt = $db->query("SELECT r.*, o.order_number, u.name as customer_name 
                   FROM returns r 
                   JOIN orders o ON r.order_id = o.id 
                   JOIN users u ON o.user_id = u.id 
                   ORDER BY r.created_at DESC");
$returns = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-arrow-return-left"></i> Return Requests</h1>
        </div>

        <div class="admin-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th class="hide-mobile">Customer</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="hide-mobile">Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($returns as $return): ?>
                        <tr>
                            <td data-label="Order #">
                                <strong><?php echo htmlspecialchars($return['order_number']); ?></strong>
                                <?php if ($return['auto_approved']): ?>
                                <br><small class="badge bg-info">Auto-approved</small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Customer" class="hide-mobile"><?php echo htmlspecialchars($return['customer_name']); ?></td>
                            <td data-label="Amount"><?php echo formatCurrency($return['refund_amount']); ?></td>
                            <td data-label="Reason">
                                <?php 
                                $reasons = [
                                    'wrong_order' => 'Wrong Order',
                                    'damaged' => 'Damaged',
                                    'quality_issue' => 'Quality Issue',
                                    'missing_items' => 'Missing Items',
                                    'other' => 'Other'
                                ];
                                echo $reasons[$return['reason']] ?? $return['reason']; 
                                ?>
                            </td>
                            <td data-label="Status">
                                <span class="badge bg-<?php echo $return['status'] == 'pending' ? 'warning' : ($return['status'] == 'approved' ? 'success' : 'danger'); ?>">
                                    <?php echo ucfirst($return['status']); ?>
                                </span>
                                <?php if ($return['status'] === 'approved' && $return['refund_processed']): ?>
                                <br><small class="badge bg-success">Processed</small>
                                <?php endif; ?>
                            </td>
                            <td data-label="Date" class="hide-mobile"><?php echo formatDate($return['created_at']); ?></td>
                            <td data-label="Actions">
                                <a href="view.php?id=<?php echo $return['id']; ?>" class="btn btn-sm btn-view">
                                    <i class="bi bi-eye"></i> <span>View</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($returns) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No return requests found
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
