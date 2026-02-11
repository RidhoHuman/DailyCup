<?php
/**
 * Core Functions Library
 * Enhanced with Phase 1, 2, and 3 Security & Enterprise Features
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Always start session - needed for both web pages and API authentication
if (session_status() === PHP_SESSION_NONE) {
    secureSessionStart();
}

/**
 * ============================================
 * PHASE 1: SECURITY FUNCTIONS
 * ============================================
 */

/**
 * Secure Session Management
 * Prevents session hijacking and fixation attacks
 */
function secureSessionStart() {
    // Session configuration
    ini_set('session.cookie_httponly', 1);  // Prevent XSS
    ini_set('session.cookie_secure', 0);    // Set to 1 for HTTPS only (production)
    ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.name', 'DAILYCUP_SESS');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate ID every 30 minutes
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
        session_regenerate_id(true);
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Session timeout (2 hours)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        header('Location: ' . SITE_URL . '/auth/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
    
    // Browser fingerprint validation
    $fingerprint = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
    } elseif ($_SESSION['fingerprint'] !== $fingerprint) {
        // Possible session hijacking
        session_unset();
        session_destroy();
        header('Location: ' . SITE_URL . '/auth/login.php?security=1');
        exit;
    }
}

/**
 * Rate Limiting
 * Prevents brute force attacks
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
    // Bypass rate limiting in testing mode
    if (defined('TESTING_MODE') && TESTING_MODE === true) {
        return ['allowed' => true, 'attempts' => 0, 'remaining' => $maxAttempts];
    }
    
    $db = getDB();
    
    try {
        // Clean old attempts
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$timeWindow]);
        
        // Count attempts
        $stmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$identifier, $timeWindow]);
        $attempts = $stmt->fetchColumn();
        
        // Calculate remaining attempts
        $remaining = $maxAttempts - $attempts;
        
        if ($attempts >= $maxAttempts) {
            // Get time of first attempt to calculate when user can try again
            $stmt = $db->prepare("SELECT created_at FROM rate_limits WHERE identifier = ? ORDER BY created_at ASC LIMIT 1");
            $stmt->execute([$identifier]);
            $firstAttempt = $stmt->fetchColumn();
            
            $retryAfter = $timeWindow - (time() - strtotime($firstAttempt));
            
            // Log security event
            logSecurityEvent('rate_limit_exceeded', null, [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts
            ]);
            
            // Redirect to user-friendly blocked page
            header('Location: ' . SITE_URL . '/auth/blocked.php?retry_after=' . $retryAfter);
            exit;
        }
        
        // Log attempt
        $stmt = $db->prepare("INSERT INTO rate_limits (identifier, created_at) VALUES (?, NOW())");
        $stmt->execute([$identifier]);
        
        // Return info for progressive warnings
        return ['allowed' => true, 'attempts' => $attempts + 1, 'remaining' => $remaining - 1];
        
    } catch (Exception $e) {
        error_log("Rate limit error: " . $e->getMessage());
        return ['allowed' => true, 'attempts' => 0, 'remaining' => $maxAttempts];
    }
}

/**
 * Clear rate limit for specific identifier (Admin tool)
 */
function clearRateLimit($identifier = null) {
    $db = getDB();
    
    try {
        if ($identifier) {
            $stmt = $db->prepare("DELETE FROM rate_limits WHERE identifier = ?");
            $stmt->execute([$identifier]);
        } else {
            // Clear all rate limits
            $db->exec("TRUNCATE TABLE rate_limits");
        }
        return true;
    } catch (Exception $e) {
        error_log("Clear rate limit error: " . $e->getMessage());
        return false;
    }
}

/**
 * Activity Logging
 * Track user actions for security audit
 */
function logActivity($action, $entityType = null, $entityId = null, $details = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entityType,
            $entityId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($details)
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Security Event Logging
 * Log security-related events
 */
function logSecurityEvent($eventType, $userId = null, $details = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO security_audit (event_type, user_id, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $eventType,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($details)
        ]);
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
    }
}

/**
 * Comprehensive Input Validation
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Indonesian phone format: 08xx, +62xx, 62xx (9-13 digits)
    return preg_match('/^(\+62|62|0)[0-9]{9,12}$/', $phone);
}

function validatePassword($password) {
    // Min 8 char, 1 uppercase, 1 lowercase, 1 number
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    
    if(!$uppercase || !$lowercase || !$number || strlen($password) < 8) {
        return false;
    }
    return true;
}

function validateInput($data, $type, $options = []) {
    if ($data === null || $data === '') {
        return $options['required'] ?? true ? false : '';
    }
    
    $data = trim($data);
    
    switch($type) {
        case 'email':
            return validateEmail($data) ? $data : false;
            
        case 'phone':
            return validatePhone($data) ? $data : false;
            
        case 'string':
            $min = $options['min'] ?? 1;
            $max = $options['max'] ?? 255;
            $length = strlen($data);
            return ($length >= $min && $length <= $max) ? htmlspecialchars($data, ENT_QUOTES, 'UTF-8') : false;
            
        case 'int':
            $val = filter_var($data, FILTER_VALIDATE_INT);
            if ($val === false) return false;
            if (isset($options['min']) && $val < $options['min']) return false;
            if (isset($options['max']) && $val > $options['max']) return false;
            return $val;
            
        case 'float':
            $val = filter_var($data, FILTER_VALIDATE_FLOAT);
            if ($val === false) return false;
            if (isset($options['min']) && $val < $options['min']) return false;
            if (isset($options['max']) && $val > $options['max']) return false;
            return $val;
            
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
            
        case 'alphanumeric':
            return preg_match('/^[a-zA-Z0-9]+$/', $data) ? $data : false;
            
        default:
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Secure File Upload
 */
function secureFileUpload($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 5242880) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    // Check file size (default 5MB)
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Max ' . ($maxSize / 1024 / 1024) . 'MB'];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only ' . implode(', ', $allowedTypes) . ' allowed'];
    }
    
    // Verify it's actually an image (for image uploads)
    if (strpos($mimeType, 'image/') === 0) {
        if (!getimagesize($file['tmp_name'])) {
            return ['success' => false, 'error' => 'File is not a valid image'];
        }
    }
    
    // Generate secure random filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    
    // Create upload directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $targetPath = $uploadDir . $newFilename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Set proper permissions
        chmod($targetPath, 0644);
        
        return [
            'success' => true,
            'filename' => $newFilename,
            'path' => $targetPath,
            'size' => $file['size'],
            'type' => $mimeType
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

/**
 * ============================================
 * ORIGINAL SECURITY FUNCTIONS (Kept for compatibility)
 * ============================================
 */

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

// Check if user is super admin
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

// Require super admin
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: ' . SITE_URL . '/admin/index.php');
        exit;
    }
}

/**
 * User Functions
 */

// Get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Get user by ID (Helper function for admin or specific operations)
function getUserById($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("getUserById error: " . $e->getMessage());
        return null;
    }
}

// Update user loyalty points
function updateUserPoints($userId, $points, $type = 'earned', $orderId = null, $description = '') {
    $db = getDB();
    
    // Update user points
    $stmt = $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
    $stmt->execute([$points, $userId]);
    
    // Log transaction
    $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, order_id, points, transaction_type, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $orderId, $points, $type, $description]);
    
    // CREATE NOTIFICATION for points update
    if ($type === 'earned' && $points > 0) {
        createNotification(
            $userId,
            "Poin Loyalty Bertambah!",
            "Selamat! Anda mendapatkan {$points} poin loyalty. {$description}",
            'loyalty_earned',
            $orderId
        );
    } elseif ($type === 'redeemed' && $points < 0) {
        createNotification(
            $userId,
            "Poin Loyalty Digunakan",
            "Anda telah menggunakan " . abs($points) . " poin loyalty. {$description}",
            'loyalty_used',
            $orderId
        );
    }
}

/**
 * Product Functions
 */

// Get all active categories
function getCategories($activeOnly = true) {
    $cacheKey = "categories_" . ($activeOnly ? 'active' : 'all');
    $cached = getCache($cacheKey);
    if ($cached !== null) return $cached;

    $db = getDB();
    $sql = "SELECT * FROM categories";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY display_order, name";
    
    $stmt = $db->query($sql);
    $data = $stmt->fetchAll();
    
    setCache($cacheKey, $data, 3600); // Cache for 1 hour
    return $data;
}

// Get products by category
function getProductsByCategory($categoryId, $activeOnly = true) {
    $cacheKey = "products_cat_{$categoryId}_" . ($activeOnly ? 'active' : 'all');
    $cached = getCache($cacheKey);
    if ($cached !== null) return $cached;

    $db = getDB();
    $sql = "SELECT * FROM products WHERE category_id = ?";
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    $sql .= " ORDER BY name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$categoryId]);
    $data = $stmt->fetchAll();

    setCache($cacheKey, $data, 1800); // Cache for 30 mins
    return $data;
}

// Get product by ID
function getProduct($productId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

// Get product variants
function getProductVariants($productId, $type = null) {
    $db = getDB();
    $sql = "SELECT * FROM product_variants WHERE product_id = ? AND is_active = 1";
    $params = [$productId];
    
    if ($type) {
        $sql .= " AND variant_type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY variant_type, price_adjustment";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Order Functions
 */

// Generate unique order number
function generateOrderNumber() {
    return ORDER_PREFIX . date('Ymd') . rand(1000, 9999);
}

// Calculate loyalty points for order
function calculateLoyaltyPoints($amount) {
    $db = getDB();
    $stmt = $db->query("SELECT points_per_rupiah FROM loyalty_settings WHERE is_active = 1 LIMIT 1");
    $settings = $stmt->fetch();
    
    if ($settings) {
        return floor($amount * $settings['points_per_rupiah']);
    }
    
    return 0;
}

/**
 * Notification Functions
 */

// Create notification
function createNotification($userId, $title, $message, $type, $orderId = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, order_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $orderId, $title, $message, $type]);
}

// Get unread notification count
function getUnreadNotificationCount($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

// Create admin notification
function createAdminNotification($orderId, $title, $message, $type) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO admin_notifications (order_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$orderId, $title, $message, $type]);
}

// Create kurir notification
function createKurirNotification($kurirId, $orderId, $title, $message, $type) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO kurir_notifications (kurir_id, order_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$kurirId, $orderId, $title, $message, $type]);
}

/**
 * File Upload Functions
 */

// Upload image
function uploadImage($file, $folder = '') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file'];
    }
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds limit'];
    }
    
    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // Create folder if not exists
    $uploadPath = UPLOAD_PATH . $folder;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }
    
    // Move file
    $destination = $uploadPath . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move file'];
}

// Upload multiple images
function uploadMultipleImages($files, $folder = '') {
    $uploadedFiles = [];
    
    foreach ($files['tmp_name'] as $key => $tmp_name) {
        $file = [
            'name' => $files['name'][$key],
            'type' => $files['type'][$key],
            'tmp_name' => $files['tmp_name'][$key],
            'error' => $files['error'][$key],
            'size' => $files['size'][$key]
        ];
        
        $result = uploadImage($file, $folder);
        if ($result['success']) {
            $uploadedFiles[] = $result['filename'];
        }
    }
    
    return $uploadedFiles;
}

/**
 * Formatting Functions
 */

// Format currency
function formatCurrency($amount) {
    // Check if currency helper is available
    if (file_exists(__DIR__ . '/../webapp/backend/helpers/currency_helper.php')) {
        require_once __DIR__ . '/../webapp/backend/helpers/currency_helper.php';
        
        // Use multi-currency format if enabled
        if (shouldShowCurrencySelector()) {
            return formatPrice($amount);
        }
    }
    
    // Fallback to default IDR format
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format date
function formatDate($date, $format = 'd M Y H:i') {
    return date($format, strtotime($date));
}

// Time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return $difference . ' detik yang lalu';
    } elseif ($difference < 3600) {
        return floor($difference / 60) . ' menit yang lalu';
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . ' jam yang lalu';
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . ' hari yang lalu';
    } else {
        return date('d M Y H:i', $timestamp);
    }
}

/**
 * Email Functions
 */

// Send email (basic implementation)
function sendEmail($to, $subject, $message, $headers = '') {
    // In production, use proper SMTP library like PHPMailer
    // This is a basic implementation
    $defaultHeaders = "From: " . SMTP_FROM_EMAIL . "\r\n";
    $defaultHeaders .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $defaultHeaders .= "MIME-Version: 1.0\r\n";
    $defaultHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $allHeaders = $defaultHeaders . $headers;
    
    return mail($to, $subject, $message, $allHeaders);
}

/**
 * WhatsApp Functions
 */

// Send WhatsApp message (placeholder for API integration)
function sendWhatsApp($phone, $message) {
    // In production, integrate with WhatsApp Business API (e.g., Twilio, Fonnté, etc.)
    // For now, we'll just simulate it or provide a link
    
    // Clean phone number (remove non-digits)
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Ensure it starts with country code (e.g., 62 for Indonesia)
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    // Example using a public API or just returning true
    // return true;
    
    // For demonstration, we can return the WhatsApp URL that the admin could use manually
    // or that could be used in a redirect
    return "https://wa.me/$phone?text=" . urlencode($message);
}

/**
 * Security Functions
 */

// Generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Pagination Functions
 */

// Get pagination data
function getPagination($totalItems, $currentPage = 1, $itemsPerPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'offset' => $offset
    ];
}

/**
 * Cart Functions
 */

// Load cart from database to session
function loadCartFromDatabase($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE user_id = ? ORDER BY created_at ASC");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    
    $_SESSION['cart'] = [];
    foreach ($items as $item) {
        $_SESSION['cart'][] = [
            'cart_key' => $item['cart_key'],
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'price' => $item['price'],
            'size' => $item['size'],
            'temperature' => $item['temperature'],
            'quantity' => $item['quantity'],
            'image' => $item['image']
        ];
    }
    
    return $_SESSION['cart'];
}

// Save cart item to database
function saveCartItemToDatabase($userId, $cartItem) {
    $db = getDB();
    
    // Check if item already exists
    $stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND cart_key = ?");
    $stmt->execute([$userId, $cartItem['cart_key']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update quantity
        $newQuantity = $existing['quantity'] + $cartItem['quantity'];
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newQuantity, $existing['id']]);
    } else {
        // Insert new item
        $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, product_name, price, size, temperature, quantity, image, cart_key) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $cartItem['product_id'],
            $cartItem['product_name'],
            $cartItem['price'],
            $cartItem['size'],
            $cartItem['temperature'],
            $cartItem['quantity'],
            $cartItem['image'],
            $cartItem['cart_key']
        ]);
    }
}

// Update cart item quantity in database
function updateCartItemQuantityInDatabase($userId, $cartKey, $quantity) {
    $db = getDB();
    
    if ($quantity <= 0) {
        // Delete item
        $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND cart_key = ?");
        $stmt->execute([$userId, $cartKey]);
    } else {
        // Update quantity
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND cart_key = ?");
        $stmt->execute([$quantity, $userId, $cartKey]);
    }
}

// Remove cart item from database
function removeCartItemFromDatabase($userId, $cartKey) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ? AND cart_key = ?");
    $stmt->execute([$userId, $cartKey]);
}

// Clear all cart items from database
function clearCartFromDatabase($userId) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);
}

// Sync session cart to database (called on login)
function syncCartToDatabase($userId) {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            saveCartItemToDatabase($userId, $item);
        }
    }
    // Reload cart from database to get merged items
    loadCartFromDatabase($userId);
}

// Calculate cart total
function calculateCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Get total items in cart (sum of quantities)
function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += intval($item['quantity']);
        }
    }
    return $count;
}

/**
 * ============================================
 * PHASE 2 SECURITY FUNCTIONS
 * ============================================
 */

/**
 * Email Verification System
 */

// Generate email verification token
function generateEmailVerificationToken() {
    return bin2hex(random_bytes(32));
}

// Send email verification
function sendEmailVerification($userId, $email) {
    try {
        $db = getDB();
        $token = generateEmailVerificationToken();
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Update user with verification token
        $stmt = $db->prepare("
            UPDATE users
            SET verification_token = ?, verification_expires = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expires, $userId]);

        // Queue verification email
        $subject = "Verify Your DailyCup Account";
        $body = generateVerificationEmailBody($token);

        queueEmail($email, $subject, $body, 'verification');
        
        // Attempt to send immediately (Synchronous fallback for non-worker environments)
        processEmailQueue(1);

        return ['success' => true, 'message' => 'Verification email sent'];
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to send verification email'];
    }
}

// Verify email token
function verifyEmailToken($token) {
    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT id, email, verification_expires
            FROM users
            WHERE verification_token = ? AND email_verified = 0
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid verification token'];
        }

        if (strtotime($user['verification_expires']) < time()) {
            return ['success' => false, 'error' => 'Verification token expired'];
        }

        // Mark email as verified
        $stmt = $db->prepare("
            UPDATE users
            SET email_verified = 1, verification_token = NULL, verification_expires = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);

        logActivity('email_verified', 'user', $user['id']);

        return [
            'success' => true, 
            'message' => 'Email verified successfully',
            'user_id' => $user['id']
        ];
    } catch (Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Verification failed'];
    }
}

// Generate verification email body
function generateVerificationEmailBody($token) {
    $verifyUrl = SITE_URL . "/auth/verify_email.php?token=" . urlencode($token);

    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #8B4513; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to DailyCup!</h1>
            </div>
            <div class='content'>
                <h2>Verify Your Email Address</h2>
                <p>Thank you for registering with DailyCup! To complete your registration, please verify your email address by clicking the button below:</p>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$verifyUrl}' class='button'>Verify Email Address</a>
                </p>

                <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                <p><a href='{$verifyUrl}'>{$verifyUrl}</a></p>

                <p>This verification link will expire in 24 hours.</p>

                <p>If you didn't create an account with DailyCup, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 DailyCup Coffee Shop. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Two-Factor Authentication (2FA)
 */

// Initialize 2FA for user
function initialize2FA($userId) {
    try {
        $db = getDB();

        // Generate secret key
        $google2fa = new PragmaRX\Google2FA\Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Store in database
        $stmt = $db->prepare("
            INSERT INTO user_2fa (user_id, secret, is_enabled)
            VALUES (?, ?, 0)
            ON DUPLICATE KEY UPDATE secret = VALUES(secret), is_enabled = 0
        ");
        $stmt->execute([$userId, $secret]);

        return [
            'success' => true,
            'secret' => $secret,
            'qr_code_url' => $google2fa->getQRCodeUrl(
                'DailyCup',
                'user' . $userId . '@dailycup.com',
                $secret
            )
        ];
    } catch (Exception $e) {
        error_log("2FA initialization error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to initialize 2FA'];
    }
}

// Verify 2FA code
function verify2FACode($userId, $code) {
    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT secret FROM user_2fa WHERE user_id = ? AND is_enabled = 1");
        $stmt->execute([$userId]);
        $user2fa = $stmt->fetch();

        if (!$user2fa) {
            return ['success' => false, 'error' => '2FA not enabled for this user'];
        }

        $google2fa = new PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($user2fa['secret'], $code);

        if ($valid) {
            // Log successful 2FA verification
            $stmt = $db->prepare("UPDATE user_2fa SET last_used = NOW() WHERE user_id = ?");
            $stmt->execute([$userId]);

            logActivity('2fa_verified', 'user', $userId);
        }

        return ['success' => $valid];
    } catch (Exception $e) {
        error_log("2FA verification error: " . $e->getMessage());
        return ['success' => false, 'error' => '2FA verification failed'];
    }
}

// Enable 2FA after verification
function enable2FA($userId, $verificationCode) {
    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT secret FROM user_2fa WHERE user_id = ? AND is_enabled = 0");
        $stmt->execute([$userId]);
        $user2fa = $stmt->fetch();

        if (!$user2fa) {
            return ['success' => false, 'error' => '2FA not initialized'];
        }

        $google2fa = new PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($user2fa['secret'], $verificationCode);

        if ($valid) {
            $stmt = $db->prepare("
                UPDATE user_2fa
                SET is_enabled = 1, enabled_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            logActivity('2fa_enabled', 'user', $userId);

            return ['success' => true, 'message' => '2FA enabled successfully'];
        }

        return ['success' => false, 'error' => 'Invalid verification code'];
    } catch (Exception $e) {
        error_log("2FA enable error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to enable 2FA'];
    }
}

// Verify 2FA code for login
function verifyUser2FA($userId, $code) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT secret FROM user_2fa WHERE user_id = ? AND is_enabled = 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        if (!$result) {
            return false;
        }

        $google2fa = new PragmaRX\Google2FA\Google2FA();
        return $google2fa->verifyKey($result['secret'], $code);
    } catch (Exception $e) {
        error_log("2FA verification error: " . $e->getMessage());
        return false;
    }
}

// Disable 2FA
function disable2FA($userId) {
    try {
        $db = getDB();

        $stmt = $db->prepare("UPDATE user_2fa SET is_enabled = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);

        logActivity('2fa_disabled', 'user', $userId);

        return ['success' => true, 'message' => '2FA disabled successfully'];
    } catch (Exception $e) {
        error_log("2FA disable error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to disable 2FA'];
    }
}

// Check if 2FA is enabled for user
function is2FAEnabled($userId) {
    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT is_enabled FROM user_2fa WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return $result && $result['is_enabled'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Password Reset System
 */

// Generate password reset token
function generatePasswordResetToken($email) {
    try {
        $db = getDB();

        // Check if user exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Email not found'];
        }

        // Generate token
        $token = bin2hex(random_bytes(32));
        
        // Use Database Time for consistency (Avoid PHP Timezone issues)
        // $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token with SQL-calculated expiration
        $stmt = $db->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at, ip_address)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)
        ");
        $stmt->execute([
            $user['id'],
            $token,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        // Send reset email
        $subject = "Reset Your DailyCup Password";
        $body = generatePasswordResetEmailBody($token);

        queueEmail($email, $subject, $body, 'password_reset');
        
        // Attempt to send immediately
        processEmailQueue(1);

        return ['success' => true, 'message' => 'Password reset email sent'];
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to send reset email'];
    }
}

// Verify password reset token
function verifyPasswordResetToken($token) {
    try {
        $db = getDB();

        // Check if token exists, is valid (not used), and NOT expired
        // Relying on Database Time for comparison
        $stmt = $db->prepare("
            SELECT user_id, expires_at, used
            FROM password_reset_tokens
            WHERE token = ? AND used = 0 AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $resetToken = $stmt->fetch();

        if (!$resetToken) {
            // Check if it exists but expired (for better error message)
            $checkStmt = $db->prepare("SELECT id FROM password_reset_tokens WHERE token = ?");
            $checkStmt->execute([$token]);
            if ($checkStmt->fetch()) {
                 return ['success' => false, 'error' => 'Reset token expired'];
            }
            return ['success' => false, 'error' => 'Invalid reset token'];
        }

        return ['success' => true, 'user_id' => $resetToken['user_id']];
    } catch (Exception $e) {
        error_log("Token verification error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Token verification failed'];
    }
}

// Reset password with token
function resetPasswordWithToken($token, $newPassword) {
    try {
        $db = getDB();

        // Verify token
        $verification = verifyPasswordResetToken($token);
        if (!$verification['success']) {
            return $verification;
        }

        // Validate new password
        if (!validatePassword($newPassword)) {
            return ['success' => false, 'error' => 'Password does not meet requirements'];
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $verification['user_id']]);

        // Mark token as used
        $stmt = $db->prepare("
            UPDATE password_reset_tokens
            SET used = 1, used_at = NOW()
            WHERE token = ?
        ");
        $stmt->execute([$token]);

        logActivity('password_reset', 'user', $verification['user_id']);

        return ['success' => true, 'message' => 'Password reset successfully'];
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Password reset failed'];
    }
}

// Generate password reset email body
function generatePasswordResetEmailBody($token) {
    $resetUrl = SITE_URL . "/auth/reset_password.php?token=" . urlencode($token);

    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .button { display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <h2>Reset Your Password</h2>
                <p>You have requested to reset your password for your DailyCup account. Click the button below to set a new password:</p>

                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetUrl}' class='button'>Reset Password</a>
                </p>

                <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                <p><a href='{$resetUrl}'>{$resetUrl}</a></p>

                <p>This reset link will expire in 1 hour for security reasons.</p>

                <p>If you didn't request a password reset, please ignore this email. Your password will remain unchanged.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 DailyCup Coffee Shop. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Email Queue System
 */

// Queue email for sending
function queueEmail($recipientEmail, $subject, $body, $template = null, $priority = 'normal') {
    try {
        $db = getDB();

        $stmt = $db->prepare("
            INSERT INTO email_queue (recipient_email, subject, body, template, priority, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$recipientEmail, $subject, $body, $template, $priority]);

        return ['success' => true];
    } catch (Exception $e) {
        error_log("Email queue error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to queue email'];
    }
}

// Process email queue (to be called by cron job or scheduled task)
function processEmailQueue($limit = 10) {
    try {
        $db = getDB();

        // Get pending emails
        $stmt = $db->prepare("
            SELECT * FROM email_queue
            WHERE status = 'pending'
            ORDER BY
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'normal' THEN 2
                    WHEN 'low' THEN 3
                END,
                created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $emails = $stmt->fetchAll();

        $processed = 0;
        foreach ($emails as $email) {
            $result = sendQueuedEmail($email);
            $processed++;
        }

        return ['success' => true, 'processed' => $processed];
    } catch (Exception $e) {
        error_log("Email processing error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to process email queue'];
    }
}

// Send queued email
function sendQueuedEmail($emailData) {
    try {
        $db = getDB();

        // Mark as sending
        $stmt = $db->prepare("UPDATE email_queue SET status = 'sending' WHERE id = ?");
        $stmt->execute([$emailData['id']]);

        // Send email using PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER'] ?? '';
        $mail->Password = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

        $mail->setFrom($_ENV['SMTP_FROM'] ?? 'noreply@dailycup.com', 'DailyCup');
        $mail->addAddress($emailData['recipient_email'], $emailData['recipient_name'] ?? '');

        $mail->isHTML(true);
        $mail->Subject = $emailData['subject'];
        $mail->Body = $emailData['body'];

        $mail->send();

        // Mark as sent
        $stmt = $db->prepare("
            UPDATE email_queue
            SET status = 'sent', sent_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$emailData['id']]);

        return ['success' => true];
    } catch (Exception $e) {
        // Mark as failed
        $stmt = $db->prepare("
            UPDATE email_queue
            SET status = 'failed', error_message = ?, attempts = attempts + 1
            WHERE id = ?
        ");
        $stmt->execute([$e->getMessage(), $emailData['id']]);

        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Login History & Security Monitoring
 */

// Log login attempt
function logLoginAttempt($userId, $success, $failureReason = null) {
    try {
        $db = getDB();

        // Detect device/browser info
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceType = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';

        // Simple device detection
        if (stripos($userAgent, 'mobile') !== false || stripos($userAgent, 'android') !== false || stripos($userAgent, 'iphone') !== false) {
            $deviceType = 'mobile';
        } elseif (stripos($userAgent, 'tablet') !== false || stripos($userAgent, 'ipad') !== false) {
            $deviceType = 'tablet';
        }

        // Simple browser detection
        if (stripos($userAgent, 'chrome') !== false) $browser = 'chrome';
        elseif (stripos($userAgent, 'firefox') !== false) $browser = 'firefox';
        elseif (stripos($userAgent, 'safari') !== false) $browser = 'safari';
        elseif (stripos($userAgent, 'edge') !== false) $browser = 'edge';

        // Simple OS detection
        if (stripos($userAgent, 'windows') !== false) $os = 'windows';
        elseif (stripos($userAgent, 'mac') !== false) $os = 'macos';
        elseif (stripos($userAgent, 'linux') !== false) $os = 'linux';
        elseif (stripos($userAgent, 'android') !== false) $os = 'android';
        elseif (stripos($userAgent, 'ios') !== false) $os = 'ios';

        $stmt = $db->prepare("
            INSERT INTO login_history
            (user_id, ip_address, user_agent, device_type, browser, os, success, failure_reason, two_fa_used)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $userAgent,
            $deviceType,
            $browser,
            $os,
            $success ? 1 : 0,
            $failureReason,
            isset($_SESSION['2fa_verified']) ? 1 : 0
        ]);

        // Check for suspicious activity
        if (!$success) {
            checkSuspiciousLoginActivity($userId);
        }

    } catch (Exception $e) {
        error_log("Login logging error: " . $e->getMessage());
    }
}

// Check for suspicious login activity
function checkSuspiciousLoginActivity($userId) {
    try {
        $db = getDB();

        // Count failed attempts in last hour
        $stmt = $db->prepare("
            SELECT COUNT(*) as failed_count
            FROM login_history
            WHERE user_id = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$userId]);
        $failedCount = $stmt->fetch()['failed_count'];

        if ($failedCount >= 5) {
            // Log security alert
            logSecurityEvent('multiple_failed_logins', $userId, [
                'failed_attempts' => $failedCount,
                'timeframe' => '1 hour'
            ]);

            // Could implement additional security measures here
            // like temporary account lockout, additional verification, etc.
        }

        // Check for login from different location/country (basic IP-based)
        $stmt = $db->prepare("
            SELECT ip_address, created_at
            FROM login_history
            WHERE user_id = ? AND success = 1
            ORDER BY created_at DESC
            LIMIT 2
        ");
        $stmt->execute([$userId]);
        $recentLogins = $stmt->fetchAll();

        if (count($recentLogins) >= 2) {
            $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $lastIP = $recentLogins[0]['ip_address'];

            if ($currentIP !== $lastIP && $currentIP !== 'unknown') {
                logSecurityEvent('unusual_login_location', $userId, [
                    'current_ip' => $currentIP,
                    'last_ip' => $lastIP,
                    'last_login' => $recentLogins[0]['created_at']
                ]);
            }
        }

    } catch (Exception $e) {
        error_log("Suspicious activity check error: " . $e->getMessage());
    }
}

/**
 * Database Backup System
 */

// Create database backup
function createDatabaseBackup($type = 'full') {
    try {
        $db = getDB();
        $backupDir = __DIR__ . '/../backups/';

        // Create backup directory if not exists
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "dailycup_backup_{$type}_{$timestamp}.sql";
        $filepath = $backupDir . $filename;

        // Get all tables
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $backupContent = "-- DailyCup Database Backup\n";
        $backupContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $backupContent .= "-- Type: $type\n\n";

        $totalSize = 0;

        foreach ($tables as $table) {
            // Skip backup logs table to avoid recursion
            if ($table === 'backup_logs') continue;

            // Get table structure
            $stmt = $db->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch();
            $backupContent .= "-- Table structure for $table\n";
            $backupContent .= $createTable['Create Table'] . ";\n\n";

            // Get table data (limit for incremental backups)
            $limit = ($type === 'incremental') ? "WHERE updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)" : "";
            $stmt = $db->query("SELECT * FROM `$table` $limit");

            if ($stmt->rowCount() > 0) {
                $backupContent .= "-- Data for $table\n";
                $backupContent .= "INSERT INTO `$table` VALUES\n";

                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $values = [];

                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        $rowValues[] = $db->quote($value);
                    }
                    $values[] = "(" . implode(", ", $rowValues) . ")";
                }

                $backupContent .= implode(",\n", $values) . ";\n\n";
            }

            $totalSize += strlen($backupContent);
        }

        // Write backup file
        file_put_contents($filepath, $backupContent);

        // Log backup
        $stmt = $db->prepare("
            INSERT INTO backup_logs (filename, filepath, filesize, backup_type, status)
            VALUES (?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([$filename, $filepath, $totalSize, $type]);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filepath,
            'size' => $totalSize
        ];

    } catch (Exception $e) {
        // Log failed backup
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO backup_logs (filename, backup_type, status, error_message)
                VALUES (?, ?, 'failed', ?)
            ");
            $stmt->execute([$filename ?? 'unknown', $type, $e->getMessage()]);
        } catch (Exception $logError) {
            error_log("Failed to log backup error: " . $logError->getMessage());
        }

        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Get backup history
function getBackupHistory($limit = 10) {
    try {
        $db = getDB();

        $stmt = $db->prepare("
            SELECT * FROM backup_logs
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Backup history error: " . $e->getMessage());
        return [];
    }
}

/**
 * ============================================
 * PHASE 3 ENTERPRISE FEATURES
 * ============================================
 */

/**
 * 1. API Authentication (JWT)
 */

/**
 * Generate JWT Token for API Authentication
 */
function generateJWT($userId, $customPayload = []) {
    $issuedAt = time();
    $expire = $issuedAt + 3600; // 1 hour default
    
    $payload = array_merge([
        'iat' => $issuedAt,
        'exp' => $expire,
        'user_id' => $userId,
        'app_name' => 'DailyCup CRM'
    ], $customPayload);
    
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

/**
 * Verify JWT Token
 */
function verifyJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        error_log("JWT Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * API Authentication Handler
 * Supports both Session (for web) and JWT (for mobile/external)
 */
function apiAuthenticate() {
    // 1. Check for Authorization header
    $authHeader = '';
    
    if (function_exists('getallheaders')) {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $authHeader = $headers['authorization'] ?? '';
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['Authorization'])) {
        $authHeader = $_SERVER['Authorization'];
    }
    
    if (preg_match('/bearer\s(\S+)/i', $authHeader, $matches)) {
        $token = $matches[1];
        $payload = verifyJWT($token);
        if ($payload && isset($payload['user_id'])) {
            return $payload['user_id'];
        } else {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
            exit;
        }
    }
    
    // 2. Fallback to session for regular web use
    if (isLoggedIn()) {
        return $_SESSION['user_id'];
    }
    
    // 3. Unauthorized
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please provide a valid token or login.']);
    exit;
}

/**
 * 2. Advanced Analytics
 */

/**
 * Get Sales data for charts
 */
function getSalesAnalytics($period = 'monthly', $limit = 6) {
    try {
        $db = getDB();
        if ($period === 'monthly') {
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%b %Y') as label,
                    SUM(final_amount) as revenue,
                    COUNT(*) as orders
                FROM orders
                WHERE status = 'completed'
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY DATE_FORMAT(created_at, '%Y-%m') DESC
                LIMIT ?
            ");
        } else {
            $stmt = $db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%d %b') as label,
                    SUM(final_amount) as revenue,
                    COUNT(*) as orders
                FROM orders
                WHERE status = 'completed' AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at) DESC
            ");
        }
        $stmt->execute([$limit]);
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        error_log("Analytics error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get Product Performance Metrics
 */
function getProductPerformance($limit = 5) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT 
                p.name as product_name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.subtotal) as total_revenue,
                COUNT(DISTINCT o.user_id) as unique_customers
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status = 'completed'
            GROUP BY p.id, p.name
            ORDER BY total_sold DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Product performance error: " . $e->getMessage());
        return [];
    }
}

/**
 * Calculate Customer Lifetime Value (CLV)
 */
function getCustomerLifetimeValue() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT AVG(total_spent) as clv
            FROM (
                SELECT user_id, SUM(final_amount) as total_spent
                FROM orders
                WHERE status = 'completed'
                GROUP BY user_id
            ) as user_totals
        ");
        return $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get count of dormant customers (No order in last X days)
 */
function getDormantCustomersCount($days = 30) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'customer' 
            AND id NOT IN (
                SELECT DISTINCT user_id 
                FROM orders 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
            )
        ");
        $stmt->execute([$days]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Customer Segmentation (Phase 3.4)
 */
function getCustomerSegmentation() {
    try {
        $db = getDB();
        $stmt = $db->query("
            SELECT 
                segment,
                COUNT(*) as count,
                AVG(total_spent) as avg_spent
            FROM (
                SELECT 
                    u.id, 
                    COALESCE(SUM(o.final_amount), 0) as total_spent,
                    CASE 
                        WHEN SUM(o.final_amount) >= 1000000 THEN 'VIP'
                        WHEN SUM(o.final_amount) >= 500000 THEN 'Loyal'
                        WHEN SUM(o.final_amount) > 0 THEN 'Occasional'
                        ELSE 'New/Inactive'
                    END as segment
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'completed'
                WHERE u.role = 'customer'
                GROUP BY u.id
            ) as customer_segments
            GROUP BY segment
            ORDER BY avg_spent DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get Sales Statistics for Dashboard
 */
function getSalesStats($period = 'today') {
    try {
        $db = getDB();
        $where = "";
        
        switch ($period) {
            case 'today': $where = "DATE(created_at) = CURDATE()"; break;
            case 'yesterday': $where = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; break;
            case 'this_week': $where = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)"; break;
            case 'this_month': $where = "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"; break;
            case 'all_time': $where = "1=1"; break;
        }

        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(final_amount), 0) as total_revenue,
                COALESCE(AVG(final_amount), 0) as avg_order_value
            FROM orders 
            WHERE status != 'cancelled' AND $where
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Sales stats error: " . $e->getMessage());
        return ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];
    }
}

/**
 * 3. Inventory Alert System
 */

/**
 * Check for items with low stock
 */
function getLowStockAlerts($threshold = 10) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, name, stock
            FROM products
            WHERE stock <= ? AND is_active = 1
            ORDER BY stock ASC
        ");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Low stock check error: " . $e->getMessage());
        return [];
    }
}

/**
 * 4. Enhanced Promo System
 */

/**
 * Validate a discount code with advanced rules
 */
function validateDiscount($code, $userId, $cartTotal, $cartItems = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM discounts 
            WHERE code = ? AND is_active = 1 
            AND start_date <= NOW() AND end_date >= NOW()
        ");
        $stmt->execute([$code]);
        $discount = $stmt->fetch();

        if (!$discount) {
            return ['valid' => false, 'message' => 'Kode diskon tidak valid atau sudah kadaluarsa'];
        }

        // 1. Global usage limit
        if ($discount['usage_limit'] !== null && $discount['usage_count'] >= $discount['usage_limit']) {
            return ['valid' => false, 'message' => 'Kode diskon ini sudah mencapai batas penggunaan'];
        }

        // 2. Minimum purchase
        if ($cartTotal < $discount['min_purchase']) {
            return ['valid' => false, 'message' => 'Minimum pembelian ' . formatCurrency($discount['min_purchase']) . ' diperlukan'];
        }

        // 3. User usage limit
        $stmt = $db->prepare("SELECT COUNT(*) FROM discount_usage WHERE discount_id = ? AND user_id = ?");
        $stmt->execute([$discount['id'], $userId]);
        $userUsage = $stmt->fetchColumn();
        if ($userUsage >= $discount['usage_limit_per_user']) {
            return ['valid' => false, 'message' => 'Anda sudah menggunakan kode diskon ini'];
        }

        // 4. First time only
        if ($discount['is_first_time_only']) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() > 0) {
                return ['valid' => false, 'message' => 'Diskon ini hanya untuk pelanggan baru'];
            }
        }

        // 5. Category specific
        if ($discount['category_id'] !== null) {
            $foundCategory = false;
            foreach ($cartItems as $item) {
                $pStmt = $db->prepare("SELECT category_id FROM products WHERE id = ?");
                $pStmt->execute([$item['product_id'] ?? 0]);
                if ($pStmt->fetchColumn() == $discount['category_id']) {
                    $foundCategory = true;
                    break;
                }
            }
            if (!$foundCategory) {
                return ['valid' => false, 'message' => 'Diskon ini hanya berlaku untuk kategori tertentu'];
            }
        }

        return ['valid' => true, 'discount' => $discount];

    } catch (Exception $e) {
        error_log("Discount validation error: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Terjadi kesalahan saat validasi diskon'];
    }
}

/**
 * ============================================
 * PHASE 4: COMPLIANCE & GDPR
 * ============================================
 */

/**
 * Export all data related to a user for GDPR compliance
 */
function exportUserData($userId) {
    try {
        $db = getDB();
        $data = [
            'exported_at' => date('Y-m-d H:i:s'),
            'app' => 'DailyCup CRM',
            'user_id' => $userId
        ];

        // 1. Basic Profile Info
        $stmt = $db->prepare("SELECT name, email, phone, address, loyalty_points, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $data['profile'] = $stmt->fetch();

        // 2. Orders History
        $stmt = $db->prepare("SELECT order_number, total_amount, discount_amount, final_amount, delivery_method, payment_status, status, created_at FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data['orders'] = $stmt->fetchAll();

        // 3. Reviews & Ratings
        $stmt = $db->prepare("SELECT product_id, rating, comment, created_at FROM reviews WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data['reviews'] = $stmt->fetchAll();

        // 4. Support Tickets
        $stmt = $db->prepare("SELECT subject, category, priority, status, created_at FROM support_tickets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $data['tickets'] = $stmt->fetchAll();
        
        // 5. Activity Logs
        $stmt = $db->prepare("SELECT action, entity_type, ip_address, created_at FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
        $stmt->execute([$userId]);
        $data['recent_activity'] = $stmt->fetchAll();

        return $data;
    } catch (Exception $e) {
        error_log("GDPR Export Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log a GDPR Request (Export, Delete, Rectify)
 */
function logGDPRRequest($userId, $type, $notes = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO gdpr_requests (user_id, request_type, status, notes) VALUES (?, ?, 'pending', ?)");
        return $stmt->execute([$userId, $type, $notes]);
    } catch (Exception $e) {
        error_log("GDPR Request Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has a pending deletion request
 */
function hasPendingDeletionRequest($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM gdpr_requests WHERE user_id = ? AND request_type = 'delete' AND status = 'pending'");
        $stmt->execute([$userId]);
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * CACHE FUNCTIONS - Phase 4: Performance & Scale
 * Simple file-based caching to reduce database load on static/slow-changing data
 */

function setCache($key, $data, $ttl = 3600) {
    $cacheDir = __DIR__ . '/../cache';
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    
    $cacheFile = $cacheDir . '/' . md5($key) . '.json';
    $cacheData = [
        'expiry' => time() + $ttl,
        'data' => $data
    ];
    
    return file_put_contents($cacheFile, json_encode($cacheData));
}

function getCache($key) {
    $cacheFile = __DIR__ . '/../cache/' . md5($key) . '.json';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    
    if (!$cacheData || time() > $cacheData['expiry']) {
        @unlink($cacheFile);
        return null;
    }
    
    return $cacheData['data'];
}

function deleteCache($key) {
    $cacheFile = __DIR__ . '/../cache/' . md5($key) . '.json';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

function clearAllCache() {
    $files = glob(__DIR__ . '/../cache/*.json');
    foreach ($files as $file) {
        @unlink($file);
    }
}
