// Real-time notification client using Server-Sent Events (SSE)
import { useNotificationStore } from '@/lib/stores/notification-store';
import { getErrorMessage } from '@/lib/utils';

export class NotificationClient {
  private eventSource: EventSource | null = null;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;
  private reconnectDelay = 1000;
  private userId: string | null = null;
  private isConnecting = false;

  constructor(userId?: string) {
    this.userId = userId || null;
  }

  connect(token: string) {
    if (this.isConnecting || this.eventSource) {
      console.log('[SSE] Already connected or connecting');
      return;
    }

    this.isConnecting = true;
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/DailyCup/webapp/backend/api';
    // Add ngrok bypass as query param since EventSource doesn't support custom headers
    const url = `${apiUrl}/notifications/stream.php?token=${encodeURIComponent(token)}&ngrok-skip-browser-warning=69420`;

    console.log('[SSE] Connecting to:', url);

    try {
      this.eventSource = new EventSource(url);

      this.eventSource.onopen = () => {
        console.log('[SSE] Connection established');
        this.reconnectAttempts = 0;
        this.isConnecting = false;
        useNotificationStore.getState().setConnected(true);
      };

      this.eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          console.log('[SSE] Message received:', data);

          if (data.type === 'ping') {
            // Keep-alive ping, ignore
            return;
          }

          if (data.type === 'notification') {
            const notification = data.notification;
            useNotificationStore.getState().addNotification({
              id: notification.id,
              type: notification.type || 'info',
              title: notification.title,
              message: notification.message,
              data: notification.data || {},
              icon: notification.icon || '',
              action_url: notification.action_url,
              is_read: notification.is_read || false,
              read_at: notification.read_at || null,
              created_at: notification.created_at || new Date().toISOString(),
            });
          }

          if (data.type === 'unread_count') {
            useNotificationStore.getState().setUnreadCount(data.count);
          }
        } catch (error) {
          console.error('[SSE] Error parsing message:', error);
        }
      };

      this.eventSource.onerror = (error) => {
        console.error('[SSE] Connection error:', error);
        this.isConnecting = false;
        useNotificationStore.getState().setConnected(false);

        if (this.eventSource) {
          this.eventSource.close();
          this.eventSource = null;
        }

        this.handleReconnect(token);
      };

      // Listen for specific event types
      this.eventSource.addEventListener('order_update', (event: MessageEvent) => {
        try {
          const data = JSON.parse(event.data as string);
          console.log('[SSE] Order update:', data);
          
          useNotificationStore.getState().addNotification({
            id: Date.now(),
            type: 'order',
            title: 'Order Update',
            message: data.message || 'Your order has been updated',
            data: { orderId: data.order_id, status: data.status },
            icon: '',
            action_url: `/orders/${data.order_id}`,
            is_read: false,
            read_at: null,
            created_at: new Date().toISOString(),
          });
        } catch (error: unknown) {
          console.error('[SSE] Error handling order update:', getErrorMessage(error));
        }
      });

      this.eventSource.addEventListener('payment_update', (event: MessageEvent) => {
        try {
          const data = JSON.parse(event.data as string);
          console.log('[SSE] Payment update:', data);
          
          useNotificationStore.getState().addNotification({
            id: Date.now() + 1,
            type: 'payment',
            title: 'Payment Update',
            message: data.message || 'Payment status updated',
            data: { orderId: data.order_id, amount: data.amount },
            icon: '',
            action_url: `/orders/${data.order_id}`,
            is_read: false,
            read_at: null,
            created_at: new Date().toISOString(),
          });
        } catch (error: unknown) {
          console.error('[SSE] Error handling payment update:', getErrorMessage(error));
        }
      });

      this.eventSource.addEventListener('promo', (event: MessageEvent) => {
        try {
          const data = JSON.parse(event.data as string);
          console.log('[SSE] Promo notification:', data);
          
          useNotificationStore.getState().addNotification({
            id: Date.now() + 2,
            type: 'promo',
            title: data.title || 'Special Offer',
            message: data.message,
            data: {},
            icon: data.image || '',
            action_url: data.url,
            is_read: false,
            read_at: null,
            created_at: new Date().toISOString(),
          });
        } catch (error: unknown) {
          console.error('[SSE] Error handling promo:', getErrorMessage(error));
        }
      });

    } catch (error) {
      console.error('[SSE] Connection failed:', error);
      this.isConnecting = false;
      this.handleReconnect(token);
    }
  }

  private handleReconnect(token: string) {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.log('[SSE] Max reconnection attempts reached');
      return;
    }

    this.reconnectAttempts++;
    const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1); // Exponential backoff

    console.log(`[SSE] Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);

    setTimeout(() => {
      this.connect(token);
    }, delay);
  }

  disconnect() {
    console.log('[SSE] Disconnecting');
    
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }

    this.isConnecting = false;
    this.reconnectAttempts = 0;
    useNotificationStore.getState().setConnected(false);
  }

  isConnected(): boolean {
    return this.eventSource !== null && this.eventSource.readyState === EventSource.OPEN;
  }
}

// Singleton instance
let notificationClient: NotificationClient | null = null;

export function getNotificationClient(userId?: string): NotificationClient {
  if (!notificationClient) {
    notificationClient = new NotificationClient(userId);
  }
  return notificationClient;
}

export function disconnectNotificationClient() {
  if (notificationClient) {
    notificationClient.disconnect();
    notificationClient = null;
  }
}

// Request browser notification permission
export async function requestNotificationPermission(): Promise<NotificationPermission> {
  if (typeof window === 'undefined' || !('Notification' in window)) {
    return 'denied';
  }

  if (Notification.permission === 'granted') {
    return 'granted';
  }

  if (Notification.permission !== 'denied') {
    const permission = await Notification.requestPermission();
    return permission;
  }

  return Notification.permission;
}

// Show browser notification
export function showBrowserNotification(
  title: string,
  options?: NotificationOptions
) {
  if (typeof window === 'undefined' || !('Notification' in window)) {
    return;
  }

  if (Notification.permission === 'granted') {
    new Notification(title, {
      icon: '/assets/image/cup.png',
      badge: '/assets/image/cup.png',
      ...options,
    });
  }
}
