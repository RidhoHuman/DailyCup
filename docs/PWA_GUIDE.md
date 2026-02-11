# PWA Implementation Guide

## Overview
DailyCup now supports Progressive Web App (PWA) features, allowing users to install the app on their devices and use it offline.

## Features Implemented

### 1. App Manifest (`/manifest.json`)
- App metadata and branding
- Custom theme colors
- App shortcuts (Menu, Orders, Cart)
- Categories and descriptions
- iOS/Android icon support

### 2. Service Worker (`/sw.js`)
- **Static Cache**: Core app files
- **Dynamic Cache**: Pages visited by user
- **API Cache**: Network-first with cache fallback
- **Image Cache**: Cache-first strategy
- **Offline Fallback**: Custom offline page
- **Background Sync**: Sync orders and cart when online
- **Push Notifications**: Support for notifications

### 3. Caching Strategies

#### Static Resources
- Network first, cache fallback
- Includes: /, /menu, /cart, /offline, manifest.json

#### API Requests
- Network first, cache fallback
- Cached for offline access
- Returns offline indicator when network unavailable

#### Images
- Cache first, network fallback
- Reduces bandwidth usage
- Faster subsequent loads

#### Navigation
- Network first with cache and offline fallback
- Seamless offline experience

### 4. Components

#### OfflineBanner
Shows status when user goes offline/online
```tsx
import OfflineBanner from '@/components/OfflineBanner';
```

#### PWAInstallPrompt
Prompts users to install the app
- Auto-shows after 30 seconds
- Different UI for iOS (manual instructions)
- Can be dismissed for 7 days
```tsx
import PWAInstallPrompt from '@/components/PWAInstallPrompt';
```

#### UpdatePrompt
Notifies users when new version available
```tsx
import UpdatePrompt from '@/components/UpdatePrompt';
```

### 5. Offline Page
Custom `/offline` page shown when:
- User navigates to uncached page while offline
- Network request fails
- Service worker cannot serve from cache

### 6. Hooks

#### useOnlineStatus
```tsx
const isOnline = useOnlineStatus();
```

#### useServiceWorker
```tsx
const { registration, isSupported, isRegistered } = useServiceWorker();
```

#### useInstallPrompt
```tsx
const { installPrompt, isInstalled, promptInstall } = useInstallPrompt();
```

#### usePWAStatus
```tsx
const { isPWA, isStandalone, displayMode } = usePWAStatus();
```

#### useCacheStorage
```tsx
const { cacheSize, isLoading, getCacheSize, clearCache } = useCacheStorage();
```

## Installation

### Android/Chrome
1. Visit the site
2. Click "Install" banner or
3. Menu → "Install DailyCup"

### iOS/Safari
1. Tap Share button
2. Scroll down to "Add to Home Screen"
3. Tap "Add"

## Testing

### Test Offline Mode
1. Open DevTools → Application → Service Workers
2. Check "Offline" checkbox
3. Navigate the app

### Test Install Prompt
1. Open DevTools → Application → Manifest
2. Click "Update" then "Install"

### Test Cache
1. DevTools → Application → Cache Storage
2. Inspect cached resources
3. Test offline functionality

### Test Notifications (requires HTTPS)
```js
// Request permission
const permission = await Notification.requestPermission();

// Show notification (from service worker)
self.registration.showNotification('DailyCup', {
  body: 'Your order is ready!',
  icon: '/assets/image/cup.png',
});
```

## Performance Tips

### Optimize Cache Size
- Service worker caches strategically
- Images cached on-demand
- Old caches auto-deleted on update

### Update Strategy
- Service worker checks for updates hourly
- User prompted to update when available
- Seamless update process

## Browser Support

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14.1+
- ✅ Samsung Internet 14+
- ⚠️ iOS Safari (limited features)

## Production Deployment

### HTTPS Required
PWA features require HTTPS in production:
- Service Workers
- Push Notifications
- Install Prompt

### Icons
Ensure icons exist in `/public/assets/image/`:
- cup.png (192x192 minimum)
- cup.png (512x512 recommended)

### Meta Tags
Already configured in `layout.tsx`:
- theme-color
- apple-mobile-web-app-capable
- viewport settings
- manifest link

## Troubleshooting

### Service Worker Not Registering
- Check console for errors
- Verify `/sw.js` is accessible
- Ensure HTTPS in production

### Install Prompt Not Showing
- Must meet PWA criteria
- User hasn't dismissed recently
- Not already installed

### Offline Page Not Working
- Verify `/offline` route exists
- Check service worker cache
- Test with DevTools offline mode

## Future Enhancements

- [ ] Background sync for orders
- [ ] Periodic background sync
- [ ] Push notification preferences
- [ ] App badging API
- [ ] Share target API
- [ ] File handling
- [ ] Payment Request API integration
