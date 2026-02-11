<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/customer/orders.php');
    exit;
}

$orderId = intval($_POST['order_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$orderId) {
    header('Location: ' . SITE_URL . '/customer/orders.php');
    exit;
}

$db = getDB();

// Verify order belongs to user and is pending
$stmt = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND payment_status = 'pending'");
$stmt->execute([$orderId, $userId]);
if (!$stmt->fetch()) {
    header('Location: ' . SITE_URL . '/customer/order_detail.php?id=' . $orderId . '&error=invalid_order');
    exit;
}

// Handle file upload with secure validation
if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../assets/images/payments/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    $result = secureFileUpload($_FILES['payment_proof'], $uploadDir, $allowedTypes, $maxSize);
    
    if ($result['success']) {
        // Update database
        $stmt = $db->prepare("UPDATE orders SET payment_proof = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$result['filename'], $orderId])) {
            // Log activity
            logActivity('payment_uploaded', 'order', $orderId, [
                'filename' => $result['filename'],
                'size' => $result['size'],
                'type' => $result['type']
            ]);
            
            // AUTO-APPROVE & AUTO-ASSIGN KURIR
            require_once __DIR__ . '/../api/auto_assign_kurir.php';
            autoApproveOrder($orderId);
            
            header('Location: ' . SITE_URL . '/customer/order_detail.php?id=' . $orderId . '&success=1');
            exit;
        }
    } else {
        header('Location: ' . SITE_URL . '/customer/order_detail.php?id=' . $orderId . '&error=' . urlencode($result['error']));
        exit;
    }
}

header('Location: ' . SITE_URL . '/customer/order_detail.php?id=' . $orderId . '&error=upload_failed');
exit;
