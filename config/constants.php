<?php
/**
 * Application Constants
 */

// Site Configuration
define('SITE_NAME', 'DailyCup Coffee Shop');
define('SITE_URL', 'http://localhost/DailyCup');
define('ADMIN_EMAIL', 'admin@dailycup.com');

// Timezone Configuration
date_default_timezone_set('Asia/Jakarta');

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../assets/images/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);

// Session Configuration
define('SESSION_TIMEOUT', 7200); // 2 hours in seconds

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ADMIN_ITEMS_PER_PAGE', 20);

// Order Configuration
define('ORDER_PREFIX', 'DC');

// Email Configuration (for order notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@dailycup.com');
define('SMTP_FROM_NAME', 'DailyCup Coffee Shop');

// Theme Colors
define('PRIMARY_COLOR', '#6F4E37'); // Coffee brown
define('SECONDARY_COLOR', '#D4A574'); // Cream/beige
define('ACCENT_COLOR', '#4A3728'); // Dark brown

// Status Constants
define('ORDER_STATUS', [
    'pending' => 'Menunggu Pembayaran',
    'confirmed' => 'Dikonfirmasi',
    'processing' => 'Sedang Diproses',
    'ready' => 'Siap Diambil/Diantar',
    'delivering' => 'Dalam Pengiriman',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan'
]);

define('PAYMENT_STATUS', [
    'pending' => 'Menunggu Pembayaran',
    'paid' => 'Sudah Dibayar',
    'failed' => 'Pembayaran Gagal'
]);

define('RETURN_REASONS', [
    'wrong_order' => 'Pesanan Salah',
    'damaged' => 'Produk Rusak/Tumpah',
    'quality_issue' => 'Kualitas Tidak Sesuai',
    'missing_items' => 'Item Kurang',
    'other' => 'Lainnya'
]);

// Store Location Configuration
define('STORE_NAME', 'DailyCup Coffee Shop');
define('STORE_ADDRESS', 'Kec. Turen
Kabupaten Malang
Jawa Timur');
define('STORE_PHONE', '021-12345678');
define('STORE_LATITUDE', -8.191135);  // Contoh: Malang area
define('STORE_LONGITUDE', 112.702670); // Contoh: Malang area
// Note: Ganti dengan koordinat lokasi toko yang sebenarnya

// Testing/Development Mode
define('TESTING_MODE', true); // Set FALSE untuk production
// Saat TESTING_MODE = true:
// - GPS/Location tidak required
// - Upload foto bisa skip (optional)
// - Semua lokasi dianggap sama (toko, kurir, customer dalam 1 lokasi)
// - Notifikasi tetap jalan untuk testing
// - Rate limiting di-bypass untuk kemudahan testing login
