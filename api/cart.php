<?php
/**
 * Cart API Endpoint
 * Handles all cart operations
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Enable error logging for debugging
error_log("Cart API called - Method: " . $_SERVER['REQUEST_METHOD']);

// Require login
if (!isLoggedIn()) {
    error_log("Cart API: User not logged in");
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = $_SESSION['user_id'];

// Initialize cart in session - Load from database if empty
if (!isset($_SESSION['cart'])) {
    loadCartFromDatabase($userId);
    error_log("Cart API: Loaded cart from database for user $userId");
}

$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    error_log("Cart API: Raw input - " . $rawInput);
    
    $data = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Cart API: JSON decode error - " . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    $action = $data['action'] ?? $action;
    error_log("Cart API: Action - " . $action);
}

switch ($action) {
    case 'get':
        // Get cart contents
        echo json_encode([
            'success' => true,
            'cart' => array_values($_SESSION['cart']),
            'count' => getCartCount(),
            'total' => calculateCartTotal()
        ]);
        break;
        
    case 'add':
        // Add item to cart
        $productId = $data['product_id'] ?? null;
        $productName = $data['product_name'] ?? '';
        $price = $data['price'] ?? 0;
        $size = $data['size'] ?? null;
        $temperature = $data['temperature'] ?? null;
        $quantity = $data['quantity'] ?? 1;
        
        error_log("Cart API Add: productId=$productId, name=$productName, price=$price, size=$size, temp=$temperature, qty=$quantity");
        
        if (!$productId || !$productName || $price <= 0) {
            error_log("Cart API Add: Invalid data");
            echo json_encode(['success' => false, 'message' => 'Data produk tidak lengkap']);
            exit;
        }
        
        // Create cart key
        $cartKey = $productId . '_' . ($size ?? '') . '_' . ($temperature ?? '');
        error_log("Cart API Add: cartKey=$cartKey");
        
        // Check if item already in cart
        $found = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['cart_key'] === $cartKey) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
                $found = true;
                error_log("Cart API Add: Updated existing item at index $key");
                break;
            }
        }
        
        if (!$found) {
            // Get product image
            $db = getDB();
            $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            $image = $product['image'] ?? '';

            $newItem = [
                'cart_key' => $cartKey,
                'product_id' => $productId,
                'product_name' => $productName,
                'price' => $price,
                'size' => $size,
                'temperature' => $temperature,
                'quantity' => $quantity,
                'image' => $image
            ];
            $_SESSION['cart'][] = $newItem;
            
            // Save to database (PERSISTENT CART)
            saveCartItemToDatabase($userId, $newItem);
            
            error_log("Cart API Add: Added new item to cart and database");
        } else {
            // Update database for existing item
            updateCartItemQuantityInDatabase($userId, $cartKey, $_SESSION['cart'][$key]['quantity']);
            error_log("Cart API Add: Updated quantity in database");
        }
        
        $cartCount = getCartCount();
        error_log("Cart API Add: Success - cart count=$cartCount");
        
        echo json_encode([
            'success' => true,
            'message' => 'Produk ditambahkan ke keranjang',
            'cart' => array_values($_SESSION['cart']),
            'count' => $cartCount
        ]);
        break;
        
    case 'update':
        // Update cart item quantity
        $cartKey = $data['cart_key'] ?? null;
        $quantity = intval($data['quantity'] ?? 1);
        
        if ($cartKey === null || !isset($_SESSION['cart'][$cartKey])) {
            echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
            exit;
        }
        
        if ($quantity < 1) {
            unset($_SESSION['cart'][$cartKey]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
            
            // Remove from database (PERSISTENT CART)
            removeCartItemFromDatabase($userId, $cartKey);
        } else {
            $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
            
            // Update database (PERSISTENT CART)
            updateCartItemQuantityInDatabase($userId, $cartKey, $quantity);
        }
        
        echo json_encode([
            'success' => true,
            'cart' => $_SESSION['cart'],
            'count' => getCartCount()
        ]);
        break;
        
    case 'remove':
        // Remove item from cart
        $cartKey = $data['cart_key'] ?? null;
        
        if ($cartKey === null || !isset($_SESSION['cart'][$cartKey])) {
            echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
            exit;
        }
        
        unset($_SESSION['cart'][$cartKey]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index
        
        // Remove from database (PERSISTENT CART)
        removeCartItemFromDatabase($userId, $cartKey);
        
        echo json_encode([
            'success' => true,
            'message' => 'Item dihapus dari keranjang',
            'cart' => $_SESSION['cart'],
            'count' => getCartCount()
        ]);
        break;
        
    case 'clear':
        // Clear from database (PERSISTENT CART)
        clearCartFromDatabase($userId);
        
        // Clear entire cart
        $_SESSION['cart'] = [];
        
        echo json_encode([
            'success' => true,
            'message' => 'Keranjang dikosongkan',
            'cart' => [],
            'count' => 0
        ]);
        break;
        
    case 'apply_discount':
        // Apply discount code (Phase 3: Enhanced Promo System)
        $code = $data['code'] ?? '';
        
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Kode diskon tidak valid']);
            exit;
        }
        
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Silakan login untuk menggunakan kode diskon']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $cartTotal = calculateCartTotal();
        $cartItems = $_SESSION['cart'] ?? [];

        $validation = validateDiscount($code, $userId, $cartTotal, $cartItems);

        if (!$validation['valid']) {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
            exit;
        }

        $discount = $validation['discount'];
        
        // Calculate discount amount
        if ($discount['discount_type'] === 'percentage') {
            $discountAmount = $cartTotal * ($discount['discount_value'] / 100);
            if (!empty($discount['max_discount'])) {
                $discountAmount = min($discountAmount, (float)$discount['max_discount']);
            }
        } else {
            $discountAmount = (float)$discount['discount_value'];
        }
        
        $_SESSION['discount_id'] = $discount['id'];
        $_SESSION['discount_code'] = $code;
        $_SESSION['discount_amount'] = $discountAmount;
        
        echo json_encode([
            'success' => true,
            'message' => 'Kode diskon berhasil diterapkan',
            'discount_amount' => $discountAmount
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
