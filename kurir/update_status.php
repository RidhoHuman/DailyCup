<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['kurir_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$kurirId = $_SESSION['kurir_id'];
$orderId = intval($_POST['order_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

if (!$orderId || !$newStatus) {
    header('Location: index.php?error=invalid');
    exit;
}

$db = getDB();

// Verify order belongs to this kurir
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND kurir_id = ?");
$stmt->execute([$orderId, $kurirId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: index.php?error=not_found');
    exit;
}

// Validate status transition
$allowedTransitions = [
    'confirmed' => 'ready',
    'processing' => 'ready',
    'ready' => 'delivering',
    'delivering' => 'completed'
];

if (!isset($allowedTransitions[$order['status']]) || $allowedTransitions[$order['status']] !== $newStatus) {
    header('Location: index.php?error=invalid_status');
    exit;
}

$db->beginTransaction();

try {
    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    // Update timestamp fields
    if ($newStatus === 'delivering') {
        $stmt = $db->prepare("UPDATE orders SET pickup_time = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
    } elseif ($newStatus === 'completed') {
        $stmt = $db->prepare("UPDATE orders SET delivery_time = NOW() WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Update kurir total deliveries
        $stmt = $db->prepare("UPDATE kurir SET total_deliveries = total_deliveries + 1 WHERE id = ?");
        $stmt->execute([$kurirId]);
        
        // Give loyalty points to customer
        require_once __DIR__ . '/../includes/functions.php';
        $points = calculateLoyaltyPoints($order['final_amount']);
        if ($points > 0) {
            updateUserPoints(
                $order['user_id'], 
                $points, 
                'earned', 
                $orderId, 
                "Pembelian senilai " . formatCurrency($order['final_amount'])
            );
        }
    }
    
    // Log delivery history
    $stmt = $db->prepare("INSERT INTO delivery_history (order_id, kurir_id, status, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$orderId, $kurirId, $newStatus, "Status updated by kurir"]);
    
    // Send notification to customer
    $statusMessages = [
        'ready' => 'Pesanan Anda sudah siap!',
        'delivering' => 'Kurir sedang dalam perjalanan mengantar pesanan Anda!',
        'completed' => 'Pesanan telah sampai! Terima kasih telah berbelanja di DailyCup.'
    ];
    
    if (isset($statusMessages[$newStatus])) {
        createNotification(
            $order['user_id'],
            "Status Pesanan Diperbarui",
            $statusMessages[$newStatus] . " Order #{$order['order_number']}",
            'order_update',
            $orderId
        );
    }
    
    // Update kurir status
    if ($newStatus === 'completed') {
        // Check if kurir has other active orders
        $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE kurir_id = ? AND status IN ('confirmed', 'processing', 'ready', 'delivering')");
        $stmt->execute([$kurirId]);
        $activeCount = $stmt->fetchColumn();
        
        if ($activeCount == 0) {
            // Set kurir to available
            $stmt = $db->prepare("UPDATE kurir SET status = 'available' WHERE id = ?");
            $stmt->execute([$kurirId]);
        }
    }
    
    $db->commit();
    header('Location: index.php?success=1');
    exit;
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Update status error: " . $e->getMessage());
    header('Location: index.php?error=failed');
    exit;
}
