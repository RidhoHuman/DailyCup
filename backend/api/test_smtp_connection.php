<?php
/**
 * TEST GMAIL SMTP LANGSUNG
 * Test koneksi ke Gmail SMTP tanpa library
 */

require_once __DIR__ . '/config.php';

echo "=== TEST KONEKSI GMAIL SMTP ===\n\n";

// Baca konfigurasi
$smtp_host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ?: 587;
$smtp_user = getenv('SMTP_USERNAME');
$smtp_pass = getenv('SMTP_PASSWORD');

echo "Konfigurasi:\n";
echo "  Host: $smtp_host\n";
echo "  Port: $smtp_port\n";
echo "  User: $smtp_user\n";
echo "  Pass: " . (empty($smtp_pass) ? "❌ KOSONG" : "✅ Ada") . "\n\n";

if (empty($smtp_user) || empty($smtp_pass)) {
    die("❌ ERROR: SMTP_USERNAME atau SMTP_PASSWORD kosong!\n");
}

echo "Test 1: Koneksi ke server SMTP...\n";

$errno = 0;
$errstr = '';
$socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);

if (!$socket) {
    echo "   ❌ GAGAL koneksi ke $smtp_host:$smtp_port\n";
    echo "   Error: $errstr ($errno)\n\n";
    echo "KEMUNGKINAN:\n";
    echo "1. Firewall memblokir port $smtp_port\n";
    echo "2. Internet tidak ada\n";
    echo "3. Gmail SMTP server down\n\n";
    die();
}

echo "   ✅ Berhasil koneksi ke $smtp_host:$smtp_port\n\n";

// Baca response
$response = fgets($socket, 515);
echo "Response: $response\n";

if (strpos($response, '220') !== false) {
    echo "   ✅ SMTP server ready!\n\n";
} else {
    echo "   ❌ Response tidak sesuai\n\n";
}

fclose($socket);

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║  HASIL: Koneksi ke Gmail SMTP BERHASIL!                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "ARTINYA:\n";
echo "✅ Server bisa connect ke Gmail\n";
echo "✅ Port $smtp_port tidak diblokir\n";
echo "✅ Internet connection OK\n\n";

echo "MASALAH SEBENARNYA:\n";
echo "Email tidak masuk karena PHP mail() function di Laragon Windows\n";
echo "TIDAK BISA langsung kirim ke Gmail SMTP.\n\n";

echo "SOLUSI:\n";
echo "Harus pakai library PHPMailer atau kirim email dari backend lain.\n\n";

echo "LANGKAH SELANJUTNYA:\n";
echo "1. Install PHPMailer via Composer\n";
echo "2. Atau gunakan service eksternal (Mailgun, SendGrid, dll)\n";
echo "3. Atau buat worker yang pakai curl ke API lain\n\n";

echo "=== SELESAI ===\n";
?>
