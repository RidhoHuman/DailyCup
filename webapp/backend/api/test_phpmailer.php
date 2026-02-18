<?php
require_once __DIR__ . '/cors.php';
/**
 * TEST EMAIL DENGAN PHPMAILER
 * Script ini akan BENAR-BENAR mengirim email via Gmail SMTP
 */

// Load composer autoload (cek beberapa lokasi)
$autoloadPaths = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$autoloadFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    die("❌ ERROR: Composer autoload tidak ditemukan!\nJalankan: composer install\n");
}

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "=== TEST EMAIL DENGAN PHPMAILER ===\n\n";

// Baca konfigurasi dari .env
$smtp_host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ?: 587;
$smtp_user = getenv('SMTP_USERNAME');
$smtp_pass = getenv('SMTP_PASSWORD');
$smtp_from = getenv('SMTP_FROM_EMAIL') ?: $smtp_user;
$smtp_name = getenv('SMTP_FROM_NAME') ?: 'DailyCup';
$smtp_encryption = getenv('SMTP_ENCRYPTION') ?: 'tls';

echo "1. Konfigurasi SMTP:\n";
echo "   Host: $smtp_host\n";
echo "   Port: $smtp_port\n";
echo "   Encryption: $smtp_encryption\n";
echo "   Username: $smtp_user\n";
echo "   Password: " . (empty($smtp_pass) ? "❌ KOSONG!" : "✅ Ada (" . strlen($smtp_pass) . " karakter)") . "\n";
echo "   From: $smtp_name <$smtp_from>\n\n";

if (empty($smtp_user) || empty($smtp_pass)) {
    echo "❌ ERROR: SMTP_USERNAME atau SMTP_PASSWORD kosong di file .env\n";
    echo "\nCara Fix:\n";
    echo "1. Buka file: backend/api/.env\n";
    echo "2. Pastikan ada:\n";
    echo "   SMTP_USERNAME=ridhohuman11@gmail.com\n";
    echo "   SMTP_PASSWORD=mjkd cdhz nufq niue\n";
    exit(1);
}

// Buat instance PHPMailer
$mail = new PHPMailer(true);

try {
    echo "2. Mengirim email...\n\n";
    
    // Server settings
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    
    // Penting: Gunakan TLS untuk port 587 atau SSL untuk port 465
    if ($smtp_port == 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
    }
    
    $mail->Port       = $smtp_port;

    // Recipients
    $mail->setFrom($smtp_from, $smtp_name);
    $mail->addAddress($smtp_user); // Kirim ke email sendiri

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'TEST EMAIL dari DailyCup - ' . date('Y-m-d H:i:s');
    $mail->Body    = '
        <html>
        <body style="font-family: Arial, sans-serif; padding: 20px;">
            <h1 style="color: #4CAF50;">✅ Test Email Berhasil!</h1>
            <p>Jika Anda menerima email ini, berarti:</p>
            <ul>
                <li>✅ PHPMailer terinstall dengan benar</li>
                <li>✅ SMTP Gmail dikonfigurasi dengan benar</li>
                <li>✅ App Password Gmail valid</li>
                <li>✅ Server bisa kirim email via Gmail</li>
            </ul>
            <p><strong>Waktu kirim:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>Server:</strong> ' . php_uname() . '</p>
        </body>
        </html>
    ';
    $mail->AltBody = 'Test email berhasil dikirim pada ' . date('Y-m-d H:i:s');

    $mail->send();
    
    echo "\n\n";
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ EMAIL BERHASIL DIKIRIM!                                   ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "LANGKAH SELANJUTNYA:\n";
    echo "1. Buka Gmail: https://mail.google.com\n";
    echo "2. Login dengan: $smtp_user\n";
    echo "3. Cari email dengan subject: 'TEST EMAIL dari DailyCup'\n";
    echo "4. Cek folder SPAM jika tidak ada di Inbox\n";
    echo "5. Tunggu 1-2 menit (kadang delivery lambat)\n\n";
    
} catch (Exception $e) {
    echo "\n\n";
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ EMAIL GAGAL DIKIRIM!                                      ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "ERROR: {$mail->ErrorInfo}\n\n";
    
    echo "PENYEBAB KEMUNGKINAN:\n";
    echo "1. App Password Gmail salah atau expired\n";
    echo "2. 2-Step Verification belum diaktifkan\n";
    echo "3. Port atau encryption tidak sesuai\n";
    echo "4. Gmail memblokir akses dari server\n\n";
    
    echo "CARA FIX:\n";
    echo "1. Buka: https://myaccount.google.com/apppasswords\n";
    echo "2. Generate App Password baru (16 karakter)\n";
    echo "3. Copy password (format: xxxx xxxx xxxx xxxx)\n";
    echo "4. Update di backend/api/.env:\n";
    echo "   SMTP_PASSWORD=xxxx xxxx xxxx xxxx\n";
    echo "5. Coba lagi: php test_phpmailer.php\n\n";
}

echo "=== TEST SELESAI ===\n";
?>
