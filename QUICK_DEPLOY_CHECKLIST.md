# 🚀 Quick Deploy Checklist

## Pre-Deployment ✅

- [x] Code menggunakan `/api/*` path (bukan hardcoded URL)
- [x] `.env.local` sudah di `.gitignore`
- [x] `.env.example` sudah dibuat untuk dokumentasi
- [ ] Test production build lokal: `npm run build && npm start`

---

## Vercel Setup 🌐

### 1️⃣ Push ke GitHub
```bash
cd c:\laragon\www\DailyCup\webapp\frontend
git add .
git commit -m "feat: production-ready with environment configs"
git push origin main
```

### 2️⃣ Connect Vercel
1. Buka [vercel.com](https://vercel.com)
2. Import GitHub repo
3. Root Directory: **`webapp/frontend`**
4. Framework: **Next.js** (auto-detect)

### 3️⃣ Set Environment Variables

**WAJIB di Vercel Dashboard:**

```
NEXT_PUBLIC_API_URL = https://your-ngrok-url.ngrok-free.dev/DailyCup/webapp/backend/api
NEXT_PUBLIC_VAPID_PUBLIC_KEY = BJZ2QjWbziK5U68pPrWDIcSB8Sm9ONFwVCi_U7LTJkyvh-Lp5nBMw1Pgq3SIaA0txvKVOHX0YdSQ5Qi8xn7e4wI
NEXT_PUBLIC_DEBUG = false
NEXT_TELEMETRY_DISABLED = 1
```

**Ganti `your-ngrok-url.ngrok-free.dev` dengan:**
- Ngrok URL Anda (jika pakai ngrok)
- ATAU URL hosting PHP Anda

### 4️⃣ Deploy
Klik **Deploy** → Tunggu 2-3 menit → ✅ Done!

---

## Backend Setup 🐘

### Option A: Ngrok (Testing Only)
```bash
# Jalankan di PC lokal (harus selalu running)
ngrok http 80 --domain=your-subdomain.ngrok-free.dev
```

⚠️ **Ngrok free tier:**
- Reset setiap 8 jam
- Harus update Vercel env vars setiap restart
- **TIDAK untuk production!**

### Option B: PHP Hosting (Production)
Upload PHP backend ke:
- cPanel hosting
- DigitalOcean ($6/month)
- AWS Lightsail ($3.50/month)
- Heroku PHP ($7/month)

✅ **Recommended untuk production**

---

## Post-Deployment Checklist ✅

### Test Functionality
- [ ] Buka `https://your-project.vercel.app`
- [ ] Login sebagai admin
- [ ] Test Analytics page (no errors)
- [ ] Test Orders page (no undefined errors)
- [ ] Test Push Notifications
- [ ] Check browser console (F12) - no CORS errors

### Verify Environment
- [ ] Check Vercel logs: `[Next.js] API Rewrite URL: https://...`
- [ ] Test backend: `https://your-backend-url/analytics.php` (should return JSON)
- [ ] Token saved in localStorage after login

### Performance
- [ ] Run Lighthouse test (should be 90+)
- [ ] Test PWA install prompt
- [ ] Test offline mode

---

## 🐛 Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| "No token provided" | Missing env var | Set `NEXT_PUBLIC_API_URL` in Vercel |
| CORS error | Backend not allowing Vercel domain | Update PHP CORS headers |
| 404 on API calls | Wrong backend URL | Check `NEXT_PUBLIC_API_URL` value |
| Ngrok expired | Free tier timeout | Restart ngrok + update Vercel env |

---

## 📝 Environment Variable Reference

### Development (`.env.local`)
```env
NEXT_PUBLIC_API_URL=http://localhost/DailyCup/webapp/backend/api
```

### Production (Vercel Dashboard)
```env
NEXT_PUBLIC_API_URL=https://your-production-backend.com/api
```

### Code (No changes needed)
```tsx
// ✅ Works in both dev and prod
fetch('/api/analytics.php')
```

---

**Status:** ✅ Ready to Deploy  
**Updated:** February 6, 2026
