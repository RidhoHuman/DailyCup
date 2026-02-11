'use client';

import { usePushNotifications } from '@/lib/hooks/usePushNotifications';
import { useAuthStore } from '@/lib/stores/auth-store';
import { useEffect, useState } from 'react';

export default function PushNotificationToggle() {
  const { isSupported, isSubscribed, subscribeToPush, unsubscribeFromPush } = usePushNotifications();
  const { isAuthenticated } = useAuthStore();
  const [isLoading, setIsLoading] = useState(false);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  if (!mounted || !isAuthenticated || !isSupported) {
    return null;
  }

  const handleToggle = async () => {
    setIsLoading(true);
    
    if (isSubscribed) {
      await unsubscribeFromPush();
    } else {
      await subscribeToPush();
    }
    
    setIsLoading(false);
  };

  return (
    <div className="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
      <div>
        <h3 className="text-sm font-medium text-gray-900 dark:text-white">
          Push Notifications
        </h3>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Terima notifikasi bahkan saat aplikasi tertutup
        </p>
      </div>
      
      <button
        onClick={handleToggle}
        disabled={isLoading}
        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[#a97456] focus:ring-offset-2 ${
          isSubscribed ? 'bg-[#a97456]' : 'bg-gray-200 dark:bg-gray-700'
        } ${isLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
      >
        <span
          className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
            isSubscribed ? 'translate-x-6' : 'translate-x-1'
          }`}
        />
      </button>
    </div>
  );
}
