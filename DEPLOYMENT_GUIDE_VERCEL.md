# 🚀 Deployment Guide: Next.js (Vercel) + PHP Backend (Ngrok/Hosting)

**Created:** February 6, 2026  
**Stack:** Next.js 16.1.6 + PHP 8+ + MySQL

---

## ⚠️ **Important: Vercel Cannot Host PHP**

Vercel is a **serverless Node.js platform** and **does NOT support PHP**. 

Your architecture:
- ✅ **Frontend (Next.js)** → Deploy to Vercel
- ❌ **Backend (PHP)** → Cannot deploy to Vercel
- ✅ **Backend (PHP)** → Options:
  1. **Keep using Ngrok** (temporary tunnel from local Laragon)
  2. **Deploy PHP to separate hosting** (cPanel, DigitalOcean, AWS, Heroku PHP, etc.)

---

## 📋 **Deployment Strategy**

### **Option 1: Vercel + Ngrok (Quick/Testing)**

**Pros:**
- ✅ Quick setup
- ✅ Keep using local Laragon database
- ✅ Free tier available

**Cons:**
- ❌ Ngrok tunnel resets daily (free tier)
- ❌ Not suitable for production
- ❌ Requires local Laragon running 24/7

**Steps:**
1. Deploy Next.js to Vercel
2. Keep Laragon + Ngrok running on your PC
3. Set environment variable in Vercel Dashboard

---

### **Option 2: Vercel + PHP Hosting (Production)**

**Pros:**
- ✅ Production-ready
- ✅ 24/7 uptime
- ✅ Scalable

**Cons:**
- ❌ Requires separate PHP hosting ($)
- ❌ Need to migrate database to cloud

**Steps:**
1. Deploy PHP backend to hosting (cPanel, DigitalOcean, etc.)
2. Migrate MySQL database to cloud
3. Deploy Next.js to Vercel
4. Set environment variable in Vercel Dashboard

---

## 🔧 **Deployment Steps**

### **Step 1: Prepare Repository**

```bash
# From frontend directory
cd c:\laragon\www\DailyCup\webapp\frontend

# Check .gitignore (should ignore .env.local)
# Already configured: .env* and .env*.local are ignored

# Copy environment example
cp .env.example .env.local

# Commit changes (will NOT include .env.local)
git add .
git commit -m "feat: ready for production deployment"
git push origin main
```

---

### **Step 2: Setup Backend (Choose One)**

#### **Option A: Continue Using Ngrok**

```bash
# On your local PC (keep running)
cd c:\laragon\www\DailyCup
ngrok http 80 --domain=your-subdomain.ngrok-free.dev
```

Copy the ngrok URL: `https://your-subdomain.ngrok-free.dev`

---

#### **Option B: Deploy PHP to Hosting**

**Example: cPanel Hosting**
1. Upload `c:\laragon\www\DailyCup` to `public_html/DailyCup`
2. Import MySQL database via phpMyAdmin
3. Update `config/database.php` with production credentials
4. Get PHP backend URL: `https://your-domain.com/DailyCup/webapp/backend/api`

**Example: DigitalOcean Droplet**
1. Create Ubuntu droplet with LAMP stack
2. Upload code via Git or FTP
3. Configure Apache virtual host
4. Get PHP backend URL: `https://api.yourdomain.com`

---

### **Step 3: Deploy to Vercel**

#### **3.1 Connect GitHub Repo**

1. Go to [Vercel Dashboard](https://vercel.com)
2. Click **"Add New Project"**
3. Import your GitHub repository
4. Select **`webapp/frontend`** as root directory
5. Framework Preset: **Next.js** (auto-detected)

---

#### **3.2 Configure Environment Variables**

In Vercel Dashboard → **Settings** → **Environment Variables**, add:

| **Variable Name** | **Value** | **Environment** |
|-------------------|-----------|-----------------|
| `NEXT_PUBLIC_API_URL` | `https://your-ngrok-url.ngrok-free.dev/DailyCup/webapp/backend/api` | Production |
| `NEXT_PUBLIC_VAPID_PUBLIC_KEY` | `BJZ2QjWbziK5U68pPrWDIcSB8Sm9ONFwVCi_U7LTJkyvh-Lp5nBMw1Pgq3SIaA0txvKVOHX0YdSQ5Qi8xn7e4wI` | Production |
| `NEXT_PUBLIC_DEBUG` | `false` | Production |
| `NEXT_TELEMETRY_DISABLED` | `1` | Production |

**Important:** Replace `your-ngrok-url.ngrok-free.dev` with:
- Your actual ngrok URL (Option A)
- OR your PHP hosting URL (Option B)

---

#### **3.3 Build Settings**

Vercel will auto-detect:
```
Framework Preset: Next.js
Build Command: npm run build
Output Directory: .next
Install Command: npm install
Root Directory: webapp/frontend
```

---

#### **3.4 Deploy**

Click **"Deploy"** → Wait 2-3 minutes → Done!

Your Next.js app will be live at: `https://your-project.vercel.app`

---

## ✅ **How Environment Variables Work**

### **Development (Local):**
```
Next.js: http://localhost:3000
PHP: http://localhost/DailyCup/webapp/backend/api
Environment: .env.local (NOT committed to Git)
```

### **Production (Vercel):**
```
Next.js: https://your-project.vercel.app
PHP: https://your-ngrok-or-hosting-url.com/DailyCup/webapp/backend/api
Environment: Set in Vercel Dashboard
```

### **Code Changes (Already Fixed):**
```tsx
// ✅ GOOD - Uses /api prefix (works both dev & prod)
const response = await fetch('/api/analytics.php', { ... });

// ❌ BAD - Hardcoded URL (only works in dev)
const response = await fetch('http://localhost/DailyCup/...', { ... });
```

**How it works:**
1. Next.js `rewrites()` in `next.config.ts` handles `/api/*` routing
2. Development: `/api/analytics.php` → `http://localhost/DailyCup/webapp/backend/api/analytics.php`
3. Production: `/api/analytics.php` → `https://your-backend-url.com/analytics.php`

---

## 🔍 **Testing Production Build Locally**

Before deploying to Vercel, test production build:

```bash
# Build for production
npm run build

# Run production server locally
npm start

# Test at http://localhost:3000
# Should use NEXT_PUBLIC_API_URL from .env.local
```

---

## 🐛 **Troubleshooting**

### **Issue: "No token provided" in production**
- **Cause:** Missing `NEXT_PUBLIC_API_URL` in Vercel
- **Fix:** Add environment variable in Vercel Dashboard
- **Verify:** Check Vercel logs for API rewrite URL

### **Issue: CORS errors in production**
- **Cause:** PHP backend not allowing Vercel domain
- **Fix:** Update `analytics.php` CORS headers:
  ```php
  header('Access-Control-Allow-Origin: https://your-project.vercel.app');
  ```

### **Issue: Analytics page blank/loading forever**
- **Cause:** Backend not accessible or wrong URL
- **Fix:** Check Vercel environment variables
- **Test:** Open `https://your-backend-url/analytics.php` in browser

### **Issue: Ngrok tunnel expired**
- **Cause:** Free ngrok tunnels reset after 8 hours
- **Fix:** Restart ngrok and update Vercel environment variable
- **Solution:** Upgrade to ngrok paid plan OR use PHP hosting

---

## 📚 **Additional Resources**

- [Vercel Environment Variables](https://vercel.com/docs/projects/environment-variables)
- [Next.js Deployment](https://nextjs.org/docs/deployment)
- [Ngrok Static Domains](https://ngrok.com/docs/guides/how-to-set-up-a-custom-domain)
- [PHP Hosting Options](https://www.hostinger.com/tutorials/best-php-hosting)

---

## 🎯 **Recommended Production Setup**

**For Serious Production:**
1. ✅ Deploy Next.js to **Vercel** (free tier)
2. ✅ Deploy PHP to **VPS/Cloud** ($5-10/month):
   - DigitalOcean LAMP Droplet ($6/month)
   - AWS Lightsail ($3.50/month)
   - Heroku PHP ($7/month)
3. ✅ MySQL on Cloud (included with hosting)
4. ✅ Custom domain with SSL (free via Let's Encrypt)

**Cost:** ~$5-10/month total

**For Testing/Demo Only:**
1. ⚠️ Deploy Next.js to **Vercel** (free)
2. ⚠️ Keep using **Ngrok** + local Laragon (free but temporary)
3. ⚠️ Reminder: Restart ngrok daily and update Vercel env vars

---

## 📝 **Checklist Before Deploy**

- [ ] `.env.local` is in `.gitignore` ✅ (already configured)
- [ ] All hardcoded URLs changed to `/api/*` ✅ (already fixed)
- [ ] Backend accessible from internet (ngrok OR hosting)
- [ ] Environment variables set in Vercel Dashboard
- [ ] CORS headers allow Vercel domain
- [ ] Database accessible from backend
- [ ] VAPID keys configured and matching
- [ ] Test production build locally (`npm run build && npm start`)

---

**Last Updated:** February 6, 2026  
**Status:** ✅ Ready for Deployment
