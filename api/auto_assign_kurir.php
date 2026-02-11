<?php
require_once __DIR__ . '/../includes/functions.php';

/**
 * Auto-assign kurir to an order
 * Returns kurir_id if successful, false otherwise
 */
function autoAssignKurir($orderId) {
    $db = getDB();
    
    // Get order details
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order || $order['delivery_method'] !== 'delivery') {
        return false; // Only assign kurir for delivery orders
    }
    
    // Find available kurir with least active orders (load balancing)
    $stmt = $db->prepare("SELECT k.id, k.name, COUNT(o.id) as active_orders
                         FROM kurir k
                         LEFT JOIN orders o ON k.id = o.kurir_id 
                            AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                         WHERE k.status = 'available' AND k.is_active = 1
                         GROUP BY k.id
                         ORDER BY active_orders ASC, k.rating DESC
                         LIMIT 1");
    $stmt->execute();
    $kurir = $stmt->fetch();
    
    if (!$kurir) {
        // No available kurir, try to find 'busy' kurir with least orders
        $stmt = $db->prepare("SELECT k.id, k.name, COUNT(o.id) as active_orders
                             FROM kurir k
                             LEFT JOIN orders o ON k.id = o.kurir_id 
                                AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                             WHERE k.status IN ('available', 'busy') AND k.is_active = 1
                             GROUP BY k.id
                             ORDER BY active_orders ASC, k.rating DESC
                             LIMIT 1");
        $stmt->execute();
        $kurir = $stmt->fetch();
    }
    
    if (!$kurir) {
        return false; // No kurir available at all
    }
    
    // Assign kurir to order
    $stmt = $db->prepare("UPDATE orders SET kurir_id = ?, assigned_at = NOW() WHERE id = ?");
    $stmt->execute([$kurir['id'], $orderId]);
    
    // Update kurir status to busy if they have active orders
    if ($kurir['active_orders'] >= 2) { // If kurir has 2+ orders, mark as busy
        $stmt = $db->prepare("UPDATE kurir SET status = 'busy' WHERE id = ?");
        $stmt->execute([$kurir['id']]);
    }
    
    // Log delivery history
    $stmt = $db->prepare("INSERT INTO delivery_history (order_id, kurir_id, status, notes) 
                         VALUES (?, ?, 'assigned', 'Auto-assigned by system')");
    $stmt->execute([$orderId, $kurir['id']]);
    
    // Send notification to customer
    createNotification(
        $order['user_id'],
        "Kurir Assigned!",
        "Order #{$order['order_number']} telah ditugaskan ke kurir: {$kurir['name']}. Pesanan Anda akan segera diantar!",
        'order_update',
        $orderId
    );
    
    // Send notification to kurir
    $stmt = $db->prepare("SELECT u.name as customer_name FROM users u WHERE u.id = ?");
    $stmt->execute([$order['user_id']]);
    $customer = $stmt->fetch();
    
    $stmt = $db->prepare("INSERT INTO kurir_notifications 
                         (kurir_id, type, title, message, order_id) 
                         VALUES (?, 'new_delivery', 'Pesanan Baru!', ?, ?)");
    $kurirMessage = "Anda mendapat pesanan baru #{$order['order_number']} dari {$customer['customer_name']}. Segera ambil pesanan!";
    $stmt->execute([$kurir['id'], $kurirMessage, $orderId]);
    
    return $kurir['id'];
}

/**
 * Auto-approve order with payment
 * Automatically confirms and assigns kurir
 */
function autoApproveOrder($orderId) {
    $db = getDB();
    
    // Get order details
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order || $order['status'] !== 'pending') {
        return false;
    }
    
    // Check if payment is uploaded (if required)
    if ($order['payment_method'] !== 'cash' && !$order['payment_proof']) {
        return false; // Need payment proof first
    }
    
    $db->beginTransaction();
    
    try {
        // Auto-approve payment
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$orderId]);
        
        // Calculate preparation time based on number of items
        $stmt = $db->prepare("SELECT SUM(quantity) as total_items FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $totalItems = $stmt->fetchColumn() ?: 1;
        
        // Base time 20 minutes + 3 minutes per additional item
        $preparationTime = 20 + (($totalItems - 1) * 3);
        $estimatedReadyAt = date('Y-m-d H:i:s', strtotime("+{$preparationTime} minutes"));
        
        // Update order status to confirmed with preparation time
        $stmt = $db->prepare("UPDATE orders SET 
                             status = 'confirmed', 
                             preparation_time = ?,
                             estimated_ready_at = ?,
                             updated_at = NOW() 
                             WHERE id = ?");
        $stmt->execute([$preparationTime, $estimatedReadyAt, $orderId]);
        
        // Send confirmation notification
        createNotification(
            $order['user_id'],
            "Pesanan Dikonfirmasi!",
            "Order #{$order['order_number']} telah dikonfirmasi. Estimasi siap: {$preparationTime} menit.",
            'order_update',
            $orderId
        );
        
        // Notify admin - order confirmed, start preparation
        createAdminNotification(
            $orderId,
            "Mulai Persiapan Pesanan!",
            "Pesanan #{$order['order_number']} telah dikonfirmasi. Estimasi siap: " . date('H:i', strtotime($estimatedReadyAt)),
            'order_confirmed'
        );
        
        // Auto-assign kurir if delivery order
        if ($order['delivery_method'] === 'delivery') {
            $kurirId = autoAssignKurir($orderId);
            
            if ($kurirId) {
                // Get kurir info
                $stmt = $db->prepare("SELECT name, phone FROM kurir WHERE id = ?");
                $stmt->execute([$kurirId]);
                $kurirInfo = $stmt->fetch();
                
                // Calculate kurir standby time (15 minutes before ready)
                $kurirStandbyTime = date('H:i', strtotime($estimatedReadyAt . ' -15 minutes'));
                
                // Notify kurir - need to standby at store
                $stmt = $db->prepare("UPDATE kurir_notifications 
                                     SET message = ? 
                                     WHERE order_id = ? AND kurir_id = ?
                                     ORDER BY created_at DESC LIMIT 1");
                $newMessage = "Pesanan #{$order['order_number']} telah dikonfirmasi! Harap standby di toko paling lambat jam {$kurirStandbyTime}. Estimasi pesanan siap: " . date('H:i', strtotime($estimatedReadyAt));
                $stmt->execute([$newMessage, $orderId, $kurirId]);
                
                // Notify admin - kurir assigned
                createAdminNotification(
                    $orderId,
                    "Kurir Ditugaskan",
                    "Kurir {$kurirInfo['name']} ({$kurirInfo['phone']}) telah ditugaskan untuk pesanan #{$order['order_number']}",
                    'kurir_assigned'
                );
                
                // Update status to processing (being prepared)
                $stmt = $db->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
                $stmt->execute([$orderId]);
            }
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Auto-approve failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check and auto-approve pending orders with payment proof
 * Can be called via cron job or manually
 */
function processPendingOrders() {
    $db = getDB();
    
    // Get pending orders with payment proof
    $stmt = $db->query("SELECT id FROM orders 
                       WHERE status = 'pending' 
                       AND payment_status = 'pending'
                       AND payment_proof IS NOT NULL
                       AND payment_proof != ''
                       ORDER BY created_at ASC");
    $orders = $stmt->fetchAll();
    
    $processed = 0;
    foreach ($orders as $order) {
        if (autoApproveOrder($order['id'])) {
            $processed++;
        }
    }
    
    return $processed;
}

// Manual trigger endpoint (for testing or admin use)
if (isset($_GET['process_pending'])) {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        die('Access denied');
    }
    
    $processed = processPendingOrders();
    echo "✅ Processed {$processed} pending orders.";
}
