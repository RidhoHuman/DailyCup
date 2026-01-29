'use client';

import { useEffect, useState } from 'react';
import { useAuthStore } from '@/lib/stores/auth-store';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/DailyCup/webapp/backend/api';

// Public VAPID key - generate with web-push library or online tool
// For now using a placeholder - you should generate real keys
const PUBLIC_VAPID_KEY = 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBroGxJqfzYv_bUYZGZg';

function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

export function usePushNotifications() {
  const [isSupported, setIsSupported] = useState(false);
  const [subscription, setSubscription] = useState<PushSubscription | null>(null);
  const [isSubscribed, setIsSubscribed] = useState(false);
  const { isAuthenticated } = useAuthStore();

  useEffect(() => {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
      setIsSupported(true);
      
      // Register service worker
      navigator.serviceWorker.register('/sw.js')
        .then((registration) => {
          console.log('Service Worker registered:', registration);
          
          // Check if already subscribed
          registration.pushManager.getSubscription()
            .then((sub) => {
              setSubscription(sub);
              setIsSubscribed(sub !== null);
            });
        })
        .catch((error) => {
          console.error('Service Worker registration failed:', error);
        });
    }
  }, []);

  const subscribeToPush = async () => {
    if (!isSupported || !isAuthenticated) {
      console.log('Push not supported or user not authenticated');
      return false;
    }

    try {
      const registration = await navigator.serviceWorker.ready;
      
      // Request notification permission
      const permission = await Notification.requestPermission();
      
      if (permission !== 'granted') {
        console.log('Notification permission denied');
        return false;
      }

      // Subscribe to push notifications
      const sub = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(PUBLIC_VAPID_KEY)
      });

      setSubscription(sub);
      setIsSubscribed(true);

      // Send subscription to backend
      await sendSubscriptionToBackend(sub);
      
      return true;
    } catch (error) {
      console.error('Failed to subscribe to push:', error);
      return false;
    }
  };

  const unsubscribeFromPush = async () => {
    if (!subscription) return false;

    try {
      await subscription.unsubscribe();
      
      // Remove subscription from backend
      await removeSubscriptionFromBackend(subscription);
      
      setSubscription(null);
      setIsSubscribed(false);
      
      return true;
    } catch (error) {
      console.error('Failed to unsubscribe:', error);
      return false;
    }
  };

  const sendSubscriptionToBackend = async (sub: PushSubscription) => {
    const token = localStorage.getItem('dailycup-auth');
    let authToken = '';
    
    if (token) {
      try {
        const parsed = JSON.parse(token);
        authToken = parsed.state?.token || '';
      } catch {}
    }

    const subscriptionData = {
      endpoint: sub.endpoint,
      keys: {
        p256dh: arrayBufferToBase64(sub.getKey('p256dh')),
        auth: arrayBufferToBase64(sub.getKey('auth'))
      }
    };

    await fetch(`${API_BASE_URL}/notifications/subscribe.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {})
      },
      body: JSON.stringify(subscriptionData)
    });
  };

  const removeSubscriptionFromBackend = async (sub: PushSubscription) => {
    const token = localStorage.getItem('dailycup-auth');
    let authToken = '';
    
    if (token) {
      try {
        const parsed = JSON.parse(token);
        authToken = parsed.state?.token || '';
      } catch {}
    }

    await fetch(`${API_BASE_URL}/notifications/unsubscribe.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(authToken ? { 'Authorization': `Bearer ${authToken}` } : {})
      },
      body: JSON.stringify({ endpoint: sub.endpoint })
    });
  };

  return {
    isSupported,
    isSubscribed,
    subscribeToPush,
    unsubscribeFromPush
  };
}

function arrayBufferToBase64(buffer: ArrayBuffer | null): string {
  if (!buffer) return '';
  const bytes = new Uint8Array(buffer);
  let binary = '';
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return window.btoa(binary);
}
