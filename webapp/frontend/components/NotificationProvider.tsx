'use client';

import { useEffect, useState } from 'react';
import { getNotificationClient, disconnectNotificationClient, requestNotificationPermission } from '@/lib/notificationClient';
import { useAuthStore } from '@/lib/stores/auth-store';
import pushManager from '@/lib/pushManager';
import api from '@/lib/api-client';

interface PreferenceResponse {
  data?: {
    preferences?: {
      push_enabled?: boolean;
      [key: string]: any;
    };
  };
}

export default function NotificationProvider({ children }: { children: React.ReactNode }) {
  const { token, user } = useAuthStore();
  const [pushSetupAttempted, setPushSetupAttempted] = useState(false);

  useEffect(() => {
    if (!token || !user) {
      // Disconnect if user logs out
      disconnectNotificationClient();
      return;
    }

    // Request browser notification permission
    requestNotificationPermission();

    // TODO: SSE connection disabled temporarily due to stream.php accessibility issues
    // Will re-enable after fixing Apache SSE configuration
    // Connect to SSE stream
    // const client = getNotificationClient(user.id?.toString());
    // client.connect(token);

    console.log('[Notifications] Using polling mode (SSE disabled temporarily)');

    // Setup push notifications (only once per session)
    if (!pushSetupAttempted) {
      setupPushNotifications();
      setPushSetupAttempted(true);
    }

    // Cleanup on unmount or token change
    return () => {
      // client.disconnect();
    };
  }, [token, user]);

  const setupPushNotifications = async () => {
    try {
      // Check if push is supported
      if (!pushManager.isSupported()) {
        console.log('Push notifications not supported');
        return;
      }

      // CRITICAL: Always unsubscribe from ALL existing subscriptions first
      // This completely clears any VAPID key conflicts
      try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        
        if (subscription) {
          console.log('[Notifications] Found existing subscription, force unsubscribing...');
          const unsubscribed = await subscription.unsubscribe();
          if (unsubscribed) {
            console.log('[Notifications] ✓ Successfully cleared old subscription');
          } else {
            console.warn('[Notifications] ⚠ Failed to unsubscribe, will try anyway');
          }
          // Wait a bit to ensure unsubscribe completes
          await new Promise(resolve => setTimeout(resolve, 500));
        } else {
          console.log('[Notifications] No existing subscription found');
        }
      } catch (e) {
        console.warn('[Notifications] Error checking/clearing subscription:', e);
        // Continue anyway - might be first time
      }

      // Check user preferences (requires authentication)
      try {
        const prefResponse = await api.get<PreferenceResponse>('/notifications/preferences.php', { requiresAuth: true });
        const preferences = prefResponse.data?.preferences;
        
        // Only auto-subscribe if user has push enabled in preferences
        if (!preferences?.push_enabled) {
          console.log('Push notifications disabled in user preferences');
          return;
        }
      } catch (prefError) {
        // If preferences endpoint fails, continue with push setup anyway
        console.log('Could not fetch preferences, continuing with push setup:', prefError);
      }

      // Get notification permission
      const permission = pushManager.getPermission();
      if (permission === 'granted') {
        // Auto-subscribe user
        const registration = await navigator.serviceWorker.ready;
        await pushManager.initialize(registration);
        
        const subscriptionData = await pushManager.subscribe();
        
        if (subscriptionData) {
          // Send subscription to backend (plain payload to satisfy API typing)
          const payload = {
            endpoint: subscriptionData.endpoint,
            keys: subscriptionData.keys,
          } as Record<string, unknown>;

          await api.post('/notifications/push_subscribe.php', payload);
          console.log('✅ Subscribed to push notifications');
        }
      } else if (permission === 'default') {
        console.log('Push permission not yet requested');
      }
    } catch (error) {
      console.error('Failed to setup push notifications:', error);
    }
  };

  return <>{children}</>;
}
