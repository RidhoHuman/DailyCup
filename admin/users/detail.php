<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 for production, log errors instead
ini_set('log_errors', 1);

require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'User Detail';
$isAdminPage = true;

try {
    requireAdmin();
    
    $db = getDB();
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($userId <= 0) {
        throw new Exception('Invalid user ID');
    }
} catch (Exception $e) {
    error_log("Error in detail.php: " . $e->getMessage());
    die("Error: Unable to load user details. Please check server logs.");
}

// Get user details
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['error'] = 'User tidak ditemukan!';
        header('Location: ' . SITE_URL . '/admin/users/index.php');
        exit;
    }
    
    // Get user statistics
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalOrders = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedOrders = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT SUM(final_amount) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetchColumn() ?? 0;
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalReviews = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalTickets = $stmt->fetchColumn();
    
    // Get recent orders
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in detail.php: " . $e->getMessage());
    die("Database error. Please contact administrator.");
}


// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['is_active'];
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);
    
    $_SESSION['success'] = 'Status user berhasil diupdate!';
    header('Location: ' . SITE_URL . '/admin/users/detail.php?id=' . $userId);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title"><i class="bi bi-person"></i> User Detail</h1>
                <a href="<?php echo SITE_URL; ?>/admin/users/index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-4">
                <!-- User Info Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Informasi User</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle" style="font-size: 80px; color: #6F4E37;"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($user['name']); ?></h5>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                        <?php if ($user['phone']): ?>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($user['phone']); ?></p>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-around mb-3">
                            <div>
                                <h6 class="mb-0"><?php echo $user['loyalty_points']; ?></h6>
                                <small class="text-muted">Loyalty Points</small>
                            </div>
                            <div>
                                <h6 class="mb-0">
                                    <?php 
                                    $roles = ['customer' => 'Customer', 'admin' => 'Admin', 'kurir' => 'Kurir'];
                                    echo $roles[$user['role']] ?? ucfirst($user['role']); 
                                    ?>
                                </h6>
                                <small class="text-muted">Role</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Status: </strong>
                            <?php if ($user['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="small text-muted mb-0">
                            <strong>Bergabung:</strong><br>
                            <?php echo formatDate($user['created_at']); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Account Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user['email']): ?>
                        <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" 
                           class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-envelope"></i> Send Email
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($user['phone']): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user['phone']); ?>" 
                           target="_blank" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-whatsapp"></i> Chat WhatsApp
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($user['role'] == 'customer'): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/cs/chat_detail.php?user_id=<?php echo $user['id']; ?>" 
                           class="btn btn-info w-100 mb-2">
                            <i class="bi bi-chat-dots"></i> Live Chat
                        </a>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Status Account:</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?php echo $user['is_active'] ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?php echo !$user['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-warning w-100">
                                <i class="bi bi-pencil"></i> Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <!-- Statistics -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo $totalOrders; ?></h3>
                                <small class="text-muted">Total Orders</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success"><?php echo $completedOrders; ?></h3>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning"><?php echo formatCurrency($totalSpent); ?></h3>
                                <small class="text-muted">Total Spent</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info"><?php echo $totalReviews; ?></h3>
                                <small class="text-muted">Reviews</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Details -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Detail Informasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Nama Lengkap:</strong>
                                <p><?php echo htmlspecialchars($user['name']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Email:</strong>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Phone:</strong>
                                <p><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Role:</strong>
                                <p><?php echo ucfirst($user['role']); ?></p>
                            </div>
                            <?php if ($user['address']): ?>
                            <div class="col-12 mb-3">
                                <strong>Alamat:</strong>
                                <p><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-6 mb-3">
                                <strong>Loyalty Points:</strong>
                                <p><?php echo number_format($user['loyalty_points']); ?> points</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Bergabung:</strong>
                                <p><?php echo formatDate($user['created_at']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Last Updated:</strong>
                                <p><?php echo formatDate($user['updated_at']); ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Status:</strong>
                                <p>
                                    <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Pesanan Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentOrders)): ?>
                        <p class="text-muted">Belum ada pesanan</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo formatCurrency($order['final_amount']); ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'processing' => 'primary',
                                                'delivering' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $statusColors[$order['status']] ?? 'secondary'; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/admin/orders/detail.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($totalOrders > 5): ?>
                        <div class="text-center mt-3">
                            <a href="<?php echo SITE_URL; ?>/admin/orders/index.php?user_id=<?php echo $user['id']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                Lihat Semua Pesanan
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Support Tickets -->
                <?php if ($totalTickets > 0): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Support Tickets (<?php echo $totalTickets; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <a href="<?php echo SITE_URL; ?>/admin/cs/tickets.php?user_id=<?php echo $user['id']; ?>" 
                           class="btn btn-sm btn-outline-info">
                            <i class="bi bi-ticket-detailed"></i> Lihat Semua Tickets
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
