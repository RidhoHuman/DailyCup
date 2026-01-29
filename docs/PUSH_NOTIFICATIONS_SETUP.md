# 🔔 Phase 12.6: Web Push Notifications - Setup Guide

## Overview
Push Notifications memungkinkan DailyCup mengirim notifikasi ke browser user bahkan saat mereka tidak membuka website.

## ✅ What's Implemented

### 1. **VAPID Keys Generation**
- ✅ Script untuk generate VAPID keys
- ✅ Public & Private keys untuk Web Push API
- 📁 `frontend/scripts/generate-vapid-keys.js`

### 2. **Service Worker Push Handler**
- ✅ Push event listener di Service Worker
- ✅ Notification click handler dengan deep linking
- ✅ Support untuk custom notification data
- 📁 `frontend/public/sw.js`

### 3. **Frontend Push Manager**
- ✅ PushNotificationManager class (singleton)
- ✅ Subscribe/Unsubscribe functionality
- ✅ Permission handling
- ✅ VAPID key conversion (Base64 → Uint8Array)
- ✅ Test notification feature
- 📁 `frontend/lib/pushManager.ts`

### 4. **Backend API Endpoints**
- ✅ `/api/notifications/push_subscribe.php` - Subscribe/Unsubscribe
- ✅ `/api/notifications/send_push.php` - Send push notifications
- ✅ `/api/notifications/preferences.php` - User preferences
- 📁 `backend/api/notifications/`

### 5. **Database Tables**
- ✅ `push_subscriptions` - Store push subscription data
- ✅ `notification_preferences` - User notification settings
- 📁 `backend/sql/push_notifications.sql`

### 6. **Notification Settings Page**
- ✅ Push/Email toggle
- ✅ Notification types (orders, payments, promos, etc.)
- ✅ Quiet hours feature
- ✅ Test notification button
- 📁 `frontend/app/settings/notifications/page.tsx`

### 7. **Auto-Subscribe Integration**
- ✅ Auto-subscribe saat user login
- ✅ Check user preferences sebelum subscribe
- ✅ Integrated dengan NotificationProvider
- 📁 `frontend/components/NotificationProvider.tsx`

---

## 🚀 Setup Instructions

### Step 1: Generate VAPID Keys

```bash
cd frontend
node scripts/generate-vapid-keys.js
```

Output akan seperti ini:
```
🔑 Generating VAPID Keys for Web Push Notifications...

Public Key: BGRA2RFTIlxrq80ThAUkDd4DTo923jsHRmXQ4P6qScFe...
Private Key: FRYYq0E2ZppHWBtgG3M_KOdgnjmvjD8YnCNMfscoyFg
```

### Step 2: Add to Environment Files

#### Frontend `.env.local`:
```env
NEXT_PUBLIC_VAPID_PUBLIC_KEY=BGRA2RFTIlxrq80ThAUkDd4DTo923jsHRmXQ4P6qScFeMxqT9Hnl_gIorFgXEe-ACAdfTbFXgxBkHjSKAq6XZI4
```

#### Backend `.env`:
```env
VAPID_PUBLIC_KEY=BGRA2RFTIlxrq80ThAUkDd4DTo923jsHRmXQ4P6qScFeMxqT9Hnl_gIorFgXEe-ACAdfTbFXgxBkHjSKAq6XZI4
VAPID_PRIVATE_KEY=FRYYq0E2ZppHWBtgG3M_KOdgnjmvjD8YnCNMfscoyFg
VAPID_SUBJECT=mailto:admin@dailycup.com
```

### Step 3: Install PHP Web Push Library

```bash
cd backend
composer require minishlink/web-push
```

### Step 4: Setup Database

```bash
# Run SQL migration
mysql -u root -p dailycup < backend/sql/push_notifications.sql
```

Or manually execute:
```sql
-- Creates: push_subscriptions, notification_preferences tables
-- See: backend/sql/push_notifications.sql
```

### Step 5: Restart Development Server

```bash
# Frontend
cd frontend
npm run dev

# Backend - restart Laragon/Apache
```

---

## 📝 How to Use

### For Users:

1. **Enable Push Notifications:**
   - Go to `/settings/notifications`
   - Toggle "Push Notifications" ON
   - Allow notification permission in browser
   - Click "Test Push Notification" button

2. **Customize Notification Types:**
   - Enable/disable specific types (orders, payments, promos, etc.)
   - Set quiet hours to avoid notifications at night
   - Toggle email notifications separately

### For Developers:

#### Subscribe User to Push:
```typescript
import pushManager from '@/lib/pushManager';

// Initialize with service worker
const registration = await navigator.serviceWorker.ready;
await pushManager.initialize(registration);

// Subscribe
const subscription = await pushManager.subscribe();

// Send to backend
await api.post('/notifications/push_subscribe.php', subscription);
```

#### Send Push Notification (Backend):
```php
POST /api/notifications/send_push.php
Authorization: Bearer <JWT_TOKEN>

{
  "title": "New Order!",
  "message": "Your coffee is ready for pickup",
  "url": "/orders/123",
  "icon": "/assets/image/cup.png",
  "type": "order_updates",
  "user_id": 5  // Optional, for specific user (admin only for broadcast)
}
```

#### Check Subscription Status:
```typescript
const isSubscribed = await pushManager.isSubscribed();
const permission = pushManager.getPermission();
const isSupported = pushManager.isSupported();
```

---

## 🧪 Testing Guide

### Test 1: Browser Notification Permission
1. Open browser console
2. Check: `pushManager.isSupported()` → should return `true`
3. Check: `pushManager.getPermission()` → `'default'`, `'granted'`, or `'denied'`

### Test 2: Subscribe to Push
1. Login to DailyCup
2. Go to `/settings/notifications`
3. Toggle "Push Notifications" ON
4. Allow permission when prompted
5. Click "Test Push Notification"
6. Should see notification appear

### Test 3: Send Push from Backend
1. Get JWT token from login
2. Use Postman/Thunder Client:
```bash
POST http://localhost/DailyCup/webapp/backend/api/notifications/send_push.php
Authorization: Bearer <your-token>
Content-Type: application/json

{
  "title": "Test Notification",
  "message": "This is a test push notification!",
  "url": "/menu"
}
```

### Test 4: Notification Types Filter
1. Disable "Promotions" in settings
2. Send push with `"type": "promotions"`
3. Should NOT receive notification
4. Send push with `"type": "order_updates"`
5. Should receive notification (if enabled)

### Test 5: Quiet Hours
1. Enable "Quiet Hours" (e.g., 22:00 - 08:00)
2. Set system time within quiet hours
3. Send push notification
4. Backend should skip sending during quiet hours

---

## 🔍 Troubleshooting

### "Push notifications are not supported"
- **Cause:** Browser tidak support atau HTTPS not enabled
- **Fix:** 
  - Use Chrome/Firefox/Edge terbaru
  - localhost OK untuk testing, production butuh HTTPS

### "VAPID keys not configured"
- **Cause:** Environment variables belum di-set
- **Fix:** 
  - Check `.env.local` (frontend) dan `.env` (backend)
  - Restart dev server setelah update .env

### "Web Push library not installed"
- **Cause:** Composer package belum terinstall
- **Fix:**
  ```bash
  cd backend
  composer require minishlink/web-push
  ```

### "Failed to subscribe"
- **Cause:** Service Worker belum ready atau permission denied
- **Fix:**
  - Check browser console untuk errors
  - Clear site data dan try again
  - Grant notification permission

### Notifications not appearing
- **Cause:** Browser notification settings atau quiet hours
- **Fix:**
  - Check browser notification settings (allow for localhost)
  - Check quiet hours in user preferences
  - Check notification type is enabled

---

## 📊 Database Schema

### `push_subscriptions`
```sql
CREATE TABLE push_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    p256dh_key VARCHAR(255) NOT NULL,
    auth_key VARCHAR(255) NOT NULL,
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    last_used_at TIMESTAMP
);
```

### `notification_preferences`
```sql
CREATE TABLE notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    push_enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    order_updates BOOLEAN DEFAULT TRUE,
    payment_updates BOOLEAN DEFAULT TRUE,
    promotions BOOLEAN DEFAULT TRUE,
    new_products BOOLEAN DEFAULT FALSE,
    reviews BOOLEAN DEFAULT TRUE,
    admin_messages BOOLEAN DEFAULT TRUE,
    quiet_hours_enabled BOOLEAN DEFAULT FALSE,
    quiet_hours_start TIME DEFAULT '22:00:00',
    quiet_hours_end TIME DEFAULT '08:00:00'
);
```

---

## 🎯 Features Checklist

- [x] Generate VAPID keys
- [x] Service Worker push event handler
- [x] Frontend PushManager class
- [x] Subscribe/Unsubscribe API
- [x] Send push notification API
- [x] Notification preferences API
- [x] Settings page UI
- [x] Auto-subscribe on login
- [x] Notification type filtering
- [x] Quiet hours support
- [x] Test notification feature
- [x] Database migrations
- [x] Error handling & validation
- [x] TypeScript type safety

---

## 🚀 Next Steps (Optional Enhancements)

1. **Push Notification Analytics**
   - Track delivery rate, click rate, conversion
   - Dashboard untuk monitoring

2. **Rich Notifications**
   - Action buttons (Accept/Decline)
   - Images in notifications
   - Custom sounds

3. **Notification Templates**
   - Pre-defined templates untuk common events
   - Dynamic data substitution

4. **Multi-Device Support**
   - Manage multiple devices per user
   - Send to all devices or specific device

5. **Notification History**
   - View sent notifications
   - Resend failed notifications

---

## 📚 Resources

- [Web Push Protocol](https://web.dev/push-notifications-overview/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Notification API](https://developer.mozilla.org/en-US/docs/Web/API/Notifications_API)
- [web-push-php Library](https://github.com/web-push-libs/web-push-php)

---

**Phase 12.6 Complete!** 🎉

Push Notifications sekarang sudah fully functional dan siap untuk production use!
