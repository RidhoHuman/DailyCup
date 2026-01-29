'use client';

import { useEffect, useState } from 'react';
import { Download, X } from 'lucide-react';
import { updateServiceWorker } from '@/lib/serviceWorker';

export default function UpdatePrompt() {
  const [registration, setRegistration] = useState<ServiceWorkerRegistration | null>(null);
  const [showPrompt, setShowPrompt] = useState(false);

  useEffect(() => {
    const handleSWUpdate = (event: CustomEvent) => {
      setRegistration(event.detail.registration);
      setShowPrompt(true);
    };

    window.addEventListener('sw-update-available' as any, handleSWUpdate);

    return () => {
      window.removeEventListener('sw-update-available' as any, handleSWUpdate);
    };
  }, []);

  const handleUpdate = () => {
    if (registration) {
      updateServiceWorker(registration);
    }
  };

  const handleDismiss = () => {
    setShowPrompt(false);
  };

  if (!showPrompt) return null;

  return (
    <div className="fixed top-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50 animate-slide-down">
      <div className="p-4">
        <button
          onClick={handleDismiss}
          className="absolute top-3 right-3 p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
          aria-label="Close"
        >
          <X className="w-4 h-4" />
        </button>

        <div className="pr-8">
          <div className="flex items-center gap-3 mb-3">
            <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
              <Download className="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <h3 className="font-bold text-sm">Update Available</h3>
              <p className="text-xs text-gray-600 dark:text-gray-400">A new version is ready</p>
            </div>
          </div>

          <div className="flex gap-2">
            <button
              onClick={handleUpdate}
              className="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors"
            >
              Update Now
            </button>
            <button
              onClick={handleDismiss}
              className="px-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-2 rounded-lg text-sm font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              Later
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
