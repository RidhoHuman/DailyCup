# Quick Test: Push Notifications

## Prerequisites
- ✅ VAPID keys configured in .env files
- ✅ Database tables created
- ✅ Composer package `minishlink/web-push` installed
- ✅ User logged in with valid JWT token

## Test 1: Check Push Support

Open browser console at http://localhost:3000:

```javascript
// Test push manager
const pushManager = (await import('/lib/pushManager.ts')).default;

console.log('Supported:', pushManager.isSupported()); // Should be true
console.log('Permission:', pushManager.getPermission()); // default/granted/denied
```

## Test 2: Subscribe to Push

1. Navigate to: `http://localhost:3000/settings/notifications`
2. Toggle "Push Notifications" ON
3. Allow permission when browser prompts
4. Click "Test Push Notification" button
5. ✅ Should see notification appear

## Test 3: Send Push via API

### Using Postman/Thunder Client:

```http
POST http://localhost/DailyCup/webapp/backend/api/notifications/send_push.php
Authorization: Bearer <YOUR_JWT_TOKEN>
Content-Type: application/json

{
  "title": "Order Ready! ☕",
  "message": "Your Cappuccino is ready for pickup",
  "url": "/orders/123",
  "type": "order_updates"
}
```

### Using cURL:

```bash
curl -X POST http://localhost/DailyCup/webapp/backend/api/notifications/send_push.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Notification",
    "message": "This is a test!",
    "url": "/menu"
  }'
```

Expected Response:
```json
{
  "success": true,
  "message": "Push notifications sent",
  "sent": 1,
  "failed": 0,
  "total_subscriptions": 1
}
```

## Test 4: Verify Database

Check subscriptions were saved:

```sql
-- Check push subscriptions
SELECT user_id, endpoint, is_active, created_at 
FROM push_subscriptions 
WHERE is_active = 1;

-- Check notification preferences
SELECT user_id, push_enabled, order_updates, promotions 
FROM notification_preferences;
```

## Test 5: Test Notification Types

1. Go to `/settings/notifications`
2. Disable "Promotions"
3. Send push with `"type": "promotions"`
4. ✅ Should NOT receive notification
5. Send push with `"type": "order_updates"` (if enabled)
6. ✅ Should receive notification

## Test 6: Test Auto-Subscribe on Login

1. Logout from DailyCup
2. Clear site data (Application > Clear site data)
3. Login again
4. Check browser console for: `✅ Subscribed to push notifications`
5. Verify in database:
   ```sql
   SELECT * FROM push_subscriptions ORDER BY id DESC LIMIT 1;
   ```

## Common Issues

### "VAPID keys not configured"
- Check frontend/.env.local has `NEXT_PUBLIC_VAPID_PUBLIC_KEY`
- Check backend/.env has `VAPID_PUBLIC_KEY` and `VAPID_PRIVATE_KEY`
- Restart dev server after adding env vars

### "Web Push library not installed"
```bash
cd backend
composer require minishlink/web-push
```

### "Permission denied"
- Check browser notification settings
- Try in incognito mode
- Clear site data and retry

### "Failed to subscribe"
- Make sure Service Worker is registered
- Check `navigator.serviceWorker.ready`
- Look for errors in browser console

## Success Criteria

All tests should pass:
- [x] Browser supports push notifications
- [x] Can request and grant permission
- [x] Can subscribe to push
- [x] Subscription saved to database
- [x] Can send push from backend API
- [x] Notification appears in browser
- [x] Can customize notification types
- [x] Auto-subscribe works on login
- [x] Test notification button works

**If all tests pass: Phase 12.6 is complete! 🎉**
