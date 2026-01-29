<?php
/**
 * TEST EMAIL SERVICE V2 (Dengan PHPMailer)
 * 
 * Script ini akan test kirim email SUNGGUHAN via Gmail SMTP
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailServiceV2.php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        TEST EMAIL SERVICE V2 (PHPMailer)                      ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Test kirim email ke email sendiri
$testEmail = getenv('SMTP_USERNAME');

if (empty($testEmail)) {
    die("❌ ERROR: SMTP_USERNAME tidak ada di .env\n");
}

echo "Mengirim test email ke: $testEmail\n\n";

// Test email sederhana
$subject = "TEST EMAIL dari DailyCup - " . date('H:i:s');
$body = "
<html>
<body style='font-family: Arial; padding: 20px;'>
    <h1 style='color: #4CAF50;'>✅ Email Berhasil Dikirim!</h1>
    <p>Jika Anda menerima email ini, berarti:</p>
    <ul>
        <li>✅ PHPMailer terinstall dengan benar</li>
        <li>✅ EmailServiceV2 berfungsi</li>
        <li>✅ SMTP Gmail dikonfigurasi dengan benar</li>
        <li>✅ Email notification system SIAP DIGUNAKAN!</li>
    </ul>
    <p><strong>Waktu:</strong> " . date('Y-m-d H:i:s') . "</p>
</body>
</html>
";

$result = EmailServiceV2::send($testEmail, $subject, $body);

echo "\n";
if ($result) {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ EMAIL BERHASIL DIKIRIM!                                   ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "LANGKAH SELANJUTNYA:\n";
    echo "1. Buka Gmail: https://mail.google.com\n";
    echo "2. Login: $testEmail\n";
    echo "3. Cari email dengan subject: '$subject'\n";
    echo "4. Cek folder SPAM jika tidak di Inbox\n";
    echo "5. Tunggu 1-2 menit\n\n";
    echo "JIKA EMAIL MASUK:\n";
    echo "✅ System READY! Email notification sudah bisa digunakan!\n\n";
} else {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ EMAIL GAGAL DIKIRIM!                                      ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "PENYEBAB KEMUNGKINAN:\n";
    echo "1. PHPMailer belum terinstall\n";
    echo "2. App Password Gmail salah\n";
    echo "3. SMTP Port/Encryption tidak sesuai\n\n";
    echo "SOLUSI:\n";
    echo "Jalankan: cd C:\\laragon\\www\\DailyCup && composer require phpmailer/phpmailer\n\n";
}

echo "=== TEST SELESAI ===\n";
?>
