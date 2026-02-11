# 🔔 PUSH NOTIFICATIONS IMPLEMENTATION GUIDE
**Web Push Notifications for DailyCup Coffee**

---

## 📋 OVERVIEW

Push notifications allow you to send real-time alerts to users even when they're not actively using your website. This guide covers implementing Web Push Notifications using **Firebase Cloud Messaging (FCM)** - completely FREE.

**Use Cases:**
- 🛎️ Order status updates (confirmed, ready, out for delivery)
- 🚴 Kurir assignment alerts
- 📦 Delivery completion notifications
- 💰 Refund processing updates
- ⭐ Review reminders

---

## 🎯 BENEFITS

### For Customers
- Real-time order updates without checking website
- Delivery ETA alerts
- Promotional offers
- Review reminders after order completion

### For Kurir
- New order assignments instantly
- Order ready for pickup alerts
- Customer messages/calls

### For Business
- 30-50% higher engagement rates
- Reduced customer support inquiries
- Better customer experience
- Increased order completion rates

---

## 🚀 IMPLEMENTATION STEPS

### Step 1: Create Firebase Project

1. Visit https://console.firebase.google.com/
2. Click "Add Project"
3. Enter project name: "DailyCup Coffee"
4. Disable Google Analytics (optional)
5. Click "Create Project"

---

### Step 2: Add Web App to Firebase

1. In Firebase Console, click gear icon → Project Settings
2. Scroll to "Your apps" section
3. Click web icon (</>)
4. Register app name: "DailyCup Web"
5. Copy the configuration object

**Config Example:**
```javascript
const firebaseConfig = {
  apiKey: "AIzaSyAbc123...",
  authDomain: "dailycup-coffee.firebaseapp.com",
  projectId: "dailycup-coffee",
  storageBucket: "dailycup-coffee.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123",
  measurementId: "G-ABC123"
};
```

---

### Step 3: Get Server Key

1. Go to Project Settings → Cloud Messaging
2. Find "Server key" (Legacy)
3. Copy this key - you'll need it for PHP

---

### Step 4: Create Service Worker

Create `firebase-messaging-sw.js` in root directory:

```javascript
// firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Initialize Firebase
firebase.initializeApp({
  apiKey: "YOUR_API_KEY",
  authDomain: "dailycup-coffee.firebaseapp.com",
  projectId: "dailycup-coffee",
  storageBucket: "dailycup-coffee.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123"
});

const messaging = firebase.messaging();

// Handle background notifications
messaging.onBackgroundMessage((payload) => {
  console.log('Received background message:', payload);
  
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/assets/images/logo/icon-192.png',
    badge: '/assets/images/logo/badge-72.png',
    data: {
      url: payload.data?.url || '/'
    },
    requireInteraction: true, // Keep notification until user interacts
    vibrate: [200, 100, 200] // Vibration pattern
  };

  return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  const urlToOpen = event.notification.data.url || '/';
  
  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then((windowClients) => {
      // Check if there's already a window/tab open
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus();
        }
      }
      // If not, open new window
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});
```

---

### Step 5: Initialize Firebase in Main App

Add to `includes/header.php` (before closing `</head>`):

```html
<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>

<script>
// Initialize Firebase
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "dailycup-coffee.firebaseapp.com",
  projectId: "dailycup-coffee",
  storageBucket: "dailycup-coffee.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123"
};

firebase.initializeApp(firebaseConfig);

const messaging = firebase.messaging();

// Request permission and get token
function initPushNotifications() {
  if (!('Notification' in window)) {
    console.log('This browser does not support notifications');
    return;
  }
  
  Notification.requestPermission().then((permission) => {
    if (permission === 'granted') {
      console.log('Notification permission granted');
      
      // Get registration token
      messaging.getToken({
        vapidKey: 'YOUR_VAPID_KEY' // Get from Firebase Console → Cloud Messaging → Web Push certificates
      }).then((currentToken) => {
        if (currentToken) {
          console.log('Token:', currentToken);
          // Save token to server
          saveFCMToken(currentToken);
        } else {
          console.log('No registration token available');
        }
      }).catch((err) => {
        console.log('An error occurred while retrieving token:', err);
      });
    } else {
      console.log('Unable to get permission to notify');
    }
  });
}

// Save FCM token to server
function saveFCMToken(token) {
  fetch('<?php echo SITE_URL; ?>/api/save_fcm_token.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ token: token })
  })
  .then(response => response.json())
  .then(data => console.log('Token saved:', data))
  .catch(error => console.error('Error saving token:', error));
}

// Handle foreground messages
messaging.onMessage((payload) => {
  console.log('Message received:', payload);
  
  // Show notification even when app is in foreground
  new Notification(payload.notification.title, {
    body: payload.notification.body,
    icon: '/assets/images/logo/icon-192.png',
    badge: '/assets/images/logo/badge-72.png',
    data: { url: payload.data?.url || '/' }
  }).onclick = function() {
    window.location.href = this.data.url;
  };
});

// Initialize on page load (only for logged in users)
<?php if (isset($_SESSION['user_id']) || isset($_SESSION['kurir_id'])): ?>
  document.addEventListener('DOMContentLoaded', function() {
    initPushNotifications();
  });
<?php endif; ?>
</script>
```

---

### Step 6: Create Database Table for Tokens

```sql
CREATE TABLE IF NOT EXISTS `fcm_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `kurir_id` int(11) DEFAULT NULL,
  `token` text NOT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `kurir_id` (`kurir_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kurir_id`) REFERENCES `kurir` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### Step 7: Create API to Save Tokens

Create `api/save_fcm_token.php`:

```php
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token required']);
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'] ?? null;
$kurirId = $_SESSION['kurir_id'] ?? null;

if (!$userId && !$kurirId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get device info
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

try {
    // Check if token already exists
    if ($userId) {
        $stmt = $db->prepare("SELECT id FROM fcm_tokens WHERE user_id = ? AND token = ?");
        $stmt->execute([$userId, $token]);
    } else {
        $stmt = $db->prepare("SELECT id FROM fcm_tokens WHERE kurir_id = ? AND token = ?");
        $stmt->execute([$kurirId, $token]);
    }
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Token already registered']);
        exit;
    }
    
    // Insert new token
    $stmt = $db->prepare("INSERT INTO fcm_tokens (user_id, kurir_id, token, device_info) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $kurirId, $token, $userAgent]);
    
    echo json_encode(['success' => true, 'message' => 'Token saved successfully']);
} catch (PDOException $e) {
    error_log("FCM Token Save Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
```

---

### Step 8: Create Push Notification Sender Function

Add to `includes/functions.php`:

```php
<?php
function sendPushNotification($userId, $title, $body, $url = '/', $kurirMode = false) {
    $db = getDB();
    
    // Get FCM tokens for user
    if ($kurirMode) {
        $stmt = $db->prepare("SELECT token FROM fcm_tokens WHERE kurir_id = ?");
    } else {
        $stmt = $db->prepare("SELECT token FROM fcm_tokens WHERE user_id = ?");
    }
    $stmt->execute([$userId]);
    $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tokens)) {
        return false; // No tokens found
    }
    
    // Firebase Cloud Messaging API endpoint
    $url_api = 'https://fcm.googleapis.com/fcm/send';
    
    // Server key from Firebase Console
    $serverKey = 'YOUR_FIREBASE_SERVER_KEY'; // Replace with actual key
    
    $data = [
        'registration_ids' => $tokens,
        'notification' => [
            'title' => $title,
            'body' => $body,
            'icon' => SITE_URL . '/assets/images/logo/icon-192.png',
            'badge' => SITE_URL . '/assets/images/logo/badge-72.png',
            'click_action' => SITE_URL . $url
        ],
        'data' => [
            'url' => SITE_URL . $url,
            'timestamp' => time()
        ],
        'priority' => 'high'
    ];
    
    $headers = [
        'Authorization: key=' . $serverKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode === 200) {
        error_log("Push notification sent successfully: $title");
        return true;
    } else {
        error_log("Push notification failed: " . $result);
        return false;
    }
}
?>
```

---

### Step 9: Integrate Push Notifications in Order Flow

#### When Order Confirmed
```php
// admin/orders/index.php (when admin confirms order)
if ($action === 'confirm_order') {
    // ... update order status ...
    
    // Send push notification to customer
    sendPushNotification(
        $order['user_id'],
        'Pesanan Dikonfirmasi! ✅',
        'Pesanan #' . $order['order_number'] . ' sedang diproses. Kami akan memberitahu Anda saat pesanan siap.',
        '/customer/order_detail.php?id=' . $orderId
    );
}
```

#### When Kurir Assigned
```php
// Auto-assign kurir script
if ($kurirAssigned) {
    // Notify customer
    sendPushNotification(
        $order['user_id'],
        'Kurir Ditugaskan! 🚴',
        'Kurir ' . $kurir['name'] . ' akan mengantarkan pesanan Anda.',
        '/customer/track_order.php?id=' . $orderId
    );
    
    // Notify kurir
    sendPushNotification(
        $kurirId,
        'Order Baru! 📦',
        'Anda mendapat order baru #' . $order['order_number'] . '. Tap untuk melihat detail.',
        '/kurir/index.php',
        true // kurir mode
    );
}
```

#### When Order Out for Delivery
```php
// kurir/update_status.php (when status = delivering)
if ($newStatus === 'delivering') {
    sendPushNotification(
        $order['user_id'],
        'Pesanan Dalam Perjalanan! 🚚',
        'Kurir sedang menuju lokasi Anda. Lacak pengiriman secara real-time.',
        '/customer/track_order.php?id=' . $orderId
    );
}
```

#### When Order Completed
```php
// When delivery completed
if ($newStatus === 'completed') {
    sendPushNotification(
        $order['user_id'],
        'Pesanan Telah Tiba! 🎉',
        'Terima kasih telah berbelanja di DailyCup. Jangan lupa berikan review!',
        '/customer/order_detail.php?id=' . $orderId
    );
}
```

#### When Refund Processed
```php
// admin/returns/index.php (when refund approved)
if ($action === 'approve_refund') {
    sendPushNotification(
        $refund['user_id'],
        'Refund Disetujui! 💰',
        'Refund Rp ' . number_format($refund['amount']) . ' telah diproses. Dana akan dikembalikan dalam 1-3 hari kerja.',
        '/customer/order_detail.php?id=' . $refund['order_id']
    );
}
```

---

## 📱 TESTING PUSH NOTIFICATIONS

### Local Testing (HTTPS Required)

Since Service Workers require HTTPS, you have 2 options for local testing:

#### Option 1: Use localhost (already supports Service Workers)
```
http://localhost/DailyCup
```

#### Option 2: Use ngrok for public URL
```bash
# Install ngrok from https://ngrok.com/
ngrok http 80

# Use the HTTPS URL provided
# Example: https://abc123.ngrok.io
```

### Test Steps
1. Open browser DevTools (F12)
2. Go to Application → Service Workers
3. Check if `firebase-messaging-sw.js` is registered
4. Go to Console tab
5. Look for "Token: ..." message
6. Trigger an action (order confirm, etc.)
7. Check if notification appears

---

## 🎨 NOTIFICATION CUSTOMIZATION

### Add Custom Actions
```javascript
// In firebase-messaging-sw.js
const notificationOptions = {
  body: payload.notification.body,
  icon: '/assets/images/logo/icon-192.png',
  badge: '/assets/images/logo/badge-72.png',
  actions: [
    {
      action: 'view',
      title: 'Lihat Order',
      icon: '/assets/images/icons/view.png'
    },
    {
      action: 'track',
      title: 'Lacak Pengiriman',
      icon: '/assets/images/icons/track.png'
    }
  ]
};

self.addEventListener('notificationclick', (event) => {
  if (event.action === 'track') {
    clients.openWindow('/customer/track_order.php?id=' + event.notification.data.orderId);
  } else if (event.action === 'view') {
    clients.openWindow('/customer/order_detail.php?id=' + event.notification.data.orderId);
  }
  event.notification.close();
});
```

### Add Sound
```javascript
const notificationOptions = {
  // ... other options
  sound: '/assets/sounds/notification.mp3',
  vibrate: [200, 100, 200, 100, 200]
};
```

### Add Image
```javascript
const notificationOptions = {
  // ... other options
  image: '/assets/images/notifications/order-ready.jpg'
};
```

---

## 📊 ANALYTICS & MONITORING

### Track Notification Performance

Add to `fcm_tokens` table:
```sql
ALTER TABLE fcm_tokens 
ADD COLUMN last_used TIMESTAMP NULL,
ADD COLUMN notification_count INT DEFAULT 0;
```

Update function:
```php
function sendPushNotification($userId, $title, $body, $url = '/', $kurirMode = false) {
    // ... existing code ...
    
    if ($httpcode === 200) {
        // Update statistics
        $stmt = $db->prepare("UPDATE fcm_tokens SET last_used = NOW(), notification_count = notification_count + 1 WHERE " . ($kurirMode ? "kurir_id" : "user_id") . " = ?");
        $stmt->execute([$userId]);
        
        return true;
    }
    return false;
}
```

---

## ⚠️ IMPORTANT NOTES

1. **HTTPS Requirement**
   - Push notifications REQUIRE HTTPS in production
   - Localhost works without HTTPS (for testing only)

2. **Browser Support**
   - Chrome: ✅ Full support
   - Firefox: ✅ Full support
   - Safari: ✅ Supported (iOS 16.4+, macOS 13+)
   - Edge: ✅ Full support
   - Internet Explorer: ❌ Not supported

3. **User Permission**
   - Always ask permission at appropriate time
   - Don't spam notifications (max 3-5 per day)
   - Allow users to opt-out

4. **Token Management**
   - Tokens can expire - handle gracefully
   - Clean up invalid tokens periodically
   - One user can have multiple tokens (multiple devices)

5. **Rate Limits**
   - FCM free tier: Unlimited notifications
   - Consider batching for multiple users

---

## 🔧 TROUBLESHOOTING

### Notification Not Showing
```
1. Check browser permission: Settings → Privacy → Notifications
2. Verify Service Worker registered: DevTools → Application → Service Workers
3. Check FCM token saved: Check fcm_tokens table
4. Verify Firebase config correct
5. Check server key in PHP function
```

### Service Worker Not Registered
```
1. Ensure file named exactly: firebase-messaging-sw.js
2. Place in root directory (not in subdirectory)
3. Check HTTPS/localhost requirement
4. Clear browser cache and reload
```

### Token Not Saved
```
1. Check user logged in
2. Verify API endpoint working
3. Check browser console for errors
4. Ensure database table exists
```

---

## 💰 COST ANALYSIS

**Firebase Cloud Messaging:**
- ✅ Completely FREE
- ✅ Unlimited notifications
- ✅ Unlimited devices
- ✅ No hidden fees
- ✅ Google infrastructure reliability

**Alternative Options:**
- OneSignal: Free up to 10,000 subscribers
- Pusher: Free up to 200 connections
- Custom WebSocket: Server cost only

**Recommendation:** Use FCM (FREE + reliable)

---

## 📚 RESOURCES

- **FCM Documentation:** https://firebase.google.com/docs/cloud-messaging
- **Web Push Protocol:** https://developers.google.com/web/fundamentals/push-notifications
- **Service Workers:** https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API

---

**Implementation Time:** 2-4 hours  
**Difficulty:** Medium  
**Cost:** FREE  
**Benefit:** HIGH (30-50% better engagement)

🔔 **START ENGAGING USERS WITH REAL-TIME NOTIFICATIONS!**
