<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Performance & Monitoring';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// 1. Get Table Stats
$tables = [];
$stmt = $db->query("SHOW TABLE STATUS");
while ($row = $stmt->fetch()) {
    $tables[] = [
        'name' => $row['Name'],
        'rows' => $row['Rows'],
        'data_length' => round($row['Data_length'] / 1024 / 1024, 2), // MB
        'index_length' => round($row['Index_length'] / 1024 / 1024, 2), // MB
        'engine' => $row['Engine']
    ];
}

// 2. Get Cache Stats
$cacheFiles = glob(__DIR__ . '/../cache/*.json');
$cacheSize = 0;
foreach ($cacheFiles as $file) {
    $cacheSize += filesize($file);
}
$cacheSizeMB = round($cacheSize / 1024 / 1024, 2);

// 3. Clear Cache Logic
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache' && verifyCSRFToken($_GET['csrf_token'] ?? '')) {
    clearAllCache();
    header('Location: performance.php?success=cache_cleared');
    exit;
}

// 4. Optimize Tables Logic
if (isset($_GET['action']) && $_GET['action'] === 'optimize_db' && verifyCSRFToken($_GET['csrf_token'] ?? '')) {
    try {
        $optimized = 0;
        $errors = [];
        
        foreach ($tables as $t) {
            try {
                // ANALYZE TABLE - returns result set, use query()
                $stmt = $db->query("ANALYZE TABLE `" . $t['name'] . "`");
                $stmt->fetchAll(); // Consume result
                
                // OPTIMIZE TABLE - returns result set, use query()
                $stmt = $db->query("OPTIMIZE TABLE `" . $t['name'] . "`");
                $result = $stmt->fetch();
                
                // Check if optimization was successful
                if ($result && isset($result['Msg_text'])) {
                    if (stripos($result['Msg_text'], 'OK') !== false || 
                        stripos($result['Msg_text'], 'Table is already up to date') !== false) {
                        $optimized++;
                    } else {
                        $errors[] = $t['name'] . ': ' . $result['Msg_text'];
                    }
                } else {
                    $optimized++;
                }
            } catch (Exception $e) {
                $errors[] = $t['name'] . ': ' . $e->getMessage();
                error_log("Optimize table {$t['name']} error: " . $e->getMessage());
            }
        }
        
        if (count($errors) > 0) {
            error_log("Optimize DB completed with errors: " . implode('; ', $errors));
            header('Location: performance.php?success=db_optimized_partial&optimized=' . $optimized);
        } else {
            header('Location: performance.php?success=db_optimized&optimized=' . $optimized);
        }
        exit;
        
    } catch (PDOException $e) {
        error_log("Optimize DB Fatal Error: " . $e->getMessage());
        header('Location: performance.php?error=optimize_failed&detail=' . urlencode($e->getMessage()));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title"><i class="bi bi-speedometer2"></i> Sistem Performance & Scale</h1>
            <div class="header-actions">
                <a href="?action=clear_cache&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn btn-outline-warning btn-sm" onclick="return confirm('Hapus semua cache?')">
                    <i class="bi bi-trash"></i> Bersihkan Cache
                </a>
                <a href="?action=optimize_db&csrf_token=<?php echo generateCSRFToken(); ?>" class="btn btn-outline-success btn-sm" onclick="return confirm('Optimasi semua tabel database?')">
                    <i class="bi bi-gear"></i> Optimasi Database
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    if ($_GET['success'] === 'cache_cleared') {
                        echo '✅ Cache berhasil dibersihkan.';
                    } elseif ($_GET['success'] === 'db_optimized') {
                        $count = $_GET['optimized'] ?? 'Semua';
                        echo "✅ Database berhasil dioptimasi! {$count} tabel telah dioptimasi.";
                    } elseif ($_GET['success'] === 'db_optimized_partial') {
                        $count = $_GET['optimized'] ?? 'Beberapa';
                        echo "⚠️ Database dioptimasi dengan beberapa warning. {$count} tabel berhasil dioptimasi. Cek error log untuk detail.";
                    }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>❌ Gagal mengoptimasi database.</strong>
                <?php if (isset($_GET['detail'])): ?>
                    <br><small>Detail: <?php echo htmlspecialchars($_GET['detail']); ?></small>
                <?php endif; ?>
                <br><small class="text-muted">
                    Kemungkinan penyebab: Permissions database tidak cukup, atau tabel sedang digunakan. 
                    Coba lagi nanti atau hubungi administrator server.
                </small>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Cache Overview -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 text-coffee mb-2"><i class="bi bi-memory"></i></div>
                        <h5 class="fw-bold">Cache Status</h5>
                        <p class="h2 mb-0"><?php echo count($cacheFiles); ?></p>
                        <p class="text-muted">File Ter-cache (<?php echo $cacheSizeMB; ?> MB)</p>
                    </div>
                </div>
            </div>

            <!-- Server Health -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body p-4">
                        <div class="display-4 text-primary mb-2"><i class="bi bi-hdd-network"></i></div>
                        <h5 class="fw-bold">Server PHP</h5>
                        <p class="h5 mb-0"><?php echo phpversion(); ?></p>
                        <p class="text-muted">Maks. Upload: <?php echo ini_get('upload_max_filesize'); ?></p>
                    </div>
                </div>
            </div>

            <!-- DB Health -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center">
                    <div class="card-body p-4">
                        <div class="display-4 text-info mb-2"><i class="bi bi-database"></i></div>
                        <h5 class="fw-bold">Database</h5>
                        <p class="h5 mb-0"><?php echo count($tables); ?> Tabel</p>
                        <p class="text-muted">Engine: InnoDB</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">Database Table Statistics</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Nama Tabel</th>
                            <th>Jumlah Baris</th>
                            <th>Ukuran Data</th>
                            <th>Ukuran Index</th>
                            <th>Total Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $t): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $t['name']; ?></td>
                            <td><?php echo number_format($t['rows']); ?></td>
                            <td><?php echo $t['data_length']; ?> MB</td>
                            <td><?php echo $t['index_length']; ?> MB</td>
                            <td><?php echo $t['data_length'] + $t['index_length']; ?> MB</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
