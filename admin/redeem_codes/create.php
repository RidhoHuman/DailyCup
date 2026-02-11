<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Generate Redeem Codes';
$isAdminPage = true;
requireAdmin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = intval($_POST['amount'] ?? 0);
    $points = intval($_POST['points'] ?? 0);
    $count = intval($_POST['count'] ?? 1);

    if ($points <= 0 || $count <= 0) {
        $error = 'Please enter valid points and count';
    } else {
        try {
            $db->beginTransaction();
            for ($i = 0; $i < $count; $i++) {
                // Generate random unique code
                $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
                
                $stmt = $db->prepare("INSERT INTO redeem_codes (code, points, is_used, created_at) VALUES (?, ?, 0, NOW())");
                $stmt->execute([$code, $points]);
            }
            $db->commit();
            $success = $count . " codes generated successfully!";
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error generating codes: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-plus-circle"></i> Generate Redeem Codes</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Points Value</label>
                            <div class="input-group">
                                <input type="number" name="points" class="form-control" placeholder="e.g. 100" required>
                                <span class="input-group-text">Points</span>
                            </div>
                            <small class="text-muted">How many points will the user get?</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Number of Codes to Generate</label>
                            <input type="number" name="count" class="form-control" value="1" min="1" max="100" required>
                            <small class="text-muted">Max 100 codes at once</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-coffee px-4">
                            <i class="bi bi-gear"></i> Generate Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
