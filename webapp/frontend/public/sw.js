/* eslint-disable no-restricted-globals */

// Service Worker for DailyCup PWA
const CACHE_VERSION = 'dailycup-v1.2.0'; // Updated to fix hydration errors - DO NOT cache HTML pages
const CACHE_NAMES = {
  static: `${CACHE_VERSION}-static`,
  dynamic: `${CACHE_VERSION}-dynamic`,
  images: `${CACHE_VERSION}-images`,
  api: `${CACHE_VERSION}-api`
};

// Files to cache on install - IMPORTANT: Do NOT cache HTML pages (/, /menu, /cart)
// HTML pages use SSR and caching them causes hydration mismatches
const STATIC_CACHE_URLS = [
  '/offline',
  '/manifest.json',
  '/assets/image/cup.png'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAMES.static)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_CACHE_URLS);
      })
      .then(() => {
        console.log('[Service Worker] Skip waiting');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames
            .filter((cacheName) => {
              return !Object.values(CACHE_NAMES).includes(cacheName);
            })
            .map((cacheName) => {
              console.log('[Service Worker] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            })
        );
      })
      .then(() => {
        console.log('[Service Worker] Claiming clients');
        return self.clients.claim();
      })
  );
});

// Push event - show notification
self.addEventListener('push', (event) => {
  let data = {
    title: 'DailyCup',
    body: 'Anda memiliki notifikasi baru',
    icon: '/assets/image/cup.png',
    badge: '/logo/cup-badge.png',
    tag: 'dailycup-notification',
    data: {
      url: '/'
    }
  };

  if (event.data) {
    try {
      const parsed = event.data.json();
      data = {
        title: parsed.title || data.title,
        body: parsed.message || parsed.body || data.body,
        icon: data.icon,
        badge: data.badge,
        tag: parsed.tag || data.tag,
        data: {
          url: parsed.action_url || parsed.url || data.data.url,
          notificationId: parsed.id
        }
      };
    } catch (e) {
      data.body = event.data.text();
    }
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon,
      badge: data.badge,
      tag: data.tag,
      data: data.data,
      vibrate: [200, 100, 200],
      requireInteraction: false
    })
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const url = event.notification.data?.url || '/';
  
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then((clientList) => {
        // Check if there's already a window open
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url === url && 'focus' in client) {
            return client.focus();
          }
        }
        // Open new window
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip cross-origin requests
  if (url.origin !== location.origin) {
    return;
  }

  // API requests - Network first, cache fallback
  if (url.pathname.includes('/backend/api/') || url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const responseClone = response.clone();
          
          // Only cache GET requests - POST/PUT/DELETE cannot be cached
          if (response.ok && request.method === 'GET') {
            caches.open(CACHE_NAMES.api).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          
          return response;
        })
        .catch(() => {
          // Only return cached response for GET requests
          if (request.method === 'GET') {
            return caches.match(request).then((cachedResponse) => {
              return cachedResponse || new Response(
                JSON.stringify({ 
                  success: false, 
                  error: 'Offline - using cached data',
                  offline: true 
                }),
                { headers: { 'Content-Type': 'application/json' } }
              );
            });
          }
          
          // For non-GET requests (POST/PUT/DELETE), return offline error
          return new Response(
            JSON.stringify({ 
              success: false, 
              error: 'Network unavailable - cannot complete request',
              offline: true 
            }),
            { 
              status: 503,
              headers: { 'Content-Type': 'application/json' } 
            }
          );
        })
    );
    return;
  }

  // Images - Cache first, network fallback
  if (request.destination === 'image') {
    event.respondWith(
      caches.match(request)
        .then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          return fetch(request).then((response) => {
            // Only cache successful GET requests
            if (response.ok && request.method === 'GET') {
              const responseClone = response.clone();
              caches.open(CACHE_NAMES.images).then((cache) => {
                cache.put(request, responseClone);
              });
            }
            return response;
          });
        })
    );
    return;
  }

  // HTML pages - ALWAYS fetch from network (SSR content should not be cached)
  // Caching HTML causes React hydration errors due to server/client mismatch
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Do NOT cache HTML pages - they contain SSR content that must match client
          return response;
        })
        .catch(() => {
          // Only show offline page when network fails
          return caches.match('/offline');
        })
    );
    return;
  }

  // Default - Cache first, network fallback (GET only)
  // Skip caching for non-GET requests (POST, PUT, DELETE)
  if (request.method !== 'GET') {
    event.respondWith(fetch(request));
    return;
  }

  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        
        return fetch(request).then((response) => {
          if (!response || response.status !== 200) {
            return response;
          }
          
          const responseClone = response.clone();
          caches.open(CACHE_NAMES.dynamic).then((cache) => {
            cache.put(request, responseClone);
          });
          
          return response;
        });
      })
  );
});
