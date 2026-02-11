'use client';

import Link from 'next/link';
import { WifiOff, Home, RefreshCw } from 'lucide-react';

export default function OfflinePage() {
  const handleRefresh = () => {
    window.location.reload();
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4">
      <div className="max-w-md w-full text-center">
        {/* Offline Icon */}
        <div className="w-24 h-24 bg-orange-100 dark:bg-orange-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
          <WifiOff className="w-12 h-12 text-orange-600 dark:text-orange-400" />
        </div>

        {/* Title */}
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-3">
          You're Offline
        </h1>

        {/* Description */}
        <p className="text-gray-600 dark:text-gray-400 mb-8">
          It looks like you've lost your internet connection. Some features may not be available right now.
        </p>

        {/* Cached Content Notice */}
        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-8">
          <p className="text-sm text-blue-800 dark:text-blue-300">
            <strong>Good news!</strong> You can still browse previously viewed pages while offline.
          </p>
        </div>

        {/* Actions */}
        <div className="space-y-3">
          <button
            onClick={handleRefresh}
            className="w-full bg-[#a15e3f] text-white py-3 px-6 rounded-xl font-semibold hover:bg-[#8a4f35] transition-colors flex items-center justify-center gap-2"
          >
            <RefreshCw className="w-5 h-5" />
            Try Again
          </button>

          <Link
            href="/"
            className="block w-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white py-3 px-6 rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
          >
            <span className="flex items-center justify-center gap-2">
              <Home className="w-5 h-5" />
              Go to Home
            </span>
          </Link>
        </div>

        {/* Tips */}
        <div className="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
          <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
            While you're offline:
          </h3>
          <ul className="text-sm text-gray-600 dark:text-gray-400 space-y-2">
            <li>✓ Browse previously loaded pages</li>
            <li>✓ View your cart items</li>
            <li>✓ Access saved favorites</li>
            <li>✗ Place new orders (requires internet)</li>
            <li>✗ Update account settings</li>
          </ul>
        </div>

        {/* Connection Status */}
        <div className="mt-8 text-xs text-gray-500 dark:text-gray-500">
          We'll automatically reconnect when you're back online
        </div>
      </div>
    </div>
  );
}
