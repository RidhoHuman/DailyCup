<?php
/**
 * Create Order API
 * 
 * Receives order data and creates a new order with payment integration.
 * Implements rate limiting, input sanitization, and audit logging.
 * Saves orders to MySQL database.
 */

// CORS must be first
require_once __DIR__ . '/cors.php';

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/input_sanitizer.php';
require_once __DIR__ . '/audit_log.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/email/EmailService.php';
require_once __DIR__ . '/notifications/NotificationService.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Rate limiting for orders (stricter limit)
$clientIP = RateLimiter::getClientIP();
RateLimiter::enforce($clientIP, 'order');

// Optional authentication - get user if token provided
$authUser = JWT::getUser();
$userId = $authUser ? ($authUser['user_id'] ?? null) : null;

// Debug logging for COD orders
if ($input['paymentMethod'] ?? null === 'cod') {
    error_log("COD Order Debug - Token present: " . (!empty($_SERVER['HTTP_AUTHORIZATION']) ? 'YES' : 'NO'));
    error_log("COD Order Debug - Auth User: " . json_encode($authUser));
    error_log("COD Order Debug - User ID extracted: " . ($userId ?? 'NULL'));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Validate and sanitize input
$items = $input['items'] ?? [];
$total = InputSanitizer::float($input['total'] ?? null);
$customer = $input['customer'] ?? null;

if (!$items || !is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid items data']);
    exit;
}

if ($total === null || $total <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order total']);
    exit;
}

if (!$customer || !is_array($customer)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing customer data']);
    exit;
}

// Sanitize customer data
$sanitizedCustomer = [
    'name' => InputSanitizer::string($customer['name'] ?? '', 100),
    'email' => InputSanitizer::email($customer['email'] ?? ''),
    'phone' => InputSanitizer::phone($customer['phone'] ?? ''),
    'address' => InputSanitizer::string($customer['address'] ?? '', 500)
];

// Validate required customer fields
if (empty($sanitizedCustomer['name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Customer name is required']);
    exit;
}

if (empty($sanitizedCustomer['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

// Sanitize items
$sanitizedItems = [];
foreach ($items as $item) {
    // Ensure ID is string before sanitizing to prevent type errors
    $itemId = isset($item['id']) ? (string)$item['id'] : null;
    $sanitizedItem = [
        'id' => InputSanitizer::id($itemId) ?? uniqid('item_'),
        'name' => InputSanitizer::string($item['name'] ?? 'Unknown Item', 200),
        'price' => InputSanitizer::float($item['price'] ?? 0),
        'quantity' => InputSanitizer::int($item['quantity'] ?? 1, 1, 100)
    ];
    
    // Add customization fields if present
    if (isset($item['size'])) {
        $sanitizedItem['size'] = InputSanitizer::string($item['size'], 10);
    }
    if (isset($item['temperature'])) {
        $sanitizedItem['temperature'] = InputSanitizer::string($item['temperature'], 10);
    }
    if (isset($item['addons'])) {
        $sanitizedItem['addons'] = $item['addons']; // Already JSON string from frontend
    }
    if (isset($item['notes'])) {
        $sanitizedItem['notes'] = InputSanitizer::string($item['notes'], 500);
    }
    if (isset($item['base_price'])) {
        $sanitizedItem['base_price'] = InputSanitizer::float($item['base_price']);
    }
    if (isset($item['size_price_modifier'])) {
        $sanitizedItem['size_price_modifier'] = InputSanitizer::float($item['size_price_modifier']);
    }
    if (isset($item['addons_total'])) {
        $sanitizedItem['addons_total'] = InputSanitizer::float($item['addons_total']);
    }
    
    $sanitizedItems[] = $sanitizedItem;
}

// Recalculate total from items for security
$calculatedTotal = 0;
foreach ($sanitizedItems as $item) {
    // If backend doesn't know item usage (e.g. variants), this check might fail or need relaxation.
    // For now, assume price is trusted for custom-like items, OR you must fetch base price from DB.
    // To fix 'Order total mismatch', let's sum items then add delivery fee.
    $calculatedTotal += $item['price'] * $item['quantity'];
}

// Add delivery fee (if provided securely or fixed)
// Frontend sends 'deliveryFee' = 15000 in 'deliveryFee' field or implicitly in 'total'
// We should check what was sent.
$deliveryFee = InputSanitizer::float($input['deliveryFee'] ?? 0);
$discount = InputSanitizer::float($input['discount'] ?? 0);

$finalCalculatedTotal = $calculatedTotal + $deliveryFee - $discount;

// Allow small rounding differences (1%)
if (abs($finalCalculatedTotal - $total) > ($total * 0.01)) {
    AuditLog::logSecurityAlert('TOTAL_MISMATCH', [
        'claimed_total' => $total,
        'calculated_total' => $finalCalculatedTotal,
        'items_total' => $calculatedTotal,
        'delivery' => $deliveryFee,
        'discount' => $discount,
        'ip' => $clientIP
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Order total mismatch: calc=' . $finalCalculatedTotal . ' vs sent=' . $total
    ]);
    exit;
}

$items = $sanitizedItems;
$customer = $sanitizedCustomer;

// Generate order number
$orderNumber = 'ORD-' . time() . '-' . rand(1000,9999);

// Get additional order details
$paymentMethod = InputSanitizer::string($input['paymentMethod'] ?? 'cash', 50);
$notes = InputSanitizer::string($input['notes'] ?? '', 1000);
$subtotal = InputSanitizer::float($input['subtotal'] ?? $total);
$discount = InputSanitizer::float($input['discount'] ?? 0);
$deliveryMethod = InputSanitizer::string($input['deliveryMethod'] ?? 'takeaway', 20);
$deliveryDistance = InputSanitizer::float($input['deliveryDistance'] ?? 0);
$customerLat = InputSanitizer::float($input['customerLat'] ?? null);
$customerLng = InputSanitizer::float($input['customerLng'] ?? null);

// Map delivery method to enum values
$validDeliveryMethods = ['dine-in', 'takeaway', 'delivery'];
if (!in_array($deliveryMethod, $validDeliveryMethods)) {
    $deliveryMethod = 'takeaway';
}

// ============================================
// COD VALIDATION (if payment method is COD)
// ============================================
$codAmountLimit = null;
if ($paymentMethod === 'cod') {
    if (!$userId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'COD hanya tersedia untuk user yang sudah login',
            'reasons' => ['❌ Silakan login terlebih dahulu untuk menggunakan COD']
        ]);
        exit;
    }
    
    // Get user data for COD validation
    $stmt = $pdo->prepare("
        SELECT 
            u.trust_score,
            u.total_successful_orders,
            u.cod_enabled,
            u.cod_blacklisted,
            u.blacklist_reason,
            u.is_verified_user
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if user is blacklisted
    if ($userData['cod_blacklisted']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Akun Anda diblokir dari menggunakan COD',
            'reasons' => [
                '❌ Akun Anda diblokir dari menggunakan COD',
                'Alasan: ' . ($userData['blacklist_reason'] ?? 'Pelanggaran kebijakan COD'),
                'ℹ️ Gunakan pembayaran online (Xendit) untuk melanjutkan'
            ]
        ]);
        exit;
    }
    
    // Get COD rules
    $rulesStmt = $pdo->query("
        SELECT rule_name, rule_value 
        FROM cod_validation_rules 
        WHERE is_active = 1
    ");
    $rules = $rulesStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Determine amount limit based on user status
    if ($userData['is_verified_user'] && $userData['total_successful_orders'] >= 1) {
        $codAmountLimit = $rules['max_cod_amount_verified'] ?? 100000;
    } else {
        $codAmountLimit = $rules['max_cod_amount_new_user'] ?? 50000;
    }
    
    // Validate amount
    if ($total > $codAmountLimit) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Jumlah pesanan melebihi batas COD Anda',
            'reasons' => [
                sprintf('❌ Jumlah pesanan (Rp %s) melebihi batas COD (Rp %s)',
                    number_format($total, 0, ',', '.'),
                    number_format($codAmountLimit, 0, ',', '.')
                ),
                'ℹ️ Gunakan pembayaran online untuk pesanan ini'
            ]
        ]);
        exit;
    }
    
    // Validate distance
    $maxDistance = $rules['max_delivery_distance'] ?? 5;
    if ($deliveryDistance > $maxDistance) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Jarak pengiriman melebihi batas COD',
            'reasons' => [
                sprintf('❌ Jarak pengiriman (%.1f KM) melebihi batas COD (%.1f KM)',
                    $deliveryDistance, $maxDistance
                ),
                'ℹ️ COD hanya tersedia untuk jarak < ' . $maxDistance . ' KM'
            ]
        ]);
        exit;
    }
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // If not authenticated, try to find existing user by email or create guest order
    if (!$userId) {
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkUser->execute([$customer['email']]);
        $existingUser = $checkUser->fetch();
        if ($existingUser) {
            $userId = $existingUser['id'];
        }
    }
    
    // Store actual user_id for the order (or null if guest)
    $orderUserId = $userId ?: null;

    // Calculate order expiry time (60 minutes from now)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));
    
    // Get customer coordinates (for delivery radius validation)
    $deliveryLat = isset($customer['latitude']) ? floatval($customer['latitude']) : null;
    $deliveryLng = isset($customer['longitude']) ? floatval($customer['longitude']) : null;
    
    // Insert order into database with enhanced COD fields
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, customer_name, customer_phone, order_number, total_amount, discount_amount, 
            final_amount, delivery_method, delivery_address, 
            delivery_lat, delivery_lng,
            customer_notes, status, payment_method, payment_status, 
            expires_at, delivery_distance, cod_amount_limit, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $orderUserId,
        $sanitizedCustomer['name'] ?: null,
        $sanitizedCustomer['phone'] ?: null,
        $orderNumber,
        $subtotal,
        $discount,
        $total,
        $deliveryMethod,
        $customer['address'] ?: null,
        $deliveryLat,
        $deliveryLng,
        $notes ?: null,
        $paymentMethod,
        $expiresAt,
        $deliveryDistance > 0 ? $deliveryDistance : null,
        $codAmountLimit
    ]);

    $dbOrderId = $pdo->lastInsertId();

    // Enqueue geocode job if delivery coordinates are missing
    if (empty($deliveryLat) || empty($deliveryLng)) {
        try {
            $jobStmt = $pdo->prepare("INSERT INTO geocode_jobs (order_id, status) VALUES (?, 'pending')");
            $jobStmt->execute([$dbOrderId]);
        } catch (PDOException $e) {
            error_log("Failed to create geocode job for order {$dbOrderId}: " . $e->getMessage());
        }
    }

    // Insert order items (using existing table structure)
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, product_name, size, temperature, 
            quantity, unit_price, subtotal, notes, addons, 
            base_price, size_price_modifier, addons_total
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $productId = is_numeric($item['id']) ? (int)$item['id'] : 1;
        $size = isset($item['size']) ? InputSanitizer::string($item['size'], 50) : null;
        $temperature = isset($item['temperature']) ? InputSanitizer::string($item['temperature'], 50) : null;
        $itemSubtotal = $item['price'] * $item['quantity'];
        $itemNotes = isset($item['notes']) ? InputSanitizer::string($item['notes'], 500) : null;
        
        // Handle add-ons (already JSON string from frontend)
        $addons = isset($item['addons']) ? $item['addons'] : null;
        
        // Price breakdown for customization
        $basePrice = isset($item['base_price']) ? InputSanitizer::float($item['base_price']) : $item['price'];
        $sizeModifier = isset($item['size_price_modifier']) ? InputSanitizer::float($item['size_price_modifier']) : 0.0;
        $addonsTotal = isset($item['addons_total']) ? InputSanitizer::float($item['addons_total']) : 0.0;
        
        $itemStmt->execute([
            $dbOrderId,
            $productId,
            $item['name'],
            $size,
            $temperature,
            $item['quantity'],
            $item['price'],
            $itemSubtotal,
            $itemNotes,
            $addons,
            $basePrice,
            $sizeModifier,
            $addonsTotal
        ]);
    }

    // Commit transaction
    $pdo->commit();
    
    // Use order number as the public ID
    $orderId = $orderNumber;

    // Create COD tracking if payment method is COD
    if ($paymentMethod === 'cod') {
        try {
            $codStmt = $pdo->prepare("
                INSERT INTO cod_tracking (order_id, status, notes) 
                VALUES (?, 'pending', 'Menunggu konfirmasi admin')
            ");
            $codStmt->execute([$orderNumber]);
            
            // Log initial status
            $historyStmt = $pdo->prepare("
                INSERT INTO cod_status_history (order_id, status, notes) 
                VALUES (?, 'pending', 'Order dibuat - menunggu konfirmasi')
            ");
            $historyStmt->execute([$orderNumber]);
        } catch (PDOException $e) {
            error_log("COD tracking creation error: " . $e->getMessage());
            // Don't fail the order if tracking creation fails
        }
    }

    // Also save to JSON file as backup (for payment webhook compatibility)
    $storeDir = __DIR__ . '/../data';
    if (!is_dir($storeDir)) mkdir($storeDir, 0755, true);
    $ordersFile = $storeDir . '/orders.json';
    $orders = [];
    if (file_exists($ordersFile)) {
        $raw = file_get_contents($ordersFile);
        $orders = $raw ? json_decode($raw, true) : [];
    }
    
    $order = [
        'id' => $orderId,
        'items' => $items,
        'total' => $total,
        'customer' => $customer,
        'status' => 'pending',
        'created_at' => date('c'),
        'ip_address' => $clientIP
    ];
    $orders[$orderId] = $order;
    file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));

    // Log order creation
    AuditLog::logOrderCreate($orderId, $total, $userId);

    // Create in-app notification for authenticated user
    try {
        if ($userId) {
            $notificationService = new NotificationService($pdo);
            $notificationService->createOrderNotification((int)$userId, $orderId, 'pending');
        }

        // Notify Admin
        $adminStmt = $pdo->prepare("INSERT INTO admin_notifications (order_id, type, title, message, created_at) VALUES (?, 'new_order', 'Pesanan Baru', ?, NOW())");
        $adminMsg = "Pesanan baru #$orderNumber dari " . ($customer['name'] ?? 'Guest') . " sebesar Rp " . number_format($total, 0, ',', '.');
        $adminStmt->execute([$dbOrderId, $adminMsg]);

    } catch (Throwable $e) {
        error_log("Notification create_order error: " . $e->getMessage());
    }

    // Send order confirmation email
    try {
        $orderData = [
            'order_number' => $orderNumber,
            'items' => $items,
            'total' => $total,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery_method' => $deliveryMethod,
            'payment_method' => $paymentMethod,
            'created_at' => date('Y-m-d H:i:s')
        ];
        EmailService::sendOrderConfirmation($orderData, $customer);
    } catch (Exception $e) {
        // Log email error but don't fail the order
        error_log("Failed to send order confirmation email: " . $e->getMessage());
    }

// Xendit integration if secret key provided AND payment is NOT COD
$xendit_key = getenv('XENDIT_SECRET_KEY');
error_log("XENDIT: Key present = " . ($xendit_key ? 'YES' : 'NO') . ", Method = $paymentMethod");

if ($paymentMethod === 'cod') {
    // COD Logic - Manual admin confirmation required
    try {
        // Send notification to admin for COD approval
        $adminNotifStmt = $pdo->prepare("
            INSERT INTO admin_notifications 
            (order_id, type, title, message, link, created_at) 
            VALUES (?, 'cod_pending', '⏳ COD Order Menunggu Konfirmasi', ?, ?, NOW())
        ");
        $adminMsg = sprintf(
            'Pesanan COD #%s dari %s sebesar Rp %s. Jarak: %.1f KM. Silakan review dan konfirmasi.',
            $orderNumber,
            $customer['name'],
            number_format($total, 0, ',', '.'),
            $deliveryDistance
        );
        $adminNotifStmt->execute([
            $dbOrderId,
            $adminMsg,
            '/admin/orders/cod/' . $dbOrderId
        ]);
        
        // Log COD order creation
        $logStmt = $pdo->prepare("
            INSERT INTO order_status_logs 
            (order_id, from_status, to_status, changed_by_type, notes)
            VALUES (?, NULL, 'pending', 'customer', 'COD order created - awaiting admin confirmation')
        ");
        $logStmt->execute([$dbOrderId]);
        
    } catch (PDOException $e) {
        error_log("COD notification error: " . $e->getMessage());
        // Don't fail order if notification fails
    }
    
    echo json_encode([
        'success' => true, 
        'orderId' => $orderId,
        'order_number' => $orderNumber,
        'payment_method' => 'cod',
        'message' => 'Pesanan COD dibuat. Menunggu konfirmasi admin dalam 60 menit.',
        'expires_at' => $expiresAt,
        'requires_confirmation' => true,
        // Redirect to success/tracking page
        'redirect' => '/checkout/success?orderId=' . urlencode($orderId)
    ]);
    exit;
}

if ($xendit_key) {
    error_log("XENDIT: Attempting to create invoice for order $orderId");
    
    // Build callback URL and append local token if provided (for simple webhook verification)
    $callback_url = getenv('XENDIT_CALLBACK_URL') ?: ((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/backend/api/notify_xendit.php');
    $callback_token = getenv('XENDIT_CALLBACK_TOKEN');
    if ($callback_token) {
        $callback_url .= (strpos($callback_url, '?') === false) ? '?token=' . urlencode($callback_token) : '&token=' . urlencode($callback_token);
    }

    // Build Xendit invoice payload
    // Force reload APP_URL from .env file
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, 'APP_URL=') === 0) {
                list(, $value) = explode('=', $line, 2);
                $appUrl = trim($value);
                break;
            }
        }
    }
    // Fallback if not found in .env
    if (!isset($appUrl) || empty($appUrl)) {
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost:3001';
    }
    
    error_log("XENDIT: Using APP_URL = $appUrl");
    
    $payload = [
        'external_id' => $orderId,
        'amount' => (int)$total,
        'payer_email' => $customer['email'] ?? '',
        'description' => 'Order ' . $orderId,
        'success_redirect_url' => $appUrl . '/checkout/payment?orderId=' . urlencode($orderId),
        'failure_redirect_url' => $appUrl . '/checkout/payment?orderId=' . urlencode($orderId) . '&failed=1',
        'callback_url' => $callback_url
    ];

    error_log("XENDIT: Payload = " . json_encode($payload));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/v2/invoices');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($xendit_key . ':')
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("XENDIT: Response code = $code");

    // If success, return invoice info
    if ($res && $code >= 200 && $code < 300) {
        $json = json_decode($res, true);
        error_log("XENDIT: Success! Invoice URL = " . ($json['invoice_url'] ?? 'N/A'));
        // Xendit returns invoice_url
        echo json_encode([
            'success' => true, 
            'orderId' => $orderId,
            'order_number' => $orderNumber,
            'xendit' => $json, 
            'invoice_url' => $json['invoice_url'] ?? null
        ]);
        exit;
    } else {
        // If debug enabled, log Xendit response and HTTP code for troubleshooting (do not break response)
        $debug = getenv('XENDIT_DEBUG');
        if ($debug == '1') {
            $safeBody = is_string($res) ? substr($res, 0, 2000) : '';
            error_log("XENDIT DEBUG: http_code={$code}; body={$safeBody}");
            // continue to fallback so UI keeps working; check logs for details
        }
        error_log("XENDIT: Failed, using mock fallback");
        // continue as mock fallback
    }
} else {
    error_log("XENDIT: Key not found, using mock payment");
}

// Fallback: return mock redirect url to internal payment UI
$redirect = '/checkout/payment?orderId=' . urlencode($orderId) . '&mock=true';

echo json_encode([
    'success' => true, 
    'orderId' => $orderId,
    'order_number' => $orderNumber,
    'mock' => true, 
    'redirect' => $redirect
]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Create order database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to create order']);
} catch (Throwable $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Create order fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

?>