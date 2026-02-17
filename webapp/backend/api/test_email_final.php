<?php
require_once __DIR__ . '/cors.php';
/**
 * TEST EMAIL FINAL
 * Test EmailService yang sudah menggunakan PHPMailer
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║        TEST EMAIL SERVICE (PHPMailer)                         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$testEmail = getenv('SMTP_USERNAME');

if (empty($testEmail)) {
    die("❌ ERROR: SMTP_USERNAME tidak ada di .env\n");
}

echo "Email akan dikirim ke: $testEmail\n\n";

// Disable queue untuk test langsung
EmailService::setUseQueue(false);
EmailService::init();

$subject = "TEST EMAIL FINAL - " . date('H:i:s');
$body = "
<html>
<body style='font-family: Arial; padding: 20px;'>
    <h1 style='color: #4CAF50;'>✅ Email Berhasil!</h1>
    <p>Sistem email DailyCup sudah berfungsi dengan benar.</p>
    <ul>
        <li>✅ PHPMailer terinstall</li>
        <li>✅ EmailService menggunakan PHPMailer</li>
        <li>✅ Gmail SMTP terkonfigurasi</li>
    </ul>
    <p><strong>Waktu:</strong> " . date('Y-m-d H:i:s') . "</p>
</body>
</html>
";

echo "Mengirim email...\n\n";

$result = EmailService::send($testEmail, $subject, $body);

if ($result) {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ EMAIL BERHASIL DIKIRIM!                                   ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "Cek Gmail Anda: $testEmail\n";
    echo "Subject: $subject\n\n";
} else {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ EMAIL GAGAL DIKIRIM!                                      ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "Cek error log untuk detail.\n\n";
}

echo "=== SELESAI ===\n";
?>
