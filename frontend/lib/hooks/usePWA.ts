import { useState, useEffect } from 'react';

export function useOnlineStatus() {
  const [isOnline, setIsOnline] = useState(true);

  useEffect(() => {
    // Set initial status
    setIsOnline(navigator.onLine);

    const handleOnline = () => setIsOnline(true);
    const handleOffline = () => setIsOnline(false);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  return isOnline;
}

export function useServiceWorker() {
  const [registration, setRegistration] = useState<ServiceWorkerRegistration | null>(null);
  const [isSupported, setIsSupported] = useState(false);
  const [isRegistered, setIsRegistered] = useState(false);

  useEffect(() => {
    if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
      setIsSupported(true);

      navigator.serviceWorker.getRegistration().then((reg) => {
        setRegistration(reg || null);
        setIsRegistered(!!reg);
      });
    }
  }, []);

  return { registration, isSupported, isRegistered };
}

export function useInstallPrompt() {
  const [installPrompt, setInstallPrompt] = useState<any>(null);
  const [isInstalled, setIsInstalled] = useState(false);

  useEffect(() => {
    // Check if already installed
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
    setIsInstalled(isStandalone);

    const handleBeforeInstallPrompt = (e: Event) => {
      e.preventDefault();
      setInstallPrompt(e);
    };

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);

    return () => {
      window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    };
  }, []);

  const promptInstall = async () => {
    if (!installPrompt) return false;

    installPrompt.prompt();
    const { outcome } = await installPrompt.userChoice;
    
    if (outcome === 'accepted') {
      setIsInstalled(true);
      return true;
    }
    
    return false;
  };

  return { installPrompt, isInstalled, promptInstall };
}

export function usePWAStatus() {
  const [isPWA, setIsPWA] = useState(false);
  const [isStandalone, setIsStandalone] = useState(false);
  const [displayMode, setDisplayMode] = useState<'browser' | 'standalone' | 'minimal-ui' | 'fullscreen'>('browser');

  useEffect(() => {
    if (typeof window === 'undefined') return;

    // Check display mode
    const standalone = window.matchMedia('(display-mode: standalone)').matches;
    const minimalUi = window.matchMedia('(display-mode: minimal-ui)').matches;
    const fullscreen = window.matchMedia('(display-mode: fullscreen)').matches;

    setIsStandalone(standalone);
    setIsPWA(standalone || minimalUi || fullscreen);

    if (standalone) {
      setDisplayMode('standalone');
    } else if (minimalUi) {
      setDisplayMode('minimal-ui');
    } else if (fullscreen) {
      setDisplayMode('fullscreen');
    } else {
      setDisplayMode('browser');
    }
  }, []);

  return { isPWA, isStandalone, displayMode };
}

export function useCacheStorage() {
  const [cacheSize, setCacheSize] = useState(0);
  const [isLoading, setIsLoading] = useState(false);

  const getCacheSize = async () => {
    if (typeof window === 'undefined' || !('caches' in window)) return 0;

    setIsLoading(true);
    try {
      const cacheNames = await caches.keys();
      let totalSize = 0;

      for (const cacheName of cacheNames) {
        const cache = await caches.open(cacheName);
        const requests = await cache.keys();
        
        for (const request of requests) {
          const response = await cache.match(request);
          if (response) {
            const blob = await response.blob();
            totalSize += blob.size;
          }
        }
      }

      setCacheSize(totalSize);
      return totalSize;
    } catch (error) {
      console.error('Failed to get cache size:', error);
      return 0;
    } finally {
      setIsLoading(false);
    }
  };

  const clearCache = async () => {
    if (typeof window === 'undefined' || !('caches' in window)) return;

    setIsLoading(true);
    try {
      const cacheNames = await caches.keys();
      await Promise.all(cacheNames.map((name) => caches.delete(name)));
      setCacheSize(0);
    } catch (error) {
      console.error('Failed to clear cache:', error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    getCacheSize();
  }, []);

  return { cacheSize, isLoading, getCacheSize, clearCache };
}

export function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 Bytes';

  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));

  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
