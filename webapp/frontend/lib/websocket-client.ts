/**
 * WebSocket Client for Real-Time Order Tracking
 * Connects to WebSocket server for live order updates
 */

export class OrderTrackingWebSocket {
  private ws: WebSocket | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 3000;
  private subscriptions = new Set<string>();
  private listeners = new Map<string, Set<(data: any) => void>>();

  constructor(private url: string = 'ws://localhost:8080') {}

  connect() {
    if (this.ws?.readyState === WebSocket.OPEN) {
      console.log('[WS] Already connected');
      return;
    }

    try {
      this.ws = new WebSocket(this.url);

      this.ws.onopen = () => {
        console.log('[WS] Connected to tracking server');
        this.reconnectAttempts = 0;
        
        // Re-subscribe to previous subscriptions
        this.subscriptions.forEach(orderId => {
          this.subscribeToOrder(orderId);
        });
      };

      this.ws.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          this.handleMessage(data);
        } catch (error) {
          console.error('[WS] Failed to parse message:', error);
        }
      };

      this.ws.onclose = () => {
        console.log('[WS] Connection closed');
        this.attemptReconnect();
      };

      this.ws.onerror = (error) => {
        console.error('[WS] Error:', error);
      };

    } catch (error) {
      console.error('[WS] Failed to connect:', error);
      this.attemptReconnect();
    }
  }

  private attemptReconnect() {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('[WS] Max reconnection attempts reached');
      return;
    }

    this.reconnectAttempts++;
    console.log(`[WS] Reconnecting... (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

    setTimeout(() => {
      this.connect();
    }, this.reconnectDelay);
  }

  private handleMessage(data: any) {
    console.log('[WS] Message received:', data);

    switch (data.type) {
      case 'connected':
        console.log('[WS]', data.message);
        break;

      case 'subscribed':
        console.log(`[WS] Subscribed to order ${data.order_id}`);
        break;

      case 'order_update':
        this.notifyListeners(data.order_id, data.data);
        break;

      case 'pong':
        console.log('[WS] Pong received');
        break;

      default:
        console.log('[WS] Unknown message type:', data.type);
    }
  }

  subscribeToOrder(orderId: string) {
    if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
      console.warn('[WS] Not connected, queuing subscription');
      this.subscriptions.add(orderId);
      return;
    }

    this.subscriptions.add(orderId);
    this.ws.send(JSON.stringify({
      type: 'subscribe',
      order_id: orderId
    }));
  }

  unsubscribeFromOrder(orderId: string) {
    if (!this.ws || this.ws.readyState !== WebSocket.OPEN) {
      return;
    }

    this.subscriptions.delete(orderId);
    this.ws.send(JSON.stringify({
      type: 'unsubscribe',
      order_id: orderId
    }));
  }

  onOrderUpdate(orderId: string, callback: (data: any) => void) {
    if (!this.listeners.has(orderId)) {
      this.listeners.set(orderId, new Set());
    }
    this.listeners.get(orderId)!.add(callback);

    // Return cleanup function
    return () => {
      this.listeners.get(orderId)?.delete(callback);
    };
  }

  private notifyListeners(orderId: string, data: any) {
    const callbacks = this.listeners.get(orderId);
    if (callbacks) {
      callbacks.forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error('[WS] Error in listener callback:', error);
        }
      });
    }
  }

  ping() {
    if (this.ws?.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ type: 'ping' }));
    }
  }

  disconnect() {
    this.subscriptions.clear();
    this.listeners.clear();
    
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
  }

  isConnected(): boolean {
    return this.ws?.readyState === WebSocket.OPEN;
  }
}

// Singleton instance
let wsInstance: OrderTrackingWebSocket | null = null;

export function getOrderTrackingWebSocket(): OrderTrackingWebSocket {
  if (!wsInstance) {
    // Use environment variable for WebSocket URL in production
    const wsUrl = process.env.NEXT_PUBLIC_WS_URL || 'ws://localhost:8080';
    wsInstance = new OrderTrackingWebSocket(wsUrl);
  }
  return wsInstance;
}

export default OrderTrackingWebSocket;
