<?php
// Mencegah output buffering agar header tidak tertahan
if (ob_get_level()) ob_end_clean();

// ----------------------------------------------------------------------
// 1. BERSIHKAN HEADER LAMA (SOLUSI MULTIPLE VALUES)
// ----------------------------------------------------------------------
// Ini wajib ada untuk menghapus header bawaan Apache/.htaccess
if (function_exists('header_remove')) {
    header_remove('Access-Control-Allow-Origin');
    header_remove('Access-Control-Allow-Headers');
    header_remove('Access-Control-Allow-Methods');
    header_remove('Access-Control-Allow-Credentials');
}

// ----------------------------------------------------------------------
// 2. VALIDASI ORIGIN
// ----------------------------------------------------------------------
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Cek apakah origin ada di list statis ATAU sesuai pola regex (Vercel & Ngrok)
$isAllowed = in_array($origin, $allowedOrigins) 
             || preg_match('/\.vercel\.app$/', $origin) 
             || preg_match('/\.ngrok-free\.dev$/', $origin);

// Jika Origin valid, baru kita kirim Header CORS
if ($isAllowed) {
    header("Access-Control-Allow-Origin: $origin", true);
    header("Access-Control-Allow-Credentials: true", true);
    header("Access-Control-Max-Age: 86400", true); // Cache preflight 24 jam
}

// ----------------------------------------------------------------------
// 3. HANDLE PREFLIGHT REQUEST (OPTIONS)
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if ($isAllowed) {
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH", true);
        
        // Izinkan semua header yang diminta browser, PLUS ngrok-skip-browser-warning
        $requestedHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? 'Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning';
        header("Access-Control-Allow-Headers: $requestedHeaders", true);
    }
    
    // Stop eksekusi agar tidak lanjut memproses query database dsb
    header("HTTP/1.1 200 OK");
    exit(0);
}

// ----------------------------------------------------------------------
// 4. HEADER TAMBAHAN UNTUK REQUEST BIASA (GET/POST)
// ----------------------------------------------------------------------
if ($isAllowed) {
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning", true);
}
