<?php
/**
 * Favorites API Endpoint
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

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

switch ($action) {
    case 'toggle':
        // Toggle favorite status
        $productId = $data['product_id'] ?? null;
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
            exit;
        }
        
        // Check if already favorited
        $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $favorite = $stmt->fetch();
        
        if ($favorite) {
            // Remove from favorites
            $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            
            echo json_encode([
                'success' => true,
                'is_favorite' => false,
                'message' => 'Dihapus dari favorit'
            ]);
        } else {
            // Add to favorites
            $stmt = $db->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            
            echo json_encode([
                'success' => true,
                'is_favorite' => true,
                'message' => 'Ditambahkan ke favorit'
            ]);
        }
        break;
        
    case 'check':
        // Check if product is favorited
        $productId = $data['product_id'] ?? null;
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'ID produk tidak valid']);
            exit;
        }
        
        $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $favorite = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'is_favorite' => $favorite ? true : false
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
