# PWA Implementation Guide - DailyCup Coffee Shop

Complete guide for Progressive Web App (PWA) implementation in DailyCup, including offline support, push notifications, and installability.

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Components](#components)
4. [Service Worker](#service-worker)
5. [Web App Manifest](#web-app-manifest)
6. [Push Notifications](#push-notifications)
7. [Installation](#installation)
8. [Testing & Validation](#testing--validation)
9. [Production Deployment](#production-deployment)
10. [Troubleshooting](#troubleshooting)

## Overview

DailyCup PWA enables:
- ✅ **Offline Access** - Browse cached pages without internet
- ✅ **Push Notifications** - Receive order updates and promotions
- ✅ **Home Screen Installation** - Add to home screen like a native app
- ✅ **Background Sync** - Queue actions when offline, sync when online
- ✅ **Fast Loading** - Aggressive caching for instant page loads
- ✅ **Responsive** - Works on mobile, tablet, and desktop

## Architecture

```
DailyCup PWA Architecture
┌────────────────────────────────────────────────┐
│              Browser (Client)                  │
├────────────────────────────────────────────────┤
│  Next.js Frontend (React)                      │
│  ├─ PWA Install Prompt                         │
│  ├─ Push Notification Subscription             │
│  └─ Offline Fallback UI                        │
├────────────────────────────────────────────────┤
│  Service Worker (sw.js)                        │
│  ├─ Cache Strategies                           │
│  │  ├─ Static Assets: Cache First              │
│  │  ├─ API Calls: Network First                │
│  │  ├─ Images: Cache First                     │
│  │  └─ HTML Pages: Network First + Offline     │
│  ├─ Push Notification Handler                  │
│  ├─ Background Sync                            │
│  └─ Offline Detection                          │
├────────────────────────────────────────────────┤
│  Web App Manifest (manifest.json)              │
│  └─ App metadata, icons, display mode          │
└────────────────────────────────────────────────┘
         ↕ HTTPS Required
┌────────────────────────────────────────────────┐
│         Backend API (PHP)                      │
│  ├─ Push Subscription API                      │
│  │  └─ /api/notifications/push_subscribe.php   │
│  ├─ Send Push Notification                     │
│  │  └─ /api/notifications/send_push.php        │
│  └─ Notification Service                       │
│     └─ /api/notifications/NotificationService  │
└────────────────────────────────────────────────┘
         ↕
┌────────────────────────────────────────────────┐
│         Database (MySQL)                       │
│  └─ push_subscriptions table                   │
│     ├─ user_id                                 │
│     ├─ endpoint                                │
│     ├─ p256dh_key                              │
│     ├─ auth_key                                │
│     └─ is_active                               │
└────────────────────────────────────────────────┘
```

## Components

### 1. Service Worker

**Location:** `webapp/frontend/public/sw.js`

The service worker is the heart of the PWA, running in the background to handle:

#### Features:
- **Offline caching** with multiple strategies
- **Push notification** handling
- **Background sync** for pending actions
- **Cache management** with size limits
- **Network/offline detection**

#### Cache Strategies:

**Cache First (Static Assets):**
```javascript
// CSS, JS, fonts, images
// Try cache first, then network if not found
// Good for: Immutable assets
```

**Network First (API, HTML):**
```javascript
// API calls, dynamic pages
// Try network first, fall back to cache if offline
// Good for: Fresh data
```

**Stale While Revalidate:**
```javascript
// Serve cached version instantly
// Update cache in background
// Good for: Non-critical updates
```

#### Cache Configuration:

```javascript
const CACHE_VERSION = 'dailycup-v1.1.0';
const CACHE_NAMES = {
  static: 'dailycup-v1.1.0-static',
  dynamic: 'dailycup-v1.1.0-dynamic',
  images: 'dailycup-v1.1.0-images',
  api: 'dailycup-v1.1.0-api'
};

// Maximum cache sizes
const MAX_IMAGE_CACHE = 50; // images
const MAX_API_CACHE = 30;   // API responses
const MAX_DYNAMIC_CACHE = 50; // pages
```

#### Service Worker Lifecycle:

```javascript
// 1. INSTALL: Pre-cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAMES.static)
      .then(cache => cache.addAll(STATIC_CACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

// 2. ACTIVATE: Clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then(names => Promise.all(
        names.filter(name => !Object.values(CACHE_NAMES).includes(name))
             .map(name => caches.delete(name))
      ))
      .then(() => self.clients.claim())
  );
});

// 3. FETCH: Intercept network requests
self.addEventListener('fetch', (event) => {
  // Apply caching strategy based on request type
});
```

### 2. Web App Manifest

**Location:** `webapp/frontend/public/manifest.json`

Provides metadata for browser installation and app appearance.

```json
{
  "name": "DailyCup - Premium Coffee Delivery",
  "short_name": "DailyCup",
  "description": "Order premium coffee delivered fresh to your door",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#a15e3f",
  "orientation": "portrait-primary",
  "icons": [
    {
      "src": "/assets/image/cup.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"
    },
    {
      "src": "/assets/image/cup.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ],
  "shortcuts": [
    { "name": "Browse Menu", "url": "/menu" },
    { "name": "My Orders", "url": "/orders" },
    { "name": "Cart", "url": "/cart" }
  ]
}
```

**Display Modes:**
- `standalone` - Looks like a native app (no browser UI)
- `fullscreen` - Full screen, no system UI
- `minimal-ui` - Minimal browser controls
- `browser` - Regular browser tab

### 3. PWA Install Prompt

**Location:** `webapp/frontend/components/PWAInstallPrompt.tsx`

Custom A2HS (Add to Home Screen) prompt with better UX than browser default.

```typescript
export default function PWAInstallPrompt() {
  const [deferredPrompt, setDeferredPrompt] = useState(null);
  const [showPrompt, setShowPrompt] = useState(false);

  useEffect(() => {
    // Capture beforeinstallprompt event
    const handler = (e) => {
      e.preventDefault();
      setDeferredPrompt(e);
      
      // Show custom prompt after 30 seconds
      setTimeout(() => setShowPrompt(true), 30000);
    };

    window.addEventListener('beforeinstallprompt', handler);
    return () => window.removeEventListener('beforeinstallprompt', handler);
  }, []);

  const handleInstall = async () => {
    if  (!deferredPrompt) return;
    
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    
    if (outcome === 'accepted') {
      console.log('User installed PWA');
    }
    
    setDeferredPrompt(null);
    setShowPrompt(false);
  };

  // Render custom install prompt UI...
}
```

**Features:**
- ✅ Delayed prompt (30s after page load)
- ✅ Dismissible with "remind later" logic
- ✅ iOS-specific instructions (Share > Add to Home Screen)
- ✅ Visual app preview
- ✅ Benefits highlighting

### 4. Offline Page

**Location:** `webapp/frontend/app/offline/page.tsx`

Fallback page shown when user is offline and page not cached.

```tsx
export default function OfflinePage() {
  const handleRefresh = () => window.location.reload();

  return (
    <div className="offline-container">
      <WifiOff className="icon" />
      <h1>You're Offline</h1>
      <p>Some features may not be available right now.</p>
      
      <div className="tip">
        You can still browse previously viewed pages while offline.
      </div>

      <button onClick={handleRefresh}>Try Again</button>
      <Link href="/">Go Home</Link>
    </div>
  );
}
```

## Service Worker

### Registration

**Location:** `webapp/frontend/app/layout.tsx`

```tsx
useEffect(() => {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
      .then(registration => {
        console.log('Service Worker registered:', registration);
      })
      .catch(error => {
        console.error('Service Worker registration failed:', error);
      });
  }
}, []);
```

### Update Strategy

When deploying new versions:

```javascript
// Update CACHE_VERSION in sw.js
const CACHE_VERSION = 'dailycup-v1.2.0'; // Increment version

// On activate, old caches are automatically deleted
// Users get new version on next page refresh
```

### Debugging Service Worker

**Chrome DevTools:**
1. Open DevTools (F12)
2. Go to Application tab
3. Select Service Workers
4. Options:
   - Unregister - Remove service worker
   - Update - Force update check
   - Bypass for network - Disable temporarily
   - Show console - View service worker logs

**Firefox DevTools:**
1. about:debugging#/runtime/this-firefox
2. Find DailyCup service worker
3. Options: Inspect, Unregister, Start/Stop

### Common Service Worker Commands

```javascript
// From browser console:

// Unregister all service workers
navigator.serviceWorker.getRegistrations()
  .then(registrations => {
    registrations.forEach(r => r.unregister());
  });

// Check registration status
navigator.serviceWorker.ready
  .then(registration => console.log('Ready:', registration));

// Post message to service worker
navigator.serviceWorker.controller.postMessage({
  action: 'clearCache'
});

// Listen for service worker messages
navigator.serviceWorker.addEventListener('message', (event) => {
  console.log('Message from SW:', event.data);
});
```

## Web App Manifest

### Linking Manifest

In Next.js, add to `<head>`:

```tsx
// app/layout.tsx
export const metadata = {
  manifest: '/manifest.json',
  themeColor: '#a15e3f',
  // ...other metadata
};
```

### Icon Requirements

PWA icons needed for different platforms:

| Size | Purpose |
|------|---------|
| 72x72 | Android small |
| 96x96 | Android medium |
| 128x128 | Android large |
| 144x144 | Android extra large |
| 152x152 | iOS |
| 192x192 | Android Chrome |
| 384x384 | Android Chrome splash |
| 512x512 | Android Chrome crisp |

**Maskable Icons:**
- Safe zone: Center 80% of icon
- Allows adaptive shapes (circle, squircle, rounded square)
- Set `"purpose": "any maskable"`

### Splash Screen

Automatically generated on Android using:
- `name` from manifest
- `theme_color` for background
- Largest `icons` entry

## Push Notifications

### Architecture

```
[User Browser] 
    ↕ Subscribe
[Service Worker] ← Push Event ← [Push Service (FCM/etc)]
                                      ↑ Send
                                      |
                              [Backend API]
                                      ↑ Trigger
                              [Admin/Cron/Event]
```

### Setup VAPID Keys

VAPID (Voluntary Application Server Identification) authenticates your server.

**Generate keys:**
```bash
npx web-push generate-vapid-keys
```

**Configure:**
```env
# Backend: webapp/backend/.env
VAPID_PUBLIC_KEY="BJZ2QjWbziK5U68pPrWDIcSB8Sm9ONFwVCi_U7LTJkyvh-Lp5nBMw1Pgq3SIaA0txvKVOHX0YdSQ5Qi8xn7e4wI"
VAPID_PRIVATE_KEY="K6ZVZP5dYamPwtq6J0-7MiHx-SAqV2d3FNBDpmIvc9A"
VAPID_SUBJECT="mailto:admin@dailycup.com"

# Frontend: webapp/frontend/.env.local
NEXT_PUBLIC_VAPID_PUBLIC_KEY="BJZ2QjWbziK5U68pPrWDIcSB8Sm9ONFwVCi_U7LTJkyvh-Lp5nBMw1Pgq3SIaA0txvKVOHX0YdSQ5Qi8xn7e4wI"
```

### Frontend: Subscribe to Push

```typescript
// Example: Subscribe user to push notifications
async function subscribeToPushNotifications() {
  const registration = await navigator.serviceWorker.ready;
  
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(
      process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY!
    )
  });

  // Send subscription to backend
  const response = await fetch('/api/notifications/push_subscribe', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      endpoint: subscription.endpoint,
      keys: {
        p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
        auth: arrayBufferToBase64(subscription.getKey('auth'))
      }
    })
  });

  return await response.json();
}

function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
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
```

### Backend: Send Push Notification

**API Endpoint:** `POST /api/notifications/send_push.php`

```php
// Example: Send push notification
$notification = [
    'title' => 'Order Delivered!',
    'message' => 'Your DailyCup order has been delivered. Enjoy!',
    'url' => '/orders/123',
    'icon' => '/assets/image/cup.png',
    'tag' => 'order-delivered-123',
    'user_id' => 456, // Optional: specific user
    'type' => 'order_delivered'
];

$ch = curl_init('https://api.dailycup.com/api/notifications/send_push');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $adminToken
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);
```

### Push Notification Best Practices

✅ **Do:**
- Ask for permission at relevant moments (after first order, in settings)
- Provide clear value (order updates, exclusive deals)
- Allow users to customize notification preferences
- Use rich notifications (images, actions)
- Respect user's "Do Not Disturb" settings
- Batch notifications when possible

❌ **Don't:**
- Ask for permission immediately on first visit
- Send spam or irrelevant notifications
- Abuse push notifications for marketing
- Send notifications late at night
- Use for non-essential updates

### Notification Types

**Order Updates:**
```json
{
  "title": "Order Status Update",
  "message": "Your order is being prepared",
  "url": "/orders/123",
  "tag": "order-123",
  "requireInteraction": false
}
```

**Promotional:**
```json
{
  "title": "Special Offer!",
  "message": "Get 20% off your next order",
  "url": "/menu?promo=SAVE20",
  "tag": "promo-daily",
  "image": "/promos/daily-deal.jpg",
  "actions": [
    { "action": "view", "title": "View Deal" },
    { "action": "dismiss", "title": "Not Now" }
  ]
}
```

**System Alerts:**
```json
{
  "title": "Account Security",
  "message": "New login detected from Chrome on Windows",
  "url": "/account/security",
  "tag": "security-alert",
  "requireInteraction": true
}
```

## Installation

### Prerequisites

- HTTPS (required for service workers and push notifications)
- Modern browser (Chrome 45+, Firefox 44+, Safari 11.1+, Edge 17+)

### Browser Installation

**Chrome/Edge (Desktop):**
1. Visit the website
2. Look for install icon in address bar
3. Click "Install" button
4. App opens in standalone window

**Chrome/Edge (Android):**
1. Visit the website
2. Tap "Add to Home screen" from menu
3. Or use install prompt when shown
4. App appears on home screen

**Safari (iOS):**
1. Visit the website
2. Tap Share button
3. Select "Add to Home Screen"
4. Tap "Add"

**Firefox (Desktop):**
1. Visit the website
2. Look for install icon in address bar
3. Click "Install" button

### Programmatic Installation Check

```typescript
// Check if already installed
function isPWAInstalled() {
  return (
    window.matchMedia('(display-mode: standalone)').matches ||
    (window.navigator as any).standalone ||
    document.referrer.includes('android-app://')
  );
}

// Check if installable
let deferredPrompt: any = null;

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  // Show custom install button
  showInstallButton();
});

// Trigger installation
async function installPWA() {
  if (!deferredPrompt) return;
  
  deferredPrompt.prompt();
  const { outcome } = await deferredPrompt.userChoice;
  
  if (outcome === 'accepted') {
    console.log('User accepted installation');
  }
  
  deferredPrompt = null;
}

// Check if installation was successful
window.addEventListener('appinstalled', () => {
  console.log('PWA was installed successfully');
  hideInstallButton();
});
```

## Testing & Validation

### Lighthouse PWA Audit

**Run audit:**
1. Open Chrome DevTools
2. Go to Lighthouse tab
3. Select "Progressive Web App" category
4. Click "Generate report"

**Requirements for PWA badge:**
- ✅ Registers a service worker
- ✅ Responds with 200 when offline
- ✅ Has a web app manifest
- ✅ Uses HTTPS
- ✅ Provides a valid apple-touch-icon
- ✅ Configured for a custom splash screen
- ✅ Sets an address bar theme color

**Target Scores:**
- Performance: > 90
- Accessibility: > 90
- Best Practices: > 90
- SEO: > 90
- PWA: 100 (all checks passing)

### Manual Testing Checklist

**Service Worker:**
- [ ] Service worker registers successfully
- [ ] Static assets are cached on install
- [ ] Pages load when offline (previously visited)
- [ ] Offline page shows for unvisited pages
- [ ] API calls fall back to cache when offline
- [ ] Images load from cache
- [ ] Service worker updates when version changes
- [ ] Old caches are cleaned up on activation

**Manifest:**
- [ ] Manifest is valid JSON
- [ ] All required fields present
- [ ] Icons load correctly (192x192, 512x512)
- [ ] App name displays correctly
- [ ] Theme color applies
- [ ] App opens in standalone mode

**Push Notifications:**
- [ ] Permission prompt shows
- [ ] Subscription saves to database
- [ ] Notifications appear when sent
- [ ] Notification click opens correct URL
- [ ] Notifications work when app closed
- [ ] Unsubscribe works correctly

**Installation:**
- [ ] Install prompt shows (desktop/Android)
- [ ] iOS add to home screen works
- [ ] App icon appears on home screen
- [ ] App launches in standalone mode
- [ ] Splash screen shows on launch (Android)
- [ ] App persists after device restart

### Test URLs

```
# Local testing
http://localhost:3000

# Service worker (must be /sw.js from root)
http://localhost:3000/sw.js

# Manifest
http://localhost:3000/manifest.json

# Offline page
http://localhost:3000/offline

# Test offline mode
# 1. Visit site with DevTools open
# 2. Go to Application > Service Workers
# 3. Check "Offline" checkbox
# 4. Navigate to different pages
```

### Testing Tools

**Chrome DevTools:**
- Application tab: Inspect manifest, service worker, cache storage
- Network tab: Throttle to slow 3G to test caching
- Console: View service worker logs
- Lighthouse: PWA audit

**Firefox DevTools:**
- Storage tab: View cache storage
- about:debugging: Inspect service workers
- Console: Service worker logs

**Online Tools:**
- [PWA Builder](https://www.pwabuilder.com/) - Validate PWA
- [Lighthouse CI](https://github.com/GoogleChrome/lighthouse-ci) - Automated testing
- [Web.dev Measure](https://web.dev/measure/) - Online Lighthouse
- [Manifest Validator](https://manifest-validator.appspot.com/) - Validate manifest

### Browser DevTools Commands

```javascript
// Check service worker registration
navigator.serviceWorker.getRegistration()
  .then(reg => console.log(reg));

// Check push subscription
navigator.serviceWorker.ready
  .then(reg => reg.pushManager.getSubscription())
  .then(sub => console.log(sub));

// Check cache contents
caches.keys()
  .then(names => console.log('Caches:', names));

caches.open('dailycup-v1.1.0-static')
  .then(cache => cache.keys())
  .then(keys => console.log('Cached URLs:', keys.map(k => k.url)));

// Clear all caches
caches.keys()
  .then(names => Promise.all(names.map(name => caches.delete(name))));

// Test push notification
navigator.serviceWorker.ready
  .then(reg => {
    reg.showNotification('Test Notification', {
      body: 'This is a test',
      icon: '/assets/image/cup.png'
    });
  });
```

## Production Deployment

### Deployment Checklist

**Backend:**
- [ ] Web-push library installed (`composer require minishlink/web-push`)
- [ ] VAPID keys generated and added to `.env`
- [ ] `.env` file secured (not in version control)
- [ ] Push notifications API endpoints deployed
- [ ] Database has `push_subscriptions` table
- [ ] HTTPS enabled (required for service workers)

**Frontend:**
- [ ] VAPID public key in `.env.local`
- [ ] Service worker in `public/sw.js`
- [ ] Manifest in `public/manifest.json`
- [ ] All icon sizes generated and uploaded
- [ ] PWA metadata in `layout.tsx`
- [ ] Service worker registration in `layout.tsx`
- [ ] Offline page deployed
- [ ] Install prompt component integrated

**Server Configuration:**
- [ ] HTTPS certificate installed and valid
- [ ] Service worker served with correct MIME type (`application/javascript`)
- [ ] Manifest served with correct MIME type (`application/manifest+json`)
- [ ] Icons served with long cache headers (immutable)
- [ ] Service worker served with no-cache headers
- [ ] CORS headers configured for API

### HTTPS Configuration

Service workers require HTTPS (except localhost for development).

**Apache (.htaccess):**
```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Service Worker headers
<FilesMatch "sw\.js$">
  Header set Cache-Control "public, max-age=0, must-revalidate"
  Header set Service-Worker-Allowed "/"
</FilesMatch>

# Manifest headers
<FilesMatch "manifest\.json$">
  Header set Content-Type "application/manifest+json"
  Header set Cache-Control "public, max-age=604800"
</FilesMatch>
```

**Nginx:**
```nginx
# Force HTTPS
server {
    listen 80;
    server_name dailycup.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name dailycup.com;
    
    # SSL configuration
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # Service worker
    location /sw.js {
        add_header Cache-Control "public, max-age=0, must-revalidate";
        add_header Service-Worker-Allowed "/";
        types { application/javascript js; }
    }
    
    # Manifest
    location /manifest.json {
        add_header Content-Type "application/manifest+json";
        add_header Cache-Control "public, max-age=604800";
    }
}
```

### Environment Variables

**Production .env (Backend):**
```env
# Database
DB_HOST=localhost
DB_NAME=dailycup_production
DB_USER=dailycup_user
DB_PASS=secure_password_here

# VAPID Keys
VAPID_PUBLIC_KEY="BJZ2QjWbziK5U68pPrWDIcSB8Sm9ONFwVCi_U7LTJkyvh-Lp5nBMw1Pgq3SIaA0txvKVOHX0YdSQ5Qi8xn7e4wI"
VAPID_PRIVATE_KEY="K6ZVZP5dYamPwtq6J0-7MiHx-SAqV2d3FNBDpmIvc9A"
VAPID_SUBJECT="mailto:admin@dailycup.com"

# JWT
JWT_SECRET=different_secret_for_production
JWT_EXPIRY=86400

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=noreply@dailycup.com
SMTP_PASS=email_password_here
```

**Production .env.local (Frontend):**
```env
NEXT_PUBLIC_API_URL=https://api.dailycup.com/api
NEXT_PUBLIC_VAPID_PUBLIC_KEY="BJZ2QjWbziK5U68pPrWDIcSB8Sm9ONFwVCi_U7LTJkyvh-Lp5nBMw1Pgq3SIaA0txvKVOHX0YdSQ5Qi8xn7e4wI"
NEXT_TELEMETRY_DISABLED=1
```

### Cache Strategy Updates

When deploying updates:

```javascript
// 1. Update CACHE_VERSION in sw.js
const CACHE_VERSION = 'dailycup-v1.2.0'; // Increment this

// 2. Deploy new sw.js to server

// 3. On next page load:
//    - New sw.js installs
//    - Waits for old sw.js to finish
//    - Activates and cleans up old caches
//    - Users get fresh content
```

**Force update strategy:**
```javascript
// In service worker (sw.js)
self.addEventListener('message', (event) => {
  if (event.data.action === 'skipWaiting') {
    self.skipWaiting();
  }
});

// From client page
navigator.serviceWorker.ready
  .then(registration => {
    registration.update(); // Check for updates
    
    // If update found, force activation
    if (registration.waiting) {
      registration.waiting.postMessage({ action: 'skipWaiting' });
    }
  });

// Listen for new service worker
navigator.serviceWorker.addEventListener('controllerchange', () => {
  // New service worker activated, reload page
  window.location.reload();
});
```

### Monitoring

Track PWA metrics:

**Key Metrics:**
- Service worker installation rate
- Push notification subscription rate
- Push notification click-through rate
- Offline usage (analytics when back online)
- App install rate (A2HS acceptance)
- Service worker update frequency

**Analytics Integration:**
```javascript
// Track service worker events
navigator.serviceWorker.ready
  .then(registration => {
    analytics.track('service_worker_ready', {
      scope: registration.scope
    });
  });

// Track install prompt
window.addEventListener('beforeinstallprompt', (e) => {
  analytics.track('install_prompt_shown');
});

window.addEventListener('appinstalled', () => {
  analytics.track('pwa_installed');
});

// Track push subscription
function trackPushSubscription(subscription) {
  analytics.track('push_subscription_created', {
    endpoint: subscription.endpoint.substring(0, 50)
  });
}
```

## Troubleshooting

### Common Issues

#### Service Worker Not Registering

**Symptoms:**
- Console error: "Failed to register service worker"
- Service worker not appearing in DevTools

**Solutions:**
```javascript
// Check if service workers are supported
if ('serviceWorker' in navigator) {
  console.log('Service workers are supported');
} else {
  console.error('Service workers are NOT supported');
}

// Check HTTPS
if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
  console.error('Service workers require HTTPS');
}

// Check file path (must be /sw.js from root)
navigator.serviceWorker.register('/sw.js', { scope: '/' })
  .then(reg => console.log('Registered:', reg))
  .catch(err => console.error('Registration failed:', err));

// Check MIME type
// sw.js must be served as application/javascript
fetch('/sw.js').then(r => console.log('Content-Type:', r.headers.get('content-type')));
```

#### Push Notifications Not Working

**Issue: "Registration failed - push service error"**
```javascript
// Check push manager support
if ('PushManager' in window) {
  console.log('Push notifications supported');
} else {
  console.error('Push notifications NOT supported');
}

// Check notification permission
console.log('Notification permission:', Notification.permission);

// Request permission
Notification.requestPermission()
  .then(permission => console.log('Permission:', permission));

// Check VAPID key format
const key = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY;
console.log('VAPID key length:', key.length); // Should be 88
console.log('VAPID key format:', /^[A-Za-z0-9_-]+$/.test(key)); // Should be base64url
```

**Issue: "VAPID keys not configured"**
```bash
# Check backend .env file exists
ls webapp/backend/.env

# Check keys are present
cat webapp/backend/.env | grep VAPID

# Restart PHP server
# Apache: sudo service apache2 restart
# Nginx : sudo service nginx restart
```

**Issue: Notifications not appearing**
```javascript
// Test notification directly
navigator.serviceWorker.ready
  .then(registration => {
    return registration.showNotification('Test', {
      body: 'Can you see this?',
      icon: '/icon-192x192.png'
    });
  })
  .then(() => console.log('Notification shown'))
  .catch(err => console.error('Show notification failed:', err));

// Check notification permission
if (Notification.permission === 'denied') {
  console.error('User denied notification permission');
  // Must be reset in browser settings
}
```

#### Offline Page Not Showing

**Issue: Blank page when offline**
```javascript
// Check if offline page is cached
caches.open('dailycup-v1.1.0-static')
  .then(cache => cache.match('/offline'))
  .then(response => {
    if (response) {
      console.log('Offline page cached');
    } else {
      console.error('Offline page NOT cached');
    }
  });

// Make sure offline page is in STATIC_CACHE_URLS
const STATIC_CACHE_URLS = [
  '/',
  '/offline', // Must include this
  '/manifest.json'
];
```

#### Icons Not Showing

**Issue: Default icon instead of custom icon**
```javascript
// Check manifest icons
fetch('/manifest.json')
  .then(r => r.json())
  .then(manifest => {
    console.log('Icons:', manifest.icons);
    
    // Verify each icon exists
    manifest.icons.forEach(icon => {
      fetch(icon.src)
        .then(r => console.log(`${icon.src}: ${r.status}`))
        .catch(e => console.error(`${icon.src}: FAILED`));
    });
  });

// Check sizes
// Minimum: 192x192 and 512x512
// Recommended: 72, 96, 128, 144, 152, 192, 384, 512
```

**Issue: Wrong icon shape on Android**
```json
// Use maskable icons for adaptive shapes
{
  "icons": [
    {
      "src": "/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any maskable"  // Add maskable
    }
  ]
}

// Design maskable icon:
// - Important content in center 80%
// - Allow 20% safe padding on edges
// - Test at: https://maskable.app
```

#### App Not Installing

**Issue: No install prompt**
```javascript
// Check installation criteria
// 1. Has valid manifest with name, icons, start_url
// 2. Has registered service worker
// 3. Served over HTTPS
// 4. User has not installed or dismissed prompt recently

// Check manifest validity
fetch('/manifest.json')
  .then(r => r.json())
  .then(manifest => {
    const required = ['name', 'icons', 'start_url', 'display'];
    const missing = required.filter(field => !manifest[field]);
    
    if (missing.length > 0) {
      console.error('Missing required fields:', missing);
    } else {
      console.log('Manifest valid');
    }
  });

// Check if criteria met
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('Install criteria met! Prompt available.');
});

// If no event after 5 seconds
setTimeout(() => {
  if (!deferredPrompt) {
    console.warn('Install prompt not triggered. Check:');
    console.warn('- Valid manifest');
    console.warn('- Service worker registered');
    console.warn('- HTTPS enabled');
    console.warn('- Not already installed');
    console.warn('- Not dismissed recently');
  }
}, 5000);
```

**Issue: iOS not showing icon**
```html
<!-- iOS requires apple-touch-icon meta tag -->
<link rel="apple-touch-icon" href="/assets/image/cup.png" />
<link rel="apple-touch-icon" sizes="152x152" href="/icon-152x152.png" />
<link rel="apple-touch-icon" sizes="180x180" href="/icon-180x180.png" />

<!-- iOS doesn't support web app manifest for icons -->
<!-- Must use meta tags -->
```

#### Cache Not Updating

**Issue: Old content still showing**
```javascript
// Force cache update
// Method 1: Update CACHE_VERSION
const CACHE_VERSION = 'dailycup-v1.2.0'; // Change this

// Method 2: Clear cache programmatically
navigator.serviceWorker.addEventListener('message', (event) => {
  if (event.data.action === 'clearCache') {
    caches.keys()
      .then(names => Promise.all(names.map(name => caches.delete(name))))
      .then(() => console.log('All caches cleared'));
  }
});

// Method 3: Manual clear in DevTools
// Application > Storage > Clear site data

// Method 4: Bypass cache for specific request
fetch('/api/products', { cache: 'no-store' });
```

#### Service Worker Update Loop

**Issue: Service worker keeps updating infinitely**
```javascript
// Problem: Service worker file keeps changing
// Solution: Don't dynamically generate sw.js

// ❌ Bad: sw.js generated with timestamp
const CACHE_VERSION = 'v' + Date.now(); // Changes every load!

// ✅ Good: sw.js is static file with fixed version
const CACHE_VERSION = 'dailycup-v1.1.0'; // Only changes on deploy

// Also check:
// - sw.js not being cached by server
// - sw.js has Cache-Control: max-age=0
```

### Debug Mode

Add debug logging to service worker:

```javascript
// sw.js
const DEBUG = true; // Set to false in production

function log(...args) {
  if (DEBUG) {
    console.log('[SW Debug]', ...args);
  }
}

self.addEventListener('fetch', (event) => {
  log('Fetch:', event.request.url);
  log('Method:', event.request.method);
  log('Mode:', event.request.mode);
  
  // ... handle request
});

self.addEventListener('push', (event) => {
  log('Push received:', event.data?.text());
  // ... show notification
});
```

### Support Resources

- [MDN: Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Google: PWA Checklist](https://web.dev/pwa-checklist/)
- [web.dev: Learn PWA](https://web.dev/learn/pwa/)
- [caniuse.com: Service Worker Support](https://caniuse.com/serviceworkers)
- [Stack Overflow: PWA Tag](https://stackoverflow.com/questions/tagged/progressive-web-apps)

### Getting Help

If you encounter issues:

1. Check browser console for errors
2. Inspect service worker in DevTools (Application tab)
3. Run Lighthouse audit
4. Test in incognito mode (eliminates cached issues)
5. Try different browser (Chrome, Firefox, Edge)
6. Check HTTPS certificate is valid
7. Verify all files are accessible (sw.js, manifest.json, icons)
8. Review server logs for backend API errors

## Conclusion

DailyCup PWA provides a native app-like experience with offline support, push notifications, and home screen installation. The implementation follows best practices for caching strategies, notification UX, and progressive enhancement.

**Key Takeaways:**
- Service worker enables offline functionality
- Manifest makes the app installable
- VAPID keys authenticate push notifications
- HTTPS is required for all PWA features
- Progressive enhancement ensures graceful degradation

**Next Steps:**
1. Test PWA features thoroughly
2. Monitor PWA metrics (install rate, push engagement)
3. Iterate based on user feedback
4. Keep service worker cache version updated
5. Consider advanced features (background sync, periodic sync)

**PWA Checklist:**
- ✅ Service worker registered
- ✅ Offline page available
- ✅ Manifest valid and linked
- ✅ Icons provided (192px, 512px)
- ✅ HTTPS enabled
- ✅ Push notifications configured
- ✅ Install prompt implemented
- ✅ Lighthouse PWA score = 100

For more information, see:
- [VAPID Keys Setup Guide](./VAPID_KEYS_SETUP.md)
- [Database Schema](../database/schema.sql)
- [API Documentation](./API_DOCUMENTATION.md)

---

**Questions or issues?** Check the Troubleshooting section or create an issue in the repository.

**Happy coding! ☕**
