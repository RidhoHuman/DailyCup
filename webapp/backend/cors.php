<?php
// Mencegah output buffering agar header tidak tertahan
if (ob_get_level()) ob_end_clean();

// 1. BERSIHKAN Header lama (jika ada sisa-sisa dari Apache)
if (function_exists('header_remove')) {
    header_remove('Access-Control-Allow-Origin');
    header_remove('Access-Control-Allow-Headers');
    header_remove('Access-Control-Allow-Methods');
    header_remove('Access-Control-Allow-Credentials');
}

// 2. Definisi Origin yang diperbolehkan
$allowedOrigins = [
    'https://dailycup.vercel.app', // Domain Vercel kamu
    'http://localhost:3000',       // Localhost Next.js
    'http://localhost',
];

// Ambil origin dari request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Cek apakah origin valid (atau izinkan semua saat dev jika pakai ngrok dinamis)
// Kita pakai regex sederhana untuk mengizinkan semua subdomain vercel/ngrok
$isAllowed = in_array($origin, $allowedOrigins) 
             || preg_match('/\.vercel\.app$/', $origin) 
             || preg_match('/\.ngrok-free\.dev$/', $origin);

if ($isAllowed) {
    // Parameter TRUE di akhir fungsi header() artinya REPLACE (Timpa)
    header("Access-Control-Allow-Origin: $origin", true);
    header("Access-Control-Allow-Credentials: true", true);
    header("Access-Control-Max-Age: 86400", true);
}

// 3. Handle Preflight Request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH", true);

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}, ngrok-skip-browser-warning", true);
    
    // Stop eksekusi script di sini agar tidak lanjut ke logika lain
    exit(0);
}

// 4. Header Default untuk request biasa
// Pastikan ngrok-skip-browser-warning ikut serta
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning", true);
?>