# Flow Diagram - API Request & Error Analysis

## Normal Flow (CORRECT) ✅

```
User Browser (dailycup.vercel.app)
    |
    | Login Request
    v
Next.js Frontend
    |
    | POST /api/login.php
    v
Next.js Rewrites (next.config.ts)
    |
    | Reads NEXT_PUBLIC_API_URL
    | Value: https://YOUR-NGROK-URL/DailyCup/webapp/backend/api
    |
    | Rewrites to: https://YOUR-NGROK-URL/DailyCup/webapp/backend/api/login.php
    v
Ngrok Tunnel
    |
    v
Laragon (localhost)
    |
    v
backend/api/login.php
    |
    | require_once cors.php (sets CORS headers)
    |
    | Process login
    v
Response (200 OK + JSON)
    |
    | Headers:
    | - Access-Control-Allow-Origin: https://dailycup.vercel.app
    | - Content-Type: application/json
    v
User receives token ✅
```

---

## Current Flow with ERROR ❌

```
User Browser (dailycup.vercel.app)
    |
    | Login Request
    v
Next.js Frontend
    |
    | POST /api/login.php
    v
Next.js Rewrites (next.config.ts)
    |
    | Reads NEXT_PUBLIC_API_URL
    | Value: https://https//YOUR-NGROK-URL/DailyCup/webapp/backend/api
    |                    ↑ ↑
    |                    | |
    |                    Double protocol! ❌
    |
    | Rewrites to: https://https//YOUR-NGROK-URL/DailyCup/webapp/backend/api/login.php
    v
Browser DNS Lookup
    |
    | Try to resolve hostname: "https//YOUR-NGROK-URL"
    |                           ↑ Not a valid hostname!
    v
❌ ERR_NAME_NOT_RESOLVED
    |
    | Cannot find IP address for "https//..."
    |
    v
Network Error ❌
    |
    v
Login fails with:
"APIError: Network error - Backend may be offline"
```

---

## Error Breakdown

### What Happens in the URL

**Environment Variable (WRONG):**
```
https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api
```

**After Rewrite:**
```
Source:      /api/login.php
Destination: https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api/login.php
```

**Browser Interprets As:**
```
Protocol: https://
Hostname: https//decagonal-subpolygonally-brecken.ngrok-free.dev
Path:     /DailyCup/webapp/backend/api/login.php
```

**DNS Lookup Tries:**
```
Looking for: https//decagonal-subpolygonally-brecken.ngrok-free.dev
Result:      ❌ Not Found (invalid hostname)
Error:       ERR_NAME_NOT_RESOLVED
```

---

## Fix Flow

### Step 1: Fix Environment Variable in Vercel

**Before:**
```javascript
NEXT_PUBLIC_API_URL = "https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api"
```

**After:**
```javascript
NEXT_PUBLIC_API_URL = "https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api"
```

### Step 2: Automatic Fix in Code

Even if environment variable has double `https://`, the code now auto-fixes it:

**next.config.ts:**
```typescript
async rewrites() {
  let apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/...';
  
  // AUTO-FIX: Remove duplicate https://
  apiUrl = apiUrl.replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');
  
  return [{ source: '/api/:path*', destination: `${apiUrl}/:path*` }]
}
```

**admin login page:**
```typescript
let apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/...';

// AUTO-FIX: Remove duplicate https://
apiUrl = apiUrl.replace(/^(https?:\/\/)(https?:?\/\/)+/i, '$1');

const response = await fetch(`${apiUrl}/login.php`, { ... });
```

### Step 3: Verification

After deploying, the URL should be:
```
https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api/login.php
```

✅ Valid hostname
✅ DNS can resolve
✅ Request succeeds

---

## Debugging Commands

### Check Environment Variable Locally
```powershell
# Run the validator script
.\check_api_url.ps1
```

### Test Ngrok URL Directly
```powershell
# Test if backend is accessible
curl https://YOUR-NGROK-URL/DailyCup/webapp/backend/api/products.php
```

### Test CORS
```powershell
# Test CORS headers
.\test_cors.ps1
```

### Monitor Ngrok Requests
```
Open ngrok dashboard: http://127.0.0.1:4040
```

---

## Common Mistakes & Solutions

| Mistake | URL Becomes | Error | Solution |
|---------|-------------|-------|----------|
| Double `https://` | `https://https//...` | ERR_NAME_NOT_RESOLVED | Remove duplicate |
| Missing `:` | `https//...` | ERR_NAME_NOT_RESOLVED | Add colon: `https://` |
| Trailing `/` | `.../api/` + `/login.php` = `.../api//login.php` | 404 or 500 | Remove trailing slash |
| Wrong path | `.../api/login.php` → 404 | 404 Not Found | Check Laragon folder structure |
| Ngrok expired | Old URL used | Timeout/DNS error | Get new ngrok URL |

---

## Timeline of Fixes

1. ✅ **First Issue**: CORS headers duplicated
   - **Fixed**: Removed manual CORS from login.php
   
2. ✅ **Second Issue**: CORS not in all endpoints
   - **Fixed**: Added cors.php to create_order.php
   
3. ✅ **Third Issue**: Hardcoded ngrok URL
   - **Fixed**: Use environment variable in next.config.ts
   
4. ✅ **Current Issue**: Double `https://` in env var
   - **Fixed**: Auto-sanitize in next.config.ts & admin login
   - **Action Needed**: Fix env var in Vercel

---

**Next Step**: Go to Vercel and fix the environment variable! 🚀
