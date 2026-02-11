<?php
/**
 * WebSocket Broadcast Utility
 * Sends HTTP request to trigger WebSocket broadcast
 * Use this after updating order status
 */

function broadcastOrderUpdate($orderId, $updateData) {
    // For simplicity, we'll use a REST endpoint that the WebSocket server polls
    // In production, use Redis pub/sub or ZMQ for better performance
    
    $webhookUrl = 'http://localhost:8080/broadcast'; // WebSocket internal endpoint
    
    // Alternative: Store in Redis or database for WebSocket server to poll
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Store broadcast queue in database
        $stmt = $pdo->prepare("
            INSERT INTO websocket_broadcast_queue 
            (order_id, payload, created_at) 
            VALUES (?, ?, NOW())
        ");
        
        $stmt->execute([
            $orderId,
            json_encode($updateData)
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to queue WebSocket broadcast: " . $e->getMessage());
        return false;
    }
}

/**
 * Simple polling-based broadcast (for systems without Redis)
 * The WebSocket server will poll this table periodically
 */
function createBroadcastQueueTable($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS websocket_broadcast_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(50) NOT NULL,
        payload JSON NOT NULL,
        is_broadcasted BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        broadcasted_at DATETIME DEFAULT NULL,
        INDEX idx_order_id (order_id),
        INDEX idx_broadcasted (is_broadcasted, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $pdo->exec($sql);
}
?>
