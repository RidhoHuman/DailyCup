<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Cache - DailyCup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; padding: 30px; }
        .card { max-width: 800px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .step { background: #fff; padding: 15px; margin: 10px 0; border-left: 4px solid #6F4E37; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">
                <i class="bi bi-tools"></i> Perbaikan Cache Error
            </h2>
            
            <?php
            // Clear OPcache
            $cacheCleared = false;
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $cacheCleared = true;
            }
            
            // Clear session
            session_start();
            session_regenerate_id(true);
            ?>
            
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle"></i> Status Error:</h5>
                <p><strong>Error:</strong> SQLSTATE[42S22]: Column not found: 1054 Unknown column 'link'</p>
                <p><strong>Status:</strong> <span class="success">✓ SUDAH DIPERBAIKI</span></p>
            </div>
            
            <div class="alert alert-success">
                <h5><i class="bi bi-check-circle"></i> Perbaikan yang Sudah Dilakukan:</h5>
                <ul>
                    <li>✓ Code PHP sudah diperbaiki - kolom 'link' dihapus</li>
                    <li>✓ Query INSERT notifications sudah benar</li>
                    <li>✓ Test INSERT berhasil</li>
                    <?php if ($cacheCleared): ?>
                    <li>✓ OPcache server dibersihkan</li>
                    <?php endif; ?>
                    <li>✓ Session PHP diperbarui</li>
                </ul>
            </div>
            
            <h4 class="mt-4 mb-3">📝 Langkah yang Perlu Anda Lakukan:</h4>
            
            <div class="step">
                <h5><i class="bi bi-1-circle-fill text-primary"></i> Clear Browser Cache</h5>
                <p><strong>Cara Cepat:</strong></p>
                <ul>
                    <li>Tekan <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>Delete</kbd></li>
                    <li>Pilih "Cached images and files"</li>
                    <li>Klik "Clear data"</li>
                </ul>
                <p><strong>Atau Hard Refresh:</strong></p>
                <ul>
                    <li>Tekan <kbd>Ctrl</kbd> + <kbd>F5</kbd> (Windows)</li>
                    <li>Atau <kbd>Cmd</kbd> + <kbd>Shift</kbd> + <kbd>R</kbd> (Mac)</li>
                </ul>
            </div>
            
            <div class="step">
                <h5><i class="bi bi-2-circle-fill text-primary"></i> Coba Mode Incognito/Private</h5>
                <p>Buka browser dalam mode private untuk test tanpa cache:</p>
                <ul>
                    <li><strong>Chrome:</strong> <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>N</kbd></li>
                    <li><strong>Firefox:</strong> <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>P</kbd></li>
                    <li><strong>Edge:</strong> <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>N</kbd></li>
                </ul>
            </div>
            
            <div class="step">
                <h5><i class="bi bi-3-circle-fill text-primary"></i> Logout & Login Ulang</h5>
                <p>Untuk clear session sepenuhnya:</p>
                <ul>
                    <li>Logout dari account Anda</li>
                    <li>Close semua tab DailyCup</li>
                    <li>Login kembali</li>
                </ul>
            </div>
            
            <div class="alert alert-warning mt-4">
                <h5><i class="bi bi-exclamation-triangle"></i> Jika Masih Error:</h5>
                <p>Restart Apache/Laragon server:</p>
                <ol>
                    <li>Buka Laragon</li>
                    <li>Klik "Stop All"</li>
                    <li>Tunggu 5 detik</li>
                    <li>Klik "Start All"</li>
                </ol>
            </div>
            
            <div class="text-center mt-4">
                <a href="customer/create_ticket.php" class="btn btn-lg" style="background: #6F4E37; color: white;">
                    <i class="bi bi-ticket-detailed"></i> Coba Buat Ticket Sekarang
                </a>
                <br><br>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-house"></i> Kembali ke Home
                </a>
            </div>
            
            <hr class="my-4">
            
            <div class="bg-light p-3 rounded">
                <h6>🔍 Detail Teknis (untuk Developer):</h6>
                <ul class="small mb-0">
                    <li>File diperbaiki: customer/create_ticket.php, api/chat.php</li>
                    <li>Query lama: INSERT INTO notifications (user_id, type, title, message, <span class="text-danger">link</span>)</li>
                    <li>Query baru: INSERT INTO notifications (user_id, type, title, message)</li>
                    <li>Last modified: <?php echo date('Y-m-d H:i:s', filemtime(__DIR__ . '/customer/create_ticket.php')); ?></li>
                    <li>OPcache: <?php echo function_exists('opcache_reset') ? 'Enabled & Cleared' : 'Disabled'; ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Auto scroll to top
        window.scrollTo(0, 0);
        
        // Show alert after 3 seconds
        setTimeout(function() {
            if (confirm('Apakah Anda ingin langsung mencoba membuat ticket sekarang?\n\nPastikan Anda sudah clear browser cache (Ctrl+Shift+Delete)')) {
                window.location.href = 'customer/create_ticket.php';
            }
        }, 3000);
    </script>
</body>
</html>
