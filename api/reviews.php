<?php
/**
 * Review API Endpoint
 */

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        $action = $data['action'] ?? $action;
    } else {
        // Form data
        $data = $_POST;
    }
}

switch ($action) {
    case 'submit':
        // Submit new review
        $orderId = intval($data['order_id'] ?? 0);
        $productId = intval($data['product_id'] ?? 0);
        $rating = intval($data['rating'] ?? 0);
        $reviewText = sanitizeInput($data['review_text'] ?? '');
        
        if (!$orderId || !$productId || $rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap atau tidak valid']);
            exit;
        }
        
        // Verify order belongs to user and is completed
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'completed'");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan atau belum selesai']);
            exit;
        }
        
        // Check if already reviewed
        $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $stmt->execute([$userId, $productId, $orderId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan review untuk produk ini']);
            exit;
        }
        
        // Insert review
        try {
            $stmt = $db->prepare("INSERT INTO reviews (user_id, product_id, order_id, rating, review_text) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $productId, $orderId, $rating, $reviewText]);
            
            // Give bonus points for review
            updateUserPoints($userId, 10, 'earned', $orderId, 'Bonus review produk');
            
            echo json_encode([
                'success' => true, 
                'message' => 'Review berhasil dikirim. Terima kasih! Anda mendapat 10 poin bonus.'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan review: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_product_reviews':
        // Get reviews for a product
        $productId = intval($_GET['product_id'] ?? 0);
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT r.*, u.name as user_name, u.profile_image 
                             FROM reviews r 
                             JOIN users u ON r.user_id = u.id 
                             WHERE r.product_id = ? AND r.is_approved = 1 
                             ORDER BY r.created_at DESC");
        $stmt->execute([$productId]);
        $reviews = $stmt->fetchAll();
        
        // Calculate average rating
        $stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                             FROM reviews 
                             WHERE product_id = ? AND is_approved = 1");
        $stmt->execute([$productId]);
        $stats = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'reviews' => $reviews,
            'avg_rating' => round($stats['avg_rating'], 1),
            'total_reviews' => $stats['total_reviews']
        ]);
        break;
        
    case 'can_review':
        // Check if user can review a product in an order
        $orderId = intval($_GET['order_id'] ?? 0);
        $productId = intval($_GET['product_id'] ?? 0);
        
        if (!$orderId || !$productId) {
            echo json_encode(['can_review' => false]);
            exit;
        }
        
        // Check if order is completed
        $stmt = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'completed'");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['can_review' => false, 'reason' => 'Order belum selesai']);
            exit;
        }
        
        // Check if already reviewed
        $stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $stmt->execute([$userId, $productId, $orderId]);
        $existingReview = $stmt->fetch();
        
        echo json_encode([
            'can_review' => !$existingReview,
            'reason' => $existingReview ? 'Sudah di-review' : null
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
}
