# Generate VAPID Keys for DailyCup PWA

# VAPID keys are required for Web Push API to send push notifications.
# These keys authenticate your applicationserver with push services.

## Quick Generation (Recommended)

Use the web-push CLI tool via npx:

```bash
npx web-push generate-vapid-keys
```

This will output:
```
=======================================

Public Key:
BEl62iUYgUivxW9IOdXjgUAP_E...

Private Key:
bdZxkJv4BvOq8VmX...

=======================================
```

## Add to Environment Files

### Backend (.env)

Create or update `webapp/backend/.env`:

```env
# VAPID Keys for Push Notifications
VAPID_PUBLIC_KEY="YOUR_PUBLIC_KEY_HERE"
VAPID_PRIVATE_KEY="YOUR_PRIVATE_KEY_HERE"
VAPID_SUBJECT="mailto:admin@dailycup.com"
```

### Frontend (.env.local)

Create or update `webapp/frontend/.env.local`:

```env
# VAPID Public Key (safe to expose to browser)
NEXT_PUBLIC_VAPID_PUBLIC_KEY="YOUR_PUBLIC_KEY_HERE"
```

## Alternative: Manual Generation with OpenSSL

If you prefer using OpenSSL directly:

```bash
# Generate private key (ECDSA P-256)
openssl ecparam -name prime256v1 -genkey -noout -out vapid_private.pem

# Extract public key
openssl ec -in vapid_private.pem -pubout -out vapid_public.pem

# Convert to base64url format (requires additional processing)
# See: https://github.com/web-push-libs/web-push-php
```

## Security Notes

⚠️ **Important Security Guidelines:**

1. **Never commit keys to version control** - Add `.env` and `.env.local` to `.gitignore`
2. **Keep private key SECRET** - Never expose it in client-side code or API responses
3. **Public key is safe to share** - It's used by browsers to encrypt messages
4. **Regenerate keys if compromised** - All subscriptions will need to be recreated
5. **Use different keys for production and development** - Better security isolation

## Testing VAPID Keys

After setting up keys, test them:

```bash
# Test push notification subscription
curl -X POST http://localhost:3000/api/notifications/push_subscribe \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "endpoint": "https://fcm.googleapis.com/fcm/send/...",
    "keys": {
      "p256dh": "...",
      "auth": "..."
    }
  }'
```

## Verification

After configuration, verify:

1. ✅ Backend has both public and private keys in `.env`
2. ✅ Frontend has public key in `.env.local`
3. ✅ Both servers restarted after adding keys
4. ✅ Browser can subscribe to push notifications
5. ✅ Test notification successfully sent

## Troubleshooting

### "VAPID keys not configured" error
- Check that keys are set in backend `.env` file
- Verify environment variables are loaded (`getenv('VAPID_PUBLIC_KEY')`)
- Restart Apache/PHP server after adding keys

### Push subscription fails
- Verify public key matches in frontend and backend
- Check browser console for detailed error messages
- Ensure HTTPS is being used (required for push notifications)

### Notification not received
- Verify subscription is saved in `push_subscriptions` table
- Check push notification service is running
- Verify VAPID private key is correct
- Check browser notification permissions

## Next Steps

After generating and configuring VAPID keys:

1. ✅ Add keys to environment files
2. ✅ Restart servers (backend + frontend)
3. ✅ Test push notification subscription in browser
4. ✅ Send test notification via admin panel
5. ✅ Verify notification appears on device

For more information, see the [PWA Implementation Guide](./PWA_IMPLEMENTATION_GUIDE.md).
