<?php
/**
 * Two-Factor Authentication Setup
 * Allows users to enable/disable 2FA
 */

require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);

if (!$user) {
    header('Location: ' . SITE_URL . '/auth/logout.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable_2fa'])) {
        // Enable 2FA
        $result = initialize2FA($userId);
        if ($result['success']) {
            $message = '2FA has been enabled successfully!';
        } else {
            $error = $result['error'];
        }
    } elseif (isset($_POST['disable_2fa'])) {
        // Disable 2FA
        $result = disable2FA($userId);
        if ($result['success']) {
            $message = '2FA has been disabled successfully!';
        } else {
            $error = $result['error'];
        }
    } elseif (isset($_POST['verify_code'])) {
        // Verify and enable 2FA
        $code = trim($_POST['verification_code'] ?? '');
        if (empty($code)) {
            $error = 'Please enter the verification code.';
        } else {
            $result = enable2FA($userId, $code);
            if ($result['success']) {
                $message = '2FA has been enabled and verified!';
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Check current 2FA status
$is2FAEnabled = is2FAEnabled($userId);
$qrCodeUrl = '';
$secret = '';

if (!$is2FAEnabled) {
    // Generate QR code for setup
    $initResult = initialize2FA($userId);
    if ($initResult['success']) {
        $qrCodeUrl = $initResult['qr_code_url'];
        $secret = $initResult['secret'];
    }
}

$pageTitle = 'Two-Factor Authentication Setup';
include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>Current Status</h5>
                        <p class="mb-2">
                            <strong>Status:</strong>
                            <span class="badge <?php echo $is2FAEnabled ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo $is2FAEnabled ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </p>
                        <p class="text-muted">
                            Two-factor authentication adds an extra layer of security to your account by requiring
                            a verification code from your authenticator app in addition to your password.
                        </p>
                    </div>

                    <?php if (!$is2FAEnabled): ?>
                        <div class="mb-4">
                            <h5>Enable Two-Factor Authentication</h5>
                            <p>To enable 2FA, you'll need an authenticator app like Google Authenticator, Authy, or Microsoft Authenticator.</p>

                            <?php if ($qrCodeUrl): ?>
                                <div class="text-center mb-3">
                                    <p><strong>Scan this QR code with your authenticator app:</strong></p>
                                    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                </div>

                                <div class="mb-3">
                                    <p><strong>Or enter this code manually:</strong></p>
                                    <code class="d-block p-2 bg-light"><?php echo htmlspecialchars($secret); ?></code>
                                </div>

                                <form method="POST" class="mb-3">
                                    <div class="form-group">
                                        <label for="verification_code">Enter the 6-digit code from your authenticator app:</label>
                                        <input type="text" class="form-control" id="verification_code" name="verification_code"
                                               maxlength="6" pattern="[0-9]{6}" required>
                                    </div>
                                    <button type="submit" name="verify_code" class="btn btn-primary">
                                        <i class="fas fa-check"></i> Verify and Enable 2FA
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST">
                                    <button type="submit" name="enable_2fa" class="btn btn-primary">
                                        <i class="fas fa-qrcode"></i> Generate QR Code
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <h5>Disable Two-Factor Authentication</h5>
                            <p class="text-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Disabling 2FA will reduce the security of your account.
                                Are you sure you want to proceed?
                            </p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to disable 2FA?')">
                                <button type="submit" name="disable_2fa" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Disable 2FA
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h6>Backup Codes</h6>
                        <p class="text-muted">
                            Keep backup codes in a safe place. You can use them to access your account if you lose your device.
                        </p>
                        <button class="btn btn-outline-secondary btn-sm" onclick="alert('Backup codes feature coming soon!')">
                            <i class="fas fa-key"></i> Generate Backup Codes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>