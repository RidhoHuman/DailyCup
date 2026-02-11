# 🚨 URGENT FIX - Set Environment Variable di Vercel

## Masalah Saat Ini:
- ❌ CORS error: "No 'Access-Control-Allow-Origin' header"
- ❌ Products fetch mendapat HTML (ngrok warning page)
- ❌ Login gagal

## Root Cause:
**Environment variable `NEXT_PUBLIC_API_URL` BELUM di-set di Vercel**

Tanpa env var ini, Next.js tidak tahu URL backend ngrok Anda!

---

## ⚡ QUICK FIX - 5 MENIT

### Step 1: Login ke Vercel
👉 https://vercel.com/dashboard

### Step 2: Buka Project Settings
1. Pilih project **dailycup**
2. Klik tab **Settings**
3. Klik **Environment Variables** di sidebar kiri

### Step 3: Add New Variable

Klik **Add New** dan isi:

**Key:**
```
NEXT_PUBLIC_API_URL
```

**Value:**
```
https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api
```

**Environment:** 
- ✅ Production
- ✅ Preview
- ✅ Development

### Step 4: Save

Klik **Save**

### Step 5: Redeploy

1. Klik tab **Deployments**
2. Pada deployment terakhir, klik **...** (titik tiga)
3. Klik **Redeploy**
4. **PENTING**: Centang **"Use existing Build Cache"** → **UNCHECK** (disable cache!)
5. Klik **Redeploy**

### Step 6: Wait (2-5 menit)

Tunggu deployment selesai sampai status **"Ready"**

### Step 7: Test

1. Buka: https://dailycup.vercel.app
2. Clear browser cache (Ctrl+Shift+Delete)
3. Hard refresh (Ctrl+Shift+R)
4. Coba login

---

## ✅ Expected Result:

Setelah env var di-set dan redeploy:
- ✅ No more CORS error
- ✅ Products load (no mock data)
- ✅ Login works

---

## 📸 Screenshot Guide:

### Environment Variables Page:
```
┌─────────────────────────────────────────────┐
│ Environment Variables                        │
├─────────────────────────────────────────────┤
│ Key: NEXT_PUBLIC_API_URL                    │
│ Value: https://decagonal-subpolygonally-... │
│ Environments: ☑ Production ☑ Preview ☑ Dev │
└─────────────────────────────────────────────┘
```

---

## ⏱️ Timeline:
1. Set env var: **1 minute**
2. Redeploy: **2-5 minutes**
3. Test: **1 minute**

**Total: ~5-10 minutes**

---

## 🆘 Jika Masih Error:

Hubungi saya dengan screenshot:
1. Vercel Environment Variables page
2. Vercel Deployment Logs
3. Browser Console error

---

**CRITICAL**: Tanpa environment variable ini, Vercel tidak tahu backend URL Anda!
