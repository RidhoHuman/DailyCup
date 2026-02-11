<?php
require_once __DIR__ . '/../includes/functions.php';

// Must be logged in
if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/auth/login.php');
    exit;
}

$currentUser = getCurrentUser();

// Check if already verified
if ($currentUser['email_verified'] == 1) {
    $_SESSION['info'] = 'Email Anda sudah terverifikasi.';
    header('Location: ' . SITE_URL . '/customer/profile.php');
    exit;
}

// Rate Limiting (Prevent Spam)
$rateKey = 'resend_email_' . $currentUser['id'];
if (isset($_SESSION[$rateKey]) && (time() - $_SESSION[$rateKey] < 60)) {
    // Block if requested less than 60 seconds ago
    $_SESSION['error'] = 'Harap tunggu 1 menit sebelum mengirim ulang email.';
    header('Location: ' . SITE_URL . '/customer/profile.php');
    exit;
}

// Send Verification
$result = sendEmailVerification($currentUser['id'], $currentUser['email']);

if ($result['success']) {
    // Attempt to process queue immediately (Synchronous send)
    processEmailQueue(1);
    
    // Set timer
    $_SESSION[$rateKey] = time();
    $_SESSION['success'] = 'Email verifikasi baru telah dikirim! Silakan cek inbox/spam Anda.';
} else {
    $_SESSION['error'] = 'Gagal mengirim email: ' . $result['error'];
}

// Redirect back
if (isset($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: ' . SITE_URL . '/customer/profile.php');
}
exit;
?>