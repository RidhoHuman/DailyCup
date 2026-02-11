<?php
/**
 * Email Verification Handler
 * Handles email verification tokens
 */

require_once '../includes/functions.php';

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: ' . SITE_URL . '/auth/login.php?error=invalid_token');
    exit;
}

$token = trim($_GET['token']);

// Verify the token
$result = verifyEmailToken($token);

if ($result['success']) {
    // ENHANCED FIX: Always update session if user is logged in
    // Check if the verified user is the currently logged-in user
    if (isset($_SESSION['user_id'])) {
        // If same user, update session immediately
        if ($_SESSION['user_id'] == $result['user_id']) {
            $_SESSION['email_verified'] = 1;
            
            // Redirect to dashboard with success notification
            $_SESSION['success'] = 'Email Anda berhasil diverifikasi! Terima kasih.';
            header('Location: ' . SITE_URL . '/customer/menu.php');
            exit;
        } else {
            // Different user is logged in, ask them to logout first
            $_SESSION['info'] = 'Email berhasil diverifikasi. Silakan login dengan akun tersebut.';
            header('Location: ' . SITE_URL . '/auth/logout.php');
            exit;
        }
    }
    
    // User not logged in: Redirect to login with success message
    header('Location: ' . SITE_URL . '/auth/login.php?message=email_verified');
    exit;
} else {
    // Failed - redirect to login with error
    header('Location: ' . SITE_URL . '/auth/login.php?error=' . urlencode($result['error']));
    exit;
}
?>