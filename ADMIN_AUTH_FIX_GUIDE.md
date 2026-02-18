# Admin Panel Authentication Fix Guide

## Problem
Admin panel features (analytics, geocode issues, all tables) are empty and showing 403 Forbidden errors despite valid login.

## Root Cause
JWT token validation failing because:
1. PHP backend needs to reload environment variables (JWT_SECRET)
2. Old token in browser localStorage was generated before .env updates
3. JWT debugging was not enabled to see error details

## Solution Applied

### 1. ✅ Enabled JWT Debugging
Added to all .env files:
- `JWT_DEBUG=1` - Shows detailed JWT verification logs
- `APP_DEBUG=1` - Shows general application errors

Files updated:
- `.env`
- `webapp/backend/.env`
- `webapp/backend/api/.env`

### 2. 📝 Created Debug Tools

**Backend Debug Endpoint:** `webapp/backend/api/test_jwt.php`
- Shows JWT_SECRET configuration
- Validates token signature
- Displays decoded token payload
- Shows recent JWT errors from PHP log

**PowerShell Test Scripts:**
- `fix_admin_auth.ps1` - Enable debugging with instructions
- `test_jwt_token.ps1` - Test JWT token validation end-to-end

## Fix Steps (Follow These)

### Step 1: Restart Laragon (REQUIRED)
PHP needs to reload .env files with JWT_DEBUG=1:

1. Open **Laragon**
2. Click **"Stop All"**
3. Wait 5 seconds
4. Click **"Start All"**

### Step 2: Verify Ngrok is Running
Ensure backend is accessible:

```powershell
# Check if ngrok is running
Get-Process ngrok -ErrorAction SilentlyContinue

# If not running, start it:
ngrok http 80 --host-header=rewrite
```

Your ngrok URL: `https://decagonal-subpolygonally-brecken.ngrok-free.dev`

### Step 3: Clear Browser Storage & Re-login (REQUIRED)
Old token was generated before .env updates:

1. Open browser DevTools (F12) on `https://dailycup.vercel.app`
2. Go to **Application** → **Local Storage** → `https://dailycup.vercel.app`
3. Find and **DELETE** the `dailycup-auth` key
4. **Close DevTools**
5. Go to: `https://dailycup.vercel.app/login`
6. **Login with admin credentials**
7. Go to: `https://dailycup.vercel.app/admin/analytics`

### Step 4: Test JWT Token Validation
Run the PowerShell test script:

```powershell
# Test with your current token
.\test_jwt_token.ps1

# When prompted, paste your token from localStorage
# (Get it from DevTools → Application → Local Storage → dailycup-auth → state.token)
```

**Expected Output if Working:**
```
✓✓✓ JWT Token is VALID!
✓✓✓ Analytics API is WORKING!
```

**If Still Failing (403/401):**
```powershell
# Debug backend directly
$NgrokUrl = "https://decagonal-subpolygonally-brecken.ngrok-free.dev"
Invoke-RestMethod -Uri "$NgrokUrl/DailyCup/webapp/backend/api/test_jwt.php"
```

### Step 5: Check Backend Logs
If still having issues, check PHP error log for JWT debug messages:

**Laragon:**
1. Laragon → Menu → Apache/Nginx → Error Log
2. Search for lines containing: `JWT:DEBUG`

**Or check file directly:**
- `C:\laragon\bin\apache\apache-2.4.xx\logs\error.log` (Apache)
- `C:\laragon\bin\nginx\nginx-1.xx.x\logs\error.log` (Nginx)
- `webapp\backend\logs\login_debug.log` (Custom login log)

**Look for:**
```
JWT:DEBUG verify - signature mismatch
JWT:DEBUG getUser - Authorization header missing
JWT:DEBUG verify - token expired
```

## Common Issues & Solutions

### Issue 1: Token Signature Mismatch
**Log:** `JWT:DEBUG verify - signature mismatch`

**Cause:** JWT_SECRET different between login and verification

**Solution:**
1. Verify JWT_SECRET is same in all .env files (already done ✅)
2. Restart Laragon to reload .env (Step 1)
3. Clear localStorage and re-login (Step 3)

### Issue 2: Authorization Header Missing
**Log:** `JWT:DEBUG getUser - Authorization header missing`

**Cause:** Token not being sent to backend

**Solution:**
1. Check `lib/api-client.ts` authorization header injection (already implemented ✅)
2. Verify token exists in localStorage: `localStorage.getItem('dailycup-auth')`
3. Check console for API errors

### Issue 3: Token Expired
**Log:** `JWT:DEBUG verify - token expired`

**Cause:** Token older than 24 hours

**Solution:**
1. Clear localStorage
2. Login again
3. Tokens expire after 24 hours (JWT expiry setting)

### Issue 4: CORS Errors
**Browser Console:** `CORS policy: No 'Access-Control-Allow-Origin'`

**Solution:**
1. Check `webapp/backend/cors.php` includes your Vercel domain
2. Verify Ngrok is running with `--host-header=rewrite` flag
3. Check if `allowedDevOrigins` in `next.config.js` is set (already done ✅)

## Verification Checklist

After following all steps, verify:

- [ ] Laragon services restarted
- [ ] Ngrok is running
- [ ] Browser localStorage cleared
- [ ] Logged in with admin account
- [ ] Admin analytics page shows data (not empty)
- [ ] No 403/401 errors in browser Console
- [ ] No JWT errors in Network tab

## Testing Commands

```powershell
# Test backend debug endpoint
.\test_jwt_token.ps1

# Or manually test analytics API
$token = "YOUR_TOKEN_FROM_LOCALSTORAGE"
$headers = @{ "Authorization" = "Bearer $token" }
$url = "https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api/admin/analytics.php?action=summary"
Invoke-RestMethod -Uri $url -Headers $headers -Method GET | ConvertTo-Json
```

## Expected Results

### Before Fix
- ❌ Analytics page shows "0" for all KPIs
- ❌ Geocode failures table empty
- ❌ Console shows: `403 Forbidden` on analytics API
- ❌ Console shows: `401 Unauthorized` on notification APIs

### After Fix
- ✅ Analytics page shows real data (revenue, orders, etc.)
- ✅ Geocode failures table shows failed attempts
- ✅ All admin tables loading correctly
- ✅ No authentication errors in console

## Files Modified

### Environment Configuration
- `.env` - Added JWT_DEBUG=1, APP_DEBUG=1
- `webapp/backend/.env` - Added JWT_DEBUG=1, APP_DEBUG=1
- `webapp/backend/api/.env` - Added JWT_DEBUG=1, APP_DEBUG=1

### Debug Tools Created
- `webapp/backend/api/test_jwt.php` - Backend JWT debug endpoint
- `fix_admin_auth.ps1` - Setup script with instructions
- `test_jwt_token.ps1` - Client-side JWT test script
- `ADMIN_AUTH_FIX_GUIDE.md` - This guide

## Next Steps After Fix

1. **Commit Changes:**
```powershell
git add .env* webapp/backend/api/test_jwt.php
git commit -m "fix: Enable JWT debugging and add test tools for admin auth issues"
git push
```

2. **Monitor Logs:**
- Keep JWT_DEBUG=1 enabled until issue is fully resolved
- Monitor PHP error log for any JWT errors
- Check browser console for network errors

3. **Production Security:**
After fixing, for production deployment:
- Set `JWT_DEBUG=0` in production .env
- Set `APP_DEBUG=0` in production
- Use stronger JWT_SECRET for production
- Consider increasing JWT expiry for admin users

## Support

If still having issues after following all steps:
1. Run `.\test_jwt_token.ps1` and share output
2. Check PHP error log for JWT:DEBUG messages
3. Share browser Network tab screenshot showing 403/401 error
4. Verify JWT_SECRET is exact same in all .env files:
   ```powershell
   Select-String -Path "*.env" -Pattern "JWT_SECRET" -Recurse
   ```

## Summary

**Problem:** JWT token validation failing → 403 Forbidden errors
**Root Cause:** PHP not reloaded after .env updates, old token in browser
**Solution:** Enable debugging, restart Laragon, clear localStorage, re-login
**Verification:** Run test script and check analytics page loads with data

**Quick Fix Command:**
```powershell
# 1. Restart Laragon manually (Stop All → Start All)
# 2. Then run:
.\test_jwt_token.ps1
# 3. Clear browser localStorage and login again
# 4. Test admin panel
```
