<?php
/**
 * TEST EMAIL SEDERHANA - LANGSUNG KE GMAIL
 * Cek apakah SMTP Gmail bisa kirim email atau tidak
 */

require_once __DIR__ . '/config.php';

echo "=== TEST EMAIL SEDERHANA ===\n\n";

// 1. Baca konfigurasi dari .env
$smtp_host = getenv('SMTP_HOST');
$smtp_port = getenv('SMTP_PORT');
$smtp_user = getenv('SMTP_USERNAME');
$smtp_pass = getenv('SMTP_PASSWORD');
$smtp_from = getenv('SMTP_FROM_EMAIL');
$smtp_name = getenv('SMTP_FROM_NAME');

echo "1. Konfigurasi SMTP:\n";
echo "   Host: $smtp_host\n";
echo "   Port: $smtp_port\n";
echo "   Username: $smtp_user\n";
echo "   Password: " . (empty($smtp_pass) ? "KOSONG!" : str_repeat('*', strlen($smtp_pass))) . "\n";
echo "   From Email: $smtp_from\n";
echo "   From Name: $smtp_name\n\n";

if (empty($smtp_user) || empty($smtp_pass)) {
    echo "❌ ERROR: SMTP Username atau Password KOSONG!\n";
    echo "   Cek file .env, pastikan SMTP_USERNAME dan SMTP_PASSWORD terisi.\n";
    exit(1);
}

// 2. Test kirim email menggunakan mail() native PHP
echo "2. Mengirim test email...\n";

$to = $smtp_user; // Kirim ke email sendiri
$subject = "TEST EMAIL dari DailyCup - " . date('Y-m-d H:i:s');
$message = "
<html>
<body>
    <h1>Test Email Berhasil!</h1>
    <p>Jika Anda menerima email ini, berarti SMTP Gmail sudah terkonfigurasi dengan benar.</p>
    <p>Waktu: " . date('Y-m-d H:i:s') . "</p>
</body>
</html>
";

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $smtp_name . ' <' . $smtp_from . '>',
    'Reply-To: ' . $smtp_from
);

$result = mail($to, $subject, $message, implode("\r\n", $headers));

echo "\n3. Hasil:\n";
if ($result) {
    echo "   ✅ mail() return TRUE\n";
    echo "   ✅ Email dikirim ke: $to\n\n";
    echo "LANGKAH SELANJUTNYA:\n";
    echo "1. Cek inbox Gmail: $to\n";
    echo "2. Cek folder SPAM jika tidak ada di inbox\n";
    echo "3. Tunggu 1-2 menit (kadang SMTP lambat)\n\n";
    echo "JIKA EMAIL TIDAK MASUK:\n";
    echo "- Kemungkinan besar: Laragon belum dikonfigurasi untuk kirim email\n";
    echo "- Solusi: Install & konfigurasi sendmail atau gunakan library PHPMailer\n";
} else {
    echo "   ❌ mail() return FALSE\n";
    echo "   ❌ Email GAGAL dikirim!\n\n";
    echo "PENYEBAB KEMUNGKINAN:\n";
    echo "1. Laragon tidak punya SMTP server built-in\n";
    echo "2. PHP mail() function tidak dikonfigurasi\n";
    echo "3. Perlu install sendmail atau gunakan PHPMailer\n\n";
    echo "SOLUSI:\n";
    echo "Gunakan PHPMailer library untuk kirim email via Gmail SMTP\n";
    echo "File yang perlu dijalankan: test_phpmailer.php (akan saya buat)\n";
}

echo "\n=== TEST SELESAI ===\n";
?>
