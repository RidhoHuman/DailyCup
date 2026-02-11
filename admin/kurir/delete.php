<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/kurir/index.php');
    exit;
}

$db = getDB();
$kurirId = intval($_POST['id'] ?? 0);

if (!$kurirId) {
    header('Location: ' . SITE_URL . '/admin/kurir/index.php?error=invalid_id');
    exit;
}

try {
    // Check if kurir has active orders
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders 
                         WHERE kurir_id = ? 
                         AND status IN ('confirmed', 'processing', 'ready', 'delivering')");
    $stmt->execute([$kurirId]);
    $activeOrders = $stmt->fetchColumn();
    
    if ($activeOrders > 0) {
        header('Location: ' . SITE_URL . '/admin/kurir/edit.php?id=' . $kurirId . '&error=has_active_orders');
        exit;
    }
    
    // Soft delete: Set is_active to 0 instead of deleting
    $stmt = $db->prepare("UPDATE kurir SET is_active = 0, status = 'offline' WHERE id = ?");
    $stmt->execute([$kurirId]);
    
    header('Location: ' . SITE_URL . '/admin/kurir/index.php?success=deleted');
    exit;
    
} catch (Exception $e) {
    header('Location: ' . SITE_URL . '/admin/kurir/edit.php?id=' . $kurirId . '&error=' . urlencode($e->getMessage()));
    exit;
}
?>
