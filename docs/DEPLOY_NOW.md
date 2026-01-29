# 🚀 Step-by-Step Deployment Guide - Mulai Deploy Sekarang!

**Target:** Deploy DailyCup ke production dalam 2-3 jam

**Tanggal:** 29 Januari 2026

---

## ✅ Pre-Deployment Checklist

Pastikan ini sudah oke sebelum deploy:

- [x] Production build configuration ready
- [x] Environment variables documented
- [x] Deployment guides created
- [ ] Git repository initialized
- [ ] GitHub account ready
- [ ] Vercel account ready

---

## 📦 Step 1: Test Production Build (5 menit)

### Test build di lokal dulu:

```powershell
cd c:\laragon\www\DailyCup\webapp\frontend

# Install dependencies (jika belum)
npm install

# Build production
npm run build

# Test production build
npm run start
```

**Expected output:**
- Build success ✅
- No errors
- Bundle size < 500KB
- Server running on http://localhost:3000

**Buka browser:** http://localhost:3000
- Test semua halaman
- Test cart functionality
- Test checkout flow

**Jika ada error:** Fix dulu sebelum lanjut deploy

---

## 🌐 Step 2: Setup GitHub Repository (10 menit)

### A. Initialize Git (jika belum)

```powershell
cd c:\laragon\www\DailyCup\webapp

# Check git status
git status

# If not initialized:
git init

# Add all files
git add .

# Commit
git commit -m "Ready for deployment - Phase 14 complete"
```

### B. Create GitHub Repository

1. **Buka:** https://github.com/new
2. **Repository name:** `DailyCup`
3. **Description:** "Premium Coffee Delivery Web App - Next.js + PHP Backend"
4. **Visibility:** Private (recommended) atau Public
5. **DON'T** initialize with README (sudah ada di local)
6. **Click:** Create repository

### C. Push ke GitHub

```powershell
# Add remote
git remote add origin https://github.com/YOUR_USERNAME/DailyCup.git

# Check branch
git branch -M main

# Push to GitHub
git push -u origin main
```

**Username/Password diminta?**
- Use Personal Access Token (PAT) instead of password
- Create PAT: GitHub → Settings → Developer settings → Personal access tokens → Generate new token
- Select scope: `repo` (full control)

---

## ⚡ Step 3: Deploy Frontend ke Vercel (15 menit)

### A. Create Vercel Account

1. **Buka:** https://vercel.com
2. **Sign up with GitHub** (recommended)
3. Authorize Vercel to access GitHub

### B. Import Project

1. **Vercel Dashboard** → **Add New** → **Project**
2. **Import Git Repository** → Select `DailyCup`
3. **Configure Project:**
   - **Framework Preset:** Next.js
   - **Root Directory:** `webapp/frontend` ⚠️ PENTING!
   - **Build Command:** `npm run build` (auto-detected)
   - **Output Directory:** `.next` (auto-detected)
   - **Install Command:** `npm install` (auto-detected)

### C. Add Environment Variables

**SEBELUM DEPLOY!** Klik **Environment Variables**:

```env
# Required Variables:
NEXT_PUBLIC_API_URL = http://localhost/DailyCup/webapp/backend/api
NEXT_PUBLIC_APP_URL = https://your-project.vercel.app
NEXT_PUBLIC_ENABLE_PWA = true
NEXT_PUBLIC_ENABLE_PUSH_NOTIFICATIONS = false
NEXT_TELEMETRY_DISABLED = 1

# Optional (nanti bisa ditambah):
# NEXT_PUBLIC_GA_ID = G-XXXXXXXXXX
# NEXT_PUBLIC_VAPID_PUBLIC_KEY = your_key
```

**Note:** 
- `NEXT_PUBLIC_API_URL` pakai backend lokal dulu (http://localhost/...)
- Nanti update ke production backend setelah deploy backend
- Environment: **Production, Preview, Development** (centang semua)

### D. Deploy!

1. **Click:** Deploy
2. **Wait:** 2-5 menit
3. **Status:** Building... → Deploying... → Ready ✅

**Deployment URL:** `https://daily-cup-xxx.vercel.app`

---

## 🎉 Step 4: Verify Deployment (10 menit)

### Test Website:

**Open:** https://your-project.vercel.app

**Test checklist:**
- [ ] Homepage loads correctly
- [ ] Images displayed (may need updating)
- [ ] Navigation works
- [ ] Menu page loads
- [ ] Product cards displayed
- [ ] Cart functionality works
- [ ] Login/Register pages accessible
- [ ] PWA manifest accessible (/manifest.json)
- [ ] Service worker registered
- [ ] No console errors

**Known Issues (Expected):**
- ⚠️ API calls fail (backend belum deploy)
- ⚠️ Products not loading (backend belum deploy)
- ⚠️ Authentication not working (backend belum deploy)

**This is NORMAL!** Backend deployment next step.

---

## 🔧 Step 5: Update Environment Variables (5 menit)

### Generate VAPID Keys (untuk Push Notifications):

```powershell
cd c:\laragon\www\DailyCup\webapp\frontend
npx web-push generate-vapid-keys
```

**Output:**
```
Public Key: BDw8o...
Private Key: xxx...
```

### Update di Vercel:

1. **Vercel Dashboard** → Your Project → **Settings** → **Environment Variables**
2. **Add:**
   ```
   NEXT_PUBLIC_VAPID_PUBLIC_KEY = BDw8o... (paste public key)
   ```
3. **Save**
4. **Redeploy:** Settings → Deployments → Latest → ⋯ → Redeploy

---

## 🌐 Step 6: Configure Custom Domain (Optional - 30 menit)

### Jika punya domain (contoh: dailycup.com):

1. **Vercel Dashboard** → Your Project → **Settings** → **Domains**
2. **Add:** `dailycup.com` dan `www.dailycup.com`
3. **Update DNS di domain provider:**

```
Type: A
Name: @
Value: 76.76.21.21 (Vercel IP)

Type: CNAME
Name: www
Value: cname.vercel-dns.com
```

4. **Wait:** 5-10 minutes untuk DNS propagation
5. **SSL:** Automatic (Vercel provides free SSL)

### Jika belum punya domain:

- Pakai subdomain Vercel dulu: `your-project.vercel.app`
- Beli domain nanti: Namecheap ($8/year), Niagahoster (Rp 20k/year)

---

## 📱 Step 7: Test PWA Installation (5 menit)

### Desktop (Chrome):

1. Open: https://your-project.vercel.app
2. URL bar → Install icon ⊕
3. Click **Install**
4. App opens in standalone window ✅

### Mobile:

1. Open in Chrome/Safari
2. Menu → **Add to Home Screen**
3. Icon appears on home screen ✅
4. Opens like native app ✅

---

## 🎯 Frontend Deployment Complete! ✅

**Status:**
- ✅ Frontend deployed ke Vercel
- ✅ SSL certificate active
- ✅ PWA installable
- ✅ Environment variables configured
- ⏳ Backend deployment (next step)

---

## 🔜 Next: Backend Deployment

**Ada 2 pilihan:**

### **Option 1: Deploy Backend ke Shared Hosting** (Recommended untuk mulai)
- **Pros:** Mudah, murah (Rp 20k-50k/bulan), cPanel user-friendly
- **Cons:** Limited resources, shared IP
- **Recommended:** Niagahoster, Hostinger, Rumahweb
- **Time:** 30-60 menit

**Follow guide:** [DATABASE_DEPLOYMENT.md](./DATABASE_DEPLOYMENT.md) → Shared Hosting section

### **Option 2: Deploy Backend ke VPS**
- **Pros:** Full control, dedicated resources, scalable
- **Cons:** Lebih mahal ($5+/month), perlu server management
- **Recommended:** DigitalOcean, Vultr, AWS Lightsail
- **Time:** 1-2 jam (setup LAMP stack)

**Follow guide:** [DATABASE_DEPLOYMENT.md](./DATABASE_DEPLOYMENT.md) → VPS section

---

## 🚨 Troubleshooting

### Build Failed on Vercel?

**Error: Module not found**
```bash
# Fix: Clear cache and rebuild
Vercel Dashboard → Deployments → ⋯ → Redeploy → Clear cache
```

**Error: TypeScript errors**
```bash
# Local:
cd frontend
npx tsc --noEmit
# Fix all errors, commit, push
```

### Deployment Success but White Screen?

1. Check browser console for errors
2. Check Vercel logs: Deployments → Your deployment → Logs
3. Verify `webapp/frontend` as root directory
4. Check environment variables

### Images Not Loading?

**Issue:** Next.js Image optimization
**Fix:** Images will work after first visit (edge caching)

### API Calls Failing?

**Expected!** Backend belum deploy. Lanjut ke backend deployment.

---

## 📊 Deployment Metrics

**Target Performance (Vercel):**
- ✅ Build time: < 2 minutes
- ✅ Deploy time: < 1 minute
- ✅ Page load: < 2 seconds
- ✅ Lighthouse score: > 90

**Check:** Vercel Dashboard → Analytics (setelah enable)

---

## ✅ Deployment Checklist

Frontend:
- [ ] Production build tested locally
- [ ] Code pushed to GitHub
- [ ] Vercel project created
- [ ] Environment variables added
- [ ] Deployment successful
- [ ] Website accessible
- [ ] PWA installable
- [ ] No critical errors

Backend (Next Step):
- [ ] Hosting/VPS selected
- [ ] Database created
- [ ] Backend files uploaded
- [ ] Environment configured
- [ ] API endpoints working

---

## 🎉 Congratulations!

**Frontend is LIVE!** 🚀

**Your app:** https://your-project.vercel.app

**Next step:** Deploy backend atau test frontend dulu!

---

**Ready untuk backend deployment?** Reply dengan pilihan hosting:
- A) Shared Hosting (Niagahoster/Hostinger) - Mudah & murah
- B) VPS (DigitalOcean/Vultr) - Full control
- C) Test frontend dulu, backend nanti
