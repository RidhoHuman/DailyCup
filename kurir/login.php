<?php
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['kurir_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($phone) && !empty($password)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM kurir WHERE phone = ? AND is_active = 1");
        $stmt->execute([$phone]);
        $kurir = $stmt->fetch();
        
        if ($kurir && password_verify($password, $kurir['password'])) {
            $_SESSION['kurir_id'] = $kurir['id'];
            $_SESSION['kurir_name'] = $kurir['name'];
            
            // Update status to available
            $stmt = $db->prepare("UPDATE kurir SET status = 'available' WHERE id = ?");
            $stmt->execute([$kurir['id']]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Nomor telepon atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurir Login - DailyCup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #6F4E37 0%, #8B4513 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 4rem;
            color: #6F4E37;
        }
        .btn-login {
            background: #6F4E37;
            color: white;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            background: #5a3d2a;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <i class="bi bi-bicycle"></i>
            <h3 class="mt-2">Kurir Login</h3>
            <p class="text-muted">DailyCup Coffee</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error']) && $_GET['error'] === 'inactive'): ?>
        <div class="alert alert-warning">
            Akun Anda tidak aktif. Hubungi admin.
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Nomor Telepon</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>
        </form>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                Lupa password? Hubungi admin<br>
                <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">← Kembali ke Website</a>
            </small>
        </div>
    </div>
</body>
</html>