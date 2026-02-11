<?php
require_once __DIR__ . '/../includes/functions.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php endif; ?>
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    
    <script>
        window.SITE_URL_JS = "<?php echo SITE_URL; ?>";
        window.IS_LOGGED_IN = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    </script>

    <!-- Favicon - Use Bootstrap Icons coffee cup as temporary favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 16 16%22><text x=%220%22 y=%2214%22 fill=%22%238B4513%22>☕</text></svg>">
    
    <?php
    // Apply seasonal theme
    try {
        require_once __DIR__ . '/../webapp/backend/helpers/seasonal_theme.php';
        echo applySeasonalThemeCSS();
    } catch (Exception $e) {
        // Silently fail if seasonal theme has error
        error_log("Seasonal theme error: " . $e->getMessage());
    }
    ?>
    
    <!-- Global JavaScript Variables -->
    <script>
        window.IS_LOGGED_IN = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    </script>
</head>
<body>

<?php if (isset($_SESSION['user_id']) && isset($_SESSION['email_verified']) && $_SESSION['email_verified'] == 0 && empty($isAdminPage)): ?>
<div class="alert alert-warning text-center mb-0 rounded-0 py-2" role="alert" style="z-index: 1050;">
    <small>
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Email Anda belum diverifikasi. Silakan cek inbox Anda atau
        <a href="<?php echo SITE_URL; ?>/auth/resend_verification.php" class="alert-link text-decoration-none">kirim ulang verifikasi</a>.
    </small>
</div>
<?php endif; ?>
