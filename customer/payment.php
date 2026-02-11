<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/customer/checkout.php');
    exit;
}

// Verify CSRF Token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token');
}

$db = getDB();
$userId = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: ' . SITE_URL . '/customer/cart.php');
    exit;
}

// Get form data
$deliveryMethod = sanitizeInput($_POST['delivery_method'] ?? 'dine-in');
$deliveryAddress = sanitizeInput($_POST['delivery_address'] ?? '');
$customerNotes = sanitizeInput($_POST['customer_notes'] ?? '');
$paymentMethodId = intval($_POST['payment_method'] ?? 0);
$useLoyaltyPoints = isset($_POST['use_loyalty_points']) && $_POST['use_loyalty_points'] == '1';
$pointsToRedeem = intval($_POST['points_to_redeem'] ?? 0);

// Get payment method details
$stmt = $db->prepare("SELECT method_name FROM payment_methods WHERE id = ?");
$stmt->execute([$paymentMethodId]);
$paymentMethodName = $stmt->fetchColumn() ?: 'Unknown';

// Get loyalty settings
$stmt = $db->query("SELECT * FROM loyalty_settings WHERE is_active = 1 LIMIT 1");
$loyaltySettings = $stmt->fetch();
$rupiahPerPoint = $loyaltySettings ? $loyaltySettings['rupiah_per_point'] : 100;
$minPointsRedeem = $loyaltySettings ? $loyaltySettings['min_points_redeem'] : 100;

// Get user's current loyalty points
$currentUser = getCurrentUser();
$userPoints = $currentUser['loyalty_points'];

// Calculate totals
$subtotal = calculateCartTotal();
$discountAmount = $_SESSION['discount_amount'] ?? 0;
$loyaltyDiscount = 0;

// Validate and apply loyalty points
if ($useLoyaltyPoints && $pointsToRedeem >= $minPointsRedeem && $pointsToRedeem <= $userPoints) {
    $loyaltyDiscount = $pointsToRedeem * $rupiahPerPoint;
    $discountAmount += $loyaltyDiscount;
}

$finalAmount = $subtotal - $discountAmount;

// Generate order number
$orderNumber = ORDER_PREFIX . date('YmdHis') . $userId;

try {
    $db->beginTransaction();

    // Insert into orders table
    $stmt = $db->prepare("INSERT INTO orders (user_id, order_number, total_amount, discount_amount, final_amount, delivery_method, delivery_address, customer_notes, payment_method, status, payment_status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')");
    
    $stmt->execute([
        $userId,
        $orderNumber,
        $subtotal,
        $discountAmount,
        $finalAmount,
        $deliveryMethod,
        $deliveryAddress,
        $customerNotes,
        $paymentMethodName
    ]);
    
    $orderId = $db->lastInsertId();

    // Insert order items
    $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, size, temperature, quantity, unit_price, subtotal) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($cart as $item) {
        $itemSubtotal = $item['price'] * $item['quantity'];
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['size'],
            $item['temperature'],
            $item['quantity'],
            $item['price'],
            $itemSubtotal
        ]);
    }

    // Deduct loyalty points if used
    if ($useLoyaltyPoints && $pointsToRedeem > 0 && $loyaltyDiscount > 0) {
        $newPoints = $userPoints - $pointsToRedeem;
        $stmt = $db->prepare("UPDATE users SET loyalty_points = ? WHERE id = ?");
        $stmt->execute([$newPoints, $userId]);
        
        // Log loyalty transaction
        $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, transaction_type, points, description, order_id) 
                             VALUES (?, 'redeemed', ?, ?, ?)");
        $stmt->execute([
            $userId,
            $pointsToRedeem,
            "Penukaran poin untuk diskon pesanan #{$orderNumber}",
            $orderId
        ]);
        
        // Notification for points redemption
        createNotification(
            $userId,
            "Poin Loyalty Digunakan",
            "Anda menggunakan {$pointsToRedeem} poin untuk diskon " . formatCurrency($loyaltyDiscount),
            'points_redeemed',
            $orderId
        );
    }
    
    // Log discount usage (Phase 3: Enhanced Promo System)
    if (isset($_SESSION['discount_id'])) {
        $discountId = $_SESSION['discount_id'];
        
        // Log to discount_usage
        $stmt = $db->prepare("INSERT INTO discount_usage (discount_id, user_id, order_id) VALUES (?, ?, ?)");
        $stmt->execute([$discountId, $userId, $orderId]);
        
        // Increment global usage count
        $stmt = $db->prepare("UPDATE discounts SET usage_count = usage_count + 1 WHERE id = ?");
        $stmt->execute([$discountId]);
    }

    // Clear cart and discount
    unset($_SESSION['cart']);
    unset($_SESSION['discount_id']);
    unset($_SESSION['discount_amount']);
    unset($_SESSION['discount_code']);
    
    // Clear cart from database (PERSISTENT CART)
    clearCartFromDatabase($userId);

    // CREATE NOTIFICATION for customer - Order Created
    createNotification(
        $userId, 
        "Pesanan Berhasil Dibuat", 
        "Pesanan #{$orderNumber} telah berhasil dibuat. Total pembayaran: " . formatCurrency($finalAmount), 
        'order_created', 
        $orderId
    );
    
    // CREATE NOTIFICATION for admin - New Order
    createAdminNotification(
        $orderId,
        "Pesanan Baru Masuk!",
        "Pesanan baru #{$orderNumber} dari customer. Total: " . formatCurrency($finalAmount) . ". Silakan konfirmasi pembayaran.",
        'new_order'
    );

    $db->commit();
    
    // AUTO-APPROVE if cash payment (no proof needed)
    if ($paymentMethod === 'cash') {
        require_once __DIR__ . '/../api/auto_assign_kurir.php';
        
        // Mark as paid and confirmed immediately
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Auto-assign kurir for delivery orders
        if ($deliveryMethod === 'delivery') {
            autoAssignKurir($orderId);
        }
        
        // Notify customer
        createNotification(
            $userId,
            "Pesanan Dikonfirmasi!",
            "Order #{$orderNumber} telah dikonfirmasi dan sedang diproses.",
            'order_update',
            $orderId
        );
    }

    // Redirect to orders page with success message
    header('Location: ' . SITE_URL . '/customer/orders.php?success=1');
    exit;

} catch (Exception $e) {
    $db->rollBack();
    die("Terjadi kesalahan saat memproses pesanan: " . $e->getMessage());
}
