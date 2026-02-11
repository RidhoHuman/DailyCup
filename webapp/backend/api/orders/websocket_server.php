<?php
/**
 * DailyCup WebSocket Server
 * Real-time order tracking updates
 * 
 * Run: php backend/api/orders/websocket_server.php
 * Connect: ws://localhost:8080
 * 
 * Note: This requires Ratchet library
 * Install: composer require cboden/ratchet
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class OrderTrackingServer implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions; // order_id => [conn1, conn2, ...]

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        echo "WebSocket server initialized\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        
        $conn->send(json_encode([
            'type' => 'connected',
            'message' => 'Connected to DailyCup tracking server',
            'connection_id' => $conn->resourceId
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            $from->send(json_encode(['error' => 'Invalid message format']));
            return;
        }

        switch ($data['type']) {
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;
                
            case 'unsubscribe':
                $this->handleUnsubscribe($from, $data);
                break;
                
            case 'ping':
                $from->send(json_encode(['type' => 'pong', 'timestamp' => time()]));
                break;
                
            default:
                $from->send(json_encode(['error' => 'Unknown message type']));
        }
    }

    protected function handleSubscribe(ConnectionInterface $conn, $data) {
        $orderId = $data['order_id'] ?? null;
        
        if (!$orderId) {
            $conn->send(json_encode(['error' => 'order_id required']));
            return;
        }

        if (!isset($this->subscriptions[$orderId])) {
            $this->subscriptions[$orderId] = new \SplObjectStorage;
        }

        $this->subscriptions[$orderId]->attach($conn);
        
        echo "Connection {$conn->resourceId} subscribed to order {$orderId}\n";
        
        $conn->send(json_encode([
            'type' => 'subscribed',
            'order_id' => $orderId,
            'message' => "Subscribed to order {$orderId}"
        ]));
    }

    protected function handleUnsubscribe(ConnectionInterface $conn, $data) {
        $orderId = $data['order_id'] ?? null;
        
        if (!$orderId || !isset($this->subscriptions[$orderId])) {
            return;
        }

        $this->subscriptions[$orderId]->detach($conn);
        
        if (count($this->subscriptions[$orderId]) === 0) {
            unset($this->subscriptions[$orderId]);
        }

        echo "Connection {$conn->resourceId} unsubscribed from order {$orderId}\n";
    }

    public function onClose(ConnectionInterface $conn) {
        // Remove from all subscriptions
        foreach ($this->subscriptions as $orderId => $subscribers) {
            $subscribers->detach($conn);
            if (count($subscribers) === 0) {
                unset($this->subscriptions[$orderId]);
            }
        }

        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Broadcast update to all subscribers of an order
     * This is called externally when order status changes
     */
    public function broadcastOrderUpdate($orderId, $updateData) {
        if (!isset($this->subscriptions[$orderId])) {
            return;
        }

        $message = json_encode([
            'type' => 'order_update',
            'order_id' => $orderId,
            'data' => $updateData,
            'timestamp' => time()
        ]);

        foreach ($this->subscriptions[$orderId] as $client) {
            $client->send($message);
        }

        echo "Broadcasted update for order {$orderId} to " . count($this->subscriptions[$orderId]) . " clients\n";
    }
}

// Start server
$port = 8080;
echo "Starting WebSocket server on port {$port}...\n";
echo "Clients can connect to: ws://localhost:{$port}\n";
echo "Press Ctrl+C to stop\n\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new OrderTrackingServer()
        )
    ),
    $port
);

$server->run();
?>
