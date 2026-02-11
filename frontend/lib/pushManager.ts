/**
 * Push Notification Manager
 * Handles Web Push API subscriptions and permissions
 */

const VAPID_PUBLIC_KEY = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY || '';

export interface PushSubscriptionData {
  endpoint: string;
  keys: {
    p256dh: string;
    auth: string;
  };
}

export class PushNotificationManager {
  private static instance: PushNotificationManager;
  private registration: ServiceWorkerRegistration | null = null;

  private constructor() {}

  static getInstance(): PushNotificationManager {
    if (!PushNotificationManager.instance) {
      PushNotificationManager.instance = new PushNotificationManager();
    }
    return PushNotificationManager.instance;
  }

  /**
   * Check if push notifications are supported
   */
  isSupported(): boolean {
    return (
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    );
  }

  /**
   * Get current notification permission
   */
  getPermission(): NotificationPermission {
    if (!this.isSupported()) return 'denied';
    return Notification.permission;
  }

  /**
   * Request notification permission
   */
  async requestPermission(): Promise<NotificationPermission> {
    if (!this.isSupported()) {
      throw new Error('Push notifications are not supported');
    }

    const permission = await Notification.requestPermission();
    return permission;
  }

  /**
   * Initialize service worker registration
   */
  async initialize(registration: ServiceWorkerRegistration): Promise<void> {
    this.registration = registration;
  }

  /**
   * Convert VAPID public key to Uint8Array
   */
  private urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  /**
   * Subscribe to push notifications
   */
  async subscribe(): Promise<PushSubscriptionData | null> {
    if (!this.isSupported()) {
      throw new Error('Push notifications are not supported');
    }

    if (!this.registration) {
      throw new Error('Service worker not registered');
    }

    if (!VAPID_PUBLIC_KEY) {
      throw new Error('VAPID public key not configured');
    }

    try {
      // Check permission
      const permission = await this.requestPermission();
      if (permission !== 'granted') {
        console.log('Push notification permission denied');
        return null;
      }

      // Subscribe to push
      const subscription = await this.registration.pushManager.subscribe({
        userVisibleOnly: true,
        // Cast to BufferSource to satisfy typing expected by the browser
        applicationServerKey: this.urlBase64ToUint8Array(VAPID_PUBLIC_KEY) as unknown as BufferSource,
      });

      // Convert to JSON
      const subscriptionData = subscription.toJSON();
      
      return {
        endpoint: subscriptionData.endpoint || '',
        keys: {
          p256dh: subscriptionData.keys?.p256dh || '',
          auth: subscriptionData.keys?.auth || '',
        },
      };
    } catch (error) {
      console.error('Failed to subscribe to push notifications:', error);
      throw error;
    }
  }

  /**
   * Unsubscribe from push notifications
   */
  async unsubscribe(): Promise<boolean> {
    if (!this.registration) {
      return false;
    }

    try {
      const subscription = await this.registration.pushManager.getSubscription();
      if (subscription) {
        await subscription.unsubscribe();
        return true;
      }
      return false;
    } catch (error) {
      console.error('Failed to unsubscribe from push notifications:', error);
      return false;
    }
  }

  /**
   * Get current subscription
   */
  async getSubscription(): Promise<PushSubscription | null> {
    if (!this.registration) {
      return null;
    }

    try {
      return await this.registration.pushManager.getSubscription();
    } catch (error) {
      console.error('Failed to get push subscription:', error);
      return null;
    }
  }

  /**
   * Check if user is subscribed
   */
  async isSubscribed(): Promise<boolean> {
    const subscription = await this.getSubscription();
    return subscription !== null;
  }

  /**
   * Test notification (requires permission)
   */
  async showTestNotification(): Promise<void> {
    if (!this.isSupported()) {
      throw new Error('Notifications are not supported');
    }

    const permission = this.getPermission();
    if (permission !== 'granted') {
      throw new Error('Notification permission not granted');
    }

    if (!this.registration) {
      throw new Error('Service worker not registered');
    }

    const notifOptions = {
      body: 'Push notifications are working! 🎉',
      icon: '/assets/image/cup.png',
      badge: '/logo/cup-badge.png',
      tag: 'test-notification',
      vibrate: [200, 100, 200],
      data: {
        url: '/menu',
      },
    } as NotificationOptions & { vibrate?: number[] };

    await this.registration.showNotification('DailyCup Test', notifOptions);
  }
}

// Export singleton instance
export const pushManager = PushNotificationManager.getInstance();
export default pushManager;
