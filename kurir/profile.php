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

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (!empty($name) && !empty($email) && !empty($phone)) {
        $stmt = $db->prepare("UPDATE kurir SET name = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $phone, $kurirId])) {
            $success = 'Profil berhasil diperbarui!';
            $_SESSION['kurir_name'] = $name;
            // Refresh kurir data
            $stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
            $stmt->execute([$kurirId]);
            $kurir = $stmt->fetch();
        } else {
            $error = 'Gagal memperbarui profil!';
        }
    } else {
        $error = 'Semua field harus diisi!';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (password_verify($currentPassword, $kurir['password'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 6) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE kurir SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $kurirId])) {
                    $success = 'Password berhasil diubah!';
                } else {
                    $error = 'Gagal mengubah password!';
                }
            } else {
                $error = 'Password baru minimal 6 karakter!';
            }
        } else {
            $error = 'Password baru tidak cocok!';
        }
    } else {
        $error = 'Password lama salah!';
    }
}

// Get statistics
$stmt = $db->prepare("SELECT 
                        COUNT(*) as total_deliveries,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        AVG(CASE WHEN status = 'completed' THEN TIMESTAMPDIFF(MINUTE, pickup_time, delivery_time) ELSE NULL END) as avg_delivery_time
                      FROM orders WHERE kurir_id = ?");
$stmt->execute([$kurirId]);
$stats = $stmt->fetch();

// Get this month earnings
$stmt = $db->prepare("SELECT SUM(final_amount) FROM orders 
                     WHERE kurir_id = ? 
                     AND status = 'completed' 
                     AND MONTH(updated_at) = MONTH(CURDATE())
                     AND YEAR(updated_at) = YEAR(CURDATE())");
$stmt->execute([$kurirId]);
$monthlyEarnings = $stmt->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Kurir - DailyCup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --success-color: #28a745;
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
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .profile-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        
        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 15px;
        }
        
        .profile-photo img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
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
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-available { background: var(--success-color); color: white; }
        .status-busy { background: #ffc107; color: #000; }
        .status-offline { background: #dc3545; color: white; }
        
        .info-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
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
        
        .section-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: #5a3d2a;
            color: white;
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
                        <i class="bi bi-person"></i> Profil Saya
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
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-photo">
                    <?php if ($kurir['photo']): ?>
                        <img src="<?php echo SITE_URL . '/assets/images/kurir/' . $kurir['photo']; ?>" alt="<?php echo htmlspecialchars($kurir['name']); ?>">
                    <?php else: ?>
                        <i class="bi bi-person"></i>
                    <?php endif; ?>
                </div>
                <h5 class="mb-1"><?php echo htmlspecialchars($kurir['name']); ?></h5>
                <span class="status-badge status-<?php echo $kurir['status']; ?>">
                    <i class="bi bi-circle-fill"></i>
                    <?php 
                    $statusLabels = [
                        'available' => 'Tersedia',
                        'busy' => 'Sibuk',
                        'offline' => 'Offline'
                    ];
                    echo $statusLabels[$kurir['status']] ?? $kurir['status'];
                    ?>
                </span>
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-star-fill text-warning"></i> <?php echo number_format($kurir['rating'], 1); ?> Rating
                    </small>
                </div>
            </div>

            <!-- Statistics -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($stats['total_deliveries']); ?></div>
                        <div class="stat-label">Total Pengiriman</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-value text-success"><?php echo number_format($stats['completed']); ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-value text-primary" style="font-size: 1rem;">
                            <?php echo $stats['avg_delivery_time'] ? number_format($stats['avg_delivery_time']) . ' min' : '-'; ?>
                        </div>
                        <div class="stat-label">Rata-rata Waktu</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-value text-warning" style="font-size: 0.9rem;">
                            Rp <?php echo number_format($monthlyEarnings / 1000); ?>k
                        </div>
                        <div class="stat-label">Bulan Ini</div>
                    </div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="section-title">Informasi Pribadi</div>
            <div class="info-item">
                <div class="info-label"><i class="bi bi-telephone"></i> Nomor Telepon</div>
                <div class="info-value"><?php echo htmlspecialchars($kurir['phone']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="bi bi-envelope"></i> Email</div>
                <div class="info-value"><?php echo htmlspecialchars($kurir['email']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="bi bi-bicycle"></i> Kendaraan</div>
                <div class="info-value">
                    <?php echo ucfirst($kurir['vehicle_type']); ?>
                    <?php if ($kurir['vehicle_number']): ?>
                        - <?php echo htmlspecialchars($kurir['vehicle_number']); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="bi bi-calendar"></i> Bergabung Sejak</div>
                <div class="info-value"><?php echo date('d F Y', strtotime($kurir['created_at'])); ?></div>
            </div>
        </div>

        <!-- Edit Profile -->
        <div class="profile-card">
            <div class="section-title">Edit Profil</div>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($kurir['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($kurir['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($kurir['phone']); ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary-custom w-100">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="profile-card">
            <div class="section-title">Ubah Password</div>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Password Lama</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="new_password" class="form-control" required>
                    <small class="text-muted">Minimal 6 karakter</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary-custom w-100">
                    <i class="bi bi-key"></i> Ubah Password
                </button>
            </form>
        </div>
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
                    <a href="history.php" class="nav-link">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat</span>
                    </a>
                </div>
                <div class="col">
                    <a href="profile.php" class="nav-link active">
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
