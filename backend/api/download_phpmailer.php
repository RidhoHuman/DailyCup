<?php
/**
 * DOWNLOAD PHPMAILER MANUAL
 * Script ini akan download PHPMailer langsung dari GitHub
 */

echo "=== DOWNLOAD PHPMAILER ===\n\n";

$targetDir = __DIR__ . '/phpmailer';
$zipFile = __DIR__ . '/phpmailer.zip';
$downloadUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';

// 1. Download
echo "1. Download PHPMailer dari GitHub...\n";
$zipContent = file_get_contents($downloadUrl);
if ($zipContent === false) {
    die("❌ Gagal download! Coba manual.\n");
}

file_put_contents($zipFile, $zipContent);
echo "   ✅ Download selesai (" . round(filesize($zipFile)/1024) . " KB)\n\n";

// 2. Extract
echo "2. Extract ZIP file...\n";
$zip = new ZipArchive;
if ($zip->open($zipFile) === true) {
    $zip->extractTo(__DIR__);
    $zip->close();
    echo "   ✅ Extract selesai\n\n";
   
    // Rename folder
    $extractedDir = __DIR__ . '/PHPMailer-master';
    if (is_dir($extractedDir)) {
        if (is_dir($targetDir)) {
            // Hapus folder lama
            array_map('unlink', glob("$targetDir/*.*"));
            rmdir($targetDir);
        }
        rename($extractedDir, $targetDir);
        echo "   ✅ Folder di-rename ke: phpmailer/\n\n";
    }
    
    // Hapus ZIP file
    unlink($zipFile);
    echo "   ✅ ZIP file dihapus\n\n";
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ PHPMAILER BERHASIL DIINSTALL!                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    echo "Selanjutnya jalankan:\n";
    echo "php test_email_phpmailer_standalone.php\n\n";
    
} else {
    echo "   ❌ Gagal extract ZIP file\n";
    die();
}

echo "=== SELESAI ===\n";
?>
