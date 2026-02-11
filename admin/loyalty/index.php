<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Loyalty Settings';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $points_per_rupiah = $_POST['points_per_rupiah'];
    $min_redeem_points = $_POST['min_redeem_points'];
    $rupiah_per_point = $_POST['rupiah_per_point'];

    try {
        $stmt = $db->prepare("UPDATE loyalty_settings SET points_per_rupiah = ?, min_points_redeem = ?, rupiah_per_point = ? WHERE id = 1");
        $stmt->execute([$points_per_rupiah, $min_redeem_points, $rupiah_per_point]);
        $message = '<div class="alert alert-success">Settings updated successfully!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Error updating settings: ' . $e->getMessage() . '</div>';
    }
}

// Get current settings
try {
    $stmt = $db->query("SELECT * FROM loyalty_settings LIMIT 1");
    $settings = $stmt->fetch();
    
    if (!$settings) {
        $settings = [
            'points_per_rupiah' => 0.01,
            'min_points_redeem' => 100,
            'rupiah_per_point' => 100
        ];
    }
} catch (PDOException $e) {
    $settings = [
        'points_per_rupiah' => 0.01,
        'min_points_redeem' => 100,
        'rupiah_per_point' => 100
    ];
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-gem"></i> Loyalty Settings</h1>
        </div>

        <?php echo $message; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Points per Rupiah Spent</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" name="points_per_rupiah" class="form-control" value="<?php echo $settings['points_per_rupiah']; ?>" required>
                                <span class="input-group-text">Pts / Rp</span>
                            </div>
                            <small class="text-muted">Example: 0.01 means Rp 10.000 = 100 Points</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Minimum Points to Redeem</label>
                            <div class="input-group">
                                <input type="number" name="min_redeem_points" class="form-control" value="<?php echo $settings['min_points_redeem']; ?>" required>
                                <span class="input-group-text">Points</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Point Value (in Rupiah)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="rupiah_per_point" class="form-control" value="<?php echo $settings['rupiah_per_point']; ?>" required>
                                <span class="input-group-text">per 1 Point</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-coffee px-4">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
