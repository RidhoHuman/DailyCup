# Push Notifications Setup (PWA)

## ✅ Yang Sudah Diimplementasi

### Frontend:
- Service Worker (`public/sw.js`) - handle push events & offline caching
- PWA Manifest (`public/manifest.json`) - app metadata
- `usePushNotifications` hook - manage subscriptions
- `PushNotificationToggle` component - UI untuk enable/disable push
- Meta tags PWA di layout.tsx

### Backend:
- `subscribe.php` - save push subscription ke database
- `unsubscribe.php` - disable push subscription
- Tabel `push_subscriptions` sudah ada di database

## ⚠️ Yang Perlu Dilakukan Manual:

### 1. Generate VAPID Keys
Untuk push notifications bekerja, butuh VAPID keys. Cara generate:

**Opsi A: Menggunakan web-push-codelab**
```bash
npm install -g web-push
web-push generate-vapid-keys
```

**Opsi B: Online Generator**
Kunjungi: https://web-push-codelab.glitch.me/

Akan dapat 2 keys:
- Public Key (untuk frontend)
- Private Key (untuk backend, RAHASIA!)

### 2. Update VAPID Keys

**Frontend** - Edit `lib/hooks/usePushNotifications.ts`:
```typescript
const PUBLIC_VAPID_KEY = 'YOUR_PUBLIC_KEY_HERE';
```

**Backend** - Tambahkan ke `.env`:
```
VAPID_PUBLIC_KEY=YOUR_PUBLIC_KEY_HERE
VAPID_PRIVATE_KEY=YOUR_PRIVATE_KEY_HERE
VAPID_SUBJECT=mailto:admin@dailycup.com
```

### 3. Install web-push Library (Backend)
```bash
cd C:\laragon\www\DailyCup
composer require minishlink/web-push
```

### 4. Tambahkan Toggle di Profile
Edit `frontend/app/profile/page.tsx`, tambahkan:
```tsx
import PushNotificationToggle from '@/components/notifications/PushNotificationToggle';

// Di dalam render:
<PushNotificationToggle />
```

## 📝 Catatan Penting:

1. **HTTPS Required**: Push notifications hanya bekerja di HTTPS (kecuali localhost)
2. **Browser Support**: Chrome, Firefox, Edge support penuh. Safari iOS terbatas.
3. **Permission**: User harus approve permission notification
4. **Testing**: Test di localhost dulu, baru deploy ke HTTPS

## 🧪 Cara Test:

1. Buka website di localhost
2. Login
3. Klik toggle "Push Notifications" di Profile
4. Allow permission saat browser minta
5. Buat order baru
6. Push notification akan muncul (bahkan jika tab tertutup)

## ❌ Known Limitations:

- Butuh HTTPS untuk production
- iOS Safari support terbatas
- Butuh backend library untuk send push (belum diimplementasi)
