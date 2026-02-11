'use client';

import { useState } from 'react';
import { useOnlineStatus, useServiceWorker, usePWAStatus, useCacheStorage, formatBytes } from '@/lib/hooks/usePWA';
import { Wifi, WifiOff, Download, Trash2, RefreshCw, Smartphone, Globe } from 'lucide-react';

export default function PWAStatus() {
  const isOnline = useOnlineStatus();
  const { registration, isSupported, isRegistered } = useServiceWorker();
  const { isPWA, isStandalone, displayMode } = usePWAStatus();
  const { cacheSize, isLoading, getCacheSize, clearCache } = useCacheStorage();
  const [clearing, setClearing] = useState(false);

  const handleClearCache = async () => {
    if (!confirm('Are you sure you want to clear all cached data?')) return;
    
    setClearing(true);
    await clearCache();
    setClearing(false);
    alert('Cache cleared successfully!');
  };

  const handleRefreshCache = async () => {
    await getCacheSize();
  };

  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
      <h2 className="text-xl font-bold mb-6 flex items-center gap-2">
        <Smartphone className="w-6 h-6 text-[#a15e3f]" />
        PWA Status
      </h2>

      <div className="space-y-4">
        {/* Online Status */}
        <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <div className="flex items-center gap-3">
            {isOnline ? (
              <Wifi className="w-5 h-5 text-green-600" />
            ) : (
              <WifiOff className="w-5 h-5 text-orange-600" />
            )}
            <div>
              <div className="font-semibold">Connection Status</div>
              <div className="text-sm text-gray-600 dark:text-gray-400">
                {isOnline ? 'Online' : 'Offline'}
              </div>
            </div>
          </div>
          <div className={`px-3 py-1 rounded-full text-xs font-semibold ${
            isOnline 
              ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
              : 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400'
          }`}>
            {isOnline ? 'Connected' : 'Disconnected'}
          </div>
        </div>

        {/* Service Worker Status */}
        <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <div className="flex items-center gap-3">
            <Download className="w-5 h-5 text-blue-600" />
            <div>
              <div className="font-semibold">Service Worker</div>
              <div className="text-sm text-gray-600 dark:text-gray-400">
                {isSupported ? (isRegistered ? 'Active' : 'Supported') : 'Not Supported'}
              </div>
            </div>
          </div>
          <div className={`px-3 py-1 rounded-full text-xs font-semibold ${
            isRegistered
              ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
              : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
          }`}>
            {isRegistered ? 'Registered' : 'Not Active'}
          </div>
        </div>

        {/* PWA Mode */}
        <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <div className="flex items-center gap-3">
            {isPWA ? (
              <Smartphone className="w-5 h-5 text-purple-600" />
            ) : (
              <Globe className="w-5 h-5 text-gray-600" />
            )}
            <div>
              <div className="font-semibold">Display Mode</div>
              <div className="text-sm text-gray-600 dark:text-gray-400">
                {displayMode.charAt(0).toUpperCase() + displayMode.slice(1)}
              </div>
            </div>
          </div>
          <div className={`px-3 py-1 rounded-full text-xs font-semibold ${
            isPWA
              ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400'
              : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
          }`}>
            {isPWA ? 'PWA Mode' : 'Browser'}
          </div>
        </div>

        {/* Cache Size */}
        <div className="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
          <div className="flex items-center justify-between mb-3">
            <div className="font-semibold">Cache Storage</div>
            <div className="text-sm font-mono text-gray-600 dark:text-gray-400">
              {isLoading ? 'Calculating...' : formatBytes(cacheSize)}
            </div>
          </div>
          
          <div className="flex gap-2">
            <button
              onClick={handleRefreshCache}
              disabled={isLoading}
              className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
              <span className="text-sm font-semibold">Refresh</span>
            </button>
            
            <button
              onClick={handleClearCache}
              disabled={isLoading || clearing}
              className="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <Trash2 className="w-4 h-4" />
              <span className="text-sm font-semibold">Clear</span>
            </button>
          </div>
        </div>

        {/* Service Worker Details */}
        {registration && (
          <div className="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
            <div className="font-semibold mb-2">Service Worker Details</div>
            <div className="space-y-1 text-sm text-gray-600 dark:text-gray-400">
              <div className="flex justify-between">
                <span>Scope:</span>
                <span className="font-mono text-xs">{registration.scope}</span>
              </div>
              <div className="flex justify-between">
                <span>State:</span>
                <span className="font-mono text-xs">
                  {registration.active?.state || 'N/A'}
                </span>
              </div>
              <div className="flex justify-between">
                <span>Update Available:</span>
                <span className={`font-semibold ${registration.waiting ? 'text-orange-600' : 'text-green-600'}`}>
                  {registration.waiting ? 'Yes' : 'No'}
                </span>
              </div>
            </div>
          </div>
        )}

        {/* Tips */}
        <div className="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
          <div className="text-sm text-blue-800 dark:text-blue-300">
            <strong>Tip:</strong> For the best experience, install this app on your device. 
            {!isPWA && ' Look for the install prompt or use your browser\'s install option.'}
          </div>
        </div>
      </div>
    </div>
  );
}
