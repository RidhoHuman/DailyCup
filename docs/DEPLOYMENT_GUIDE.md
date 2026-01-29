# 🚀 Deployment Guide - DailyCup

Complete guide untuk deploy DailyCup ke production.

---

## 📋 Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Frontend Deployment (Vercel)](#frontend-deployment-vercel)
3. [Backend Deployment (PHP)](#backend-deployment-php)
4. [Database Setup](#database-setup)
5. [Environment Configuration](#environment-configuration)
6. [SSL Certificate](#ssl-certificate)
7. [Performance Optimization](#performance-optimization)
8. [Monitoring & Analytics](#monitoring--analytics)
9. [Post-Deployment Tasks](#post-deployment-tasks)

---

## ✅ Pre-Deployment Checklist

### Code Quality
- [ ] All tests passing (`npm run test:all`)
- [ ] No TypeScript errors (`npx tsc --noEmit`)
- [ ] No ESLint errors (`npm run lint`)
- [ ] Production build successful (`npm run build`)

### Security
- [ ] Environment variables configured
- [ ] API keys secured (not in git)
- [ ] CORS configured properly
- [ ] JWT secret changed from default
- [ ] SQL injection prevention checked
- [ ] XSS protection enabled

### Performance
- [ ] Images optimized
- [ ] Code splitting implemented
- [ ] Bundle size checked (`npm run build`)
- [ ] Lighthouse score > 90

### Features
- [ ] All critical features working
- [ ] Payment gateway tested
- [ ] Email notifications working
- [ ] Push notifications tested

---

## 🌐 Frontend Deployment (Vercel)

### Step 1: Prepare for Deployment

```bash
cd frontend

# Install dependencies
npm install

# Run production build locally
npm run build

# Test production build
npm run start
```

### Step 2: Create Vercel Account

1. Go to [vercel.com](https://vercel.com)
2. Sign up with GitHub
3. Install Vercel CLI (optional):
   ```bash
   npm install -g vercel
   ```

### Step 3: Deploy via Vercel Dashboard

#### Option A: GitHub Integration (Recommended)

1. **Push to GitHub:**
   ```bash
   git add .
   git commit -m "Ready for deployment"
   git push origin main
   ```

2. **Import Project in Vercel:**
   - Go to Vercel Dashboard
   - Click "Add New Project"
   - Import from GitHub
   - Select `DailyCup/webapp/frontend`

3. **Configure Project:**
   - **Framework Preset:** Next.js
   - **Root Directory:** `webapp/frontend`
   - **Build Command:** `npm run build`
   - **Output Directory:** `.next`

4. **Add Environment Variables:**
   ```env
   NEXT_PUBLIC_API_URL=https://api.dailycup.com
   NEXT_PUBLIC_VAPID_PUBLIC_KEY=your_key_here
   NEXT_PUBLIC_ENABLE_PWA=true
   ```

5. **Deploy:**
   - Click "Deploy"
   - Wait 2-5 minutes
   - Get deployment URL: `https://dailycup.vercel.app`

#### Option B: Vercel CLI

```bash
cd frontend

# Login to Vercel
vercel login

# Deploy to production
vercel --prod

# Follow prompts:
# - Set up project: Yes
# - Link to existing: No
# - Project name: dailycup
# - Directory: ./
```

### Step 4: Configure Custom Domain

1. **Add Domain in Vercel:**
   - Go to Project Settings → Domains
   - Add `dailycup.com` and `www.dailycup.com`

2. **Update DNS Records:**
   ```
   Type: A
   Name: @
   Value: 76.76.21.21 (Vercel IP)

   Type: CNAME
   Name: www
   Value: cname.vercel-dns.com
   ```

3. **SSL Certificate:**
   - Vercel automatically provisions SSL
   - Usually ready in 5-10 minutes

---

## 🖥️ Backend Deployment (PHP)

### Hosting Options

#### Option A: Shared Hosting (Niagahoster, Hostinger)
**Best for:** Small to medium traffic, budget-friendly

**Pros:**
- Affordable (Rp 20.000 - 50.000/month)
- Easy cPanel setup
- Automatic backups
- Free SSL

**Cons:**
- Limited control
- Shared resources
- PHP version limitations

#### Option B: VPS (DigitalOcean, Vultr, AWS Lightsail)
**Best for:** Full control, scalability

**Pros:**
- Full control
- Better performance
- Scalable
- Custom configurations

**Cons:**
- More expensive (starting $5/month)
- Requires server management
- Manual setup

#### Option C: Cloud Platform (AWS, Google Cloud)
**Best for:** Enterprise, high traffic

**Pros:**
- Auto-scaling
- Global CDN
- High availability
- Advanced features

**Cons:**
- Complex setup
- Higher cost
- Steep learning curve

---

### Deployment Steps (Shared Hosting)

#### 1. **Prepare Files**

```bash
# Create deployment package
cd webapp/backend

# Remove unnecessary files
rm -rf tests/
rm -rf .git/
rm composer.lock

# Create zip
zip -r dailycup-backend.zip .
```

#### 2. **Upload to Hosting**

Via cPanel:
1. Login to cPanel
2. File Manager → public_html
3. Upload `dailycup-backend.zip`
4. Extract zip

Via FTP:
```bash
# Using FileZilla or WinSCP
Host: ftp.yourdomain.com
Username: your_username
Password: your_password
Port: 21

# Upload to: /public_html/
```

#### 3. **Setup Database**

In cPanel → MySQL Databases:

1. **Create Database:**
   - Name: `dailycup_db`
   - Create

2. **Create User:**
   - Username: `dailycup_user`
   - Password: (generate strong password)
   - Create

3. **Grant Privileges:**
   - Add user to database
   - Select ALL PRIVILEGES

4. **Import Database:**
   ```bash
   # Via phpMyAdmin:
   # - Select dailycup_db
   # - Import tab
   # - Choose dailycup_db.sql
   # - Go
   ```

#### 4. **Configure Environment**

Create `.env` in backend root:

```env
# Database
DB_HOST=localhost
DB_NAME=dailycup_db
DB_USER=dailycup_user
DB_PASS=your_secure_password

# JWT
JWT_SECRET=your_very_long_random_secret_key_change_this

# CORS
ALLOWED_ORIGINS=https://dailycup.com,https://www.dailycup.com

# Email (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your_app_password

# Push Notifications
VAPID_PUBLIC_KEY=your_public_key
VAPID_PRIVATE_KEY=your_private_key
VAPID_SUBJECT=mailto:admin@dailycup.com

# Payment Gateway
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_IS_PRODUCTION=true

# App
APP_ENV=production
APP_DEBUG=false
```

#### 5. **Install Composer Dependencies**

Via SSH (if available):
```bash
cd public_html
composer install --no-dev --optimize-autoloader
```

Via cPanel Terminal:
```bash
php composer.phar install --no-dev
```

If no SSH access:
- Install dependencies locally
- Upload vendor/ folder via FTP

#### 6. **Set File Permissions**

```bash
# Via SSH
chmod 755 public_html/
chmod 644 public_html/.env
chmod 755 public_html/api/
chmod 755 public_html/backend/data/
chmod 755 public_html/backend/queue/
```

#### 7. **Test API Endpoints**

```bash
# Test in browser or Postman
https://api.dailycup.com/products.php
https://api.dailycup.com/categories.php
https://api.dailycup.com/me.php (with JWT)
```

---

### Deployment Steps (VPS - Ubuntu)

#### 1. **Server Setup**

```bash
# Connect to VPS
ssh root@your_server_ip

# Update system
apt update && apt upgrade -y

# Install LAMP stack
apt install apache2 mysql-server php8.2 php8.2-mysql php8.2-curl php8.2-mbstring php8.2-xml -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

#### 2. **Configure Apache**

```bash
# Create virtual host
nano /etc/apache2/sites-available/dailycup.conf
```

```apache
<VirtualHost *:80>
    ServerName api.dailycup.com
    DocumentRoot /var/www/dailycup/backend

    <Directory /var/www/dailycup/backend>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dailycup-error.log
    CustomLog ${APACHE_LOG_DIR}/dailycup-access.log combined
</VirtualHost>
```

```bash
# Enable site and mod_rewrite
a2ensite dailycup
a2enmod rewrite
systemctl restart apache2
```

#### 3. **Deploy Code**

```bash
# Clone from Git (recommended)
cd /var/www
git clone https://github.com/yourusername/dailycup.git
cd dailycup/backend

# Or upload via SCP
scp -r backend/ root@server_ip:/var/www/dailycup/

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chown -R www-data:www-data /var/www/dailycup
chmod -R 755 /var/www/dailycup
chmod 644 /var/www/dailycup/backend/.env
```

#### 4. **Setup MySQL**

```bash
# Secure MySQL
mysql_secure_installation

# Create database
mysql -u root -p

CREATE DATABASE dailycup_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dailycup_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON dailycup_db.* TO 'dailycup_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database
mysql -u dailycup_user -p dailycup_db < /var/www/dailycup/database/dailycup_db.sql
```

#### 5. **Install SSL Certificate (Let's Encrypt)**

```bash
# Install Certbot
apt install certbot python3-certbot-apache -y

# Get certificate
certbot --apache -d api.dailycup.com

# Auto-renewal (already setup by certbot)
# Test renewal:
certbot renew --dry-run
```

---

## 🔐 SSL Certificate Setup

### Vercel (Frontend)
- **Automatic:** Vercel provides free SSL automatically
- No manual setup needed
- Renews automatically

### Backend (Shared Hosting)
- **cPanel:** SSL/TLS → Let's Encrypt → Issue
- Usually free with hosting

### Backend (VPS)
```bash
# Let's Encrypt (Free)
certbot --apache -d api.dailycup.com
```

---

## ⚡ Performance Optimization

### Frontend Optimization

#### 1. **Image Optimization**
```bash
# Already using Next.js Image component
# Further optimize:
npm install sharp  # For better image processing
```

#### 2. **Code Splitting**
```typescript
// Use dynamic imports for large components
const HeavyComponent = dynamic(() => import('./HeavyComponent'), {
  loading: () => <Spinner />,
  ssr: false
})
```

#### 3. **Bundle Analysis**
```bash
npm install --save-dev @next/bundle-analyzer

# In next.config.js:
const withBundleAnalyzer = require('@next/bundle-analyzer')({
  enabled: process.env.ANALYZE === 'true',
})

module.exports = withBundleAnalyzer(nextConfig)

# Run:
ANALYZE=true npm run build
```

#### 4. **Caching Strategy**
```javascript
// next.config.js
module.exports = {
  async headers() {
    return [
      {
        source: '/:all*(svg|jpg|png|webp)',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
    ]
  },
}
```

### Backend Optimization

#### 1. **Enable OPcache**
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

#### 2. **Database Indexing**
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_product_category ON products(category_id);
CREATE INDEX idx_order_user ON orders(user_id);
CREATE INDEX idx_order_status ON orders(status);
```

#### 3. **API Response Caching**
```php
// Simple cache implementation
$cache_file = __DIR__ . '/cache/products.json';
$cache_time = 300; // 5 minutes

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
    echo file_get_contents($cache_file);
    exit;
}

// Fetch data
$data = fetchProductsFromDB();

// Save to cache
file_put_contents($cache_file, json_encode($data));
echo json_encode($data);
```

---

## 📊 Monitoring & Analytics

### 1. **Google Analytics**

```typescript
// app/layout.tsx
import Script from 'next/script'

export default function RootLayout({ children }) {
  return (
    <html>
      <head>
        <Script
          src={`https://www.googletagmanager.com/gtag/js?id=${process.env.NEXT_PUBLIC_GA_ID}`}
          strategy="afterInteractive"
        />
        <Script id="google-analytics" strategy="afterInteractive">
          {`
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '${process.env.NEXT_PUBLIC_GA_ID}');
          `}
        </Script>
      </head>
      <body>{children}</body>
    </html>
  )
}
```

### 2. **Vercel Analytics**

```bash
npm install @vercel/analytics
```

```typescript
// app/layout.tsx
import { Analytics } from '@vercel/analytics/react'

export default function RootLayout({ children }) {
  return (
    <html>
      <body>
        {children}
        <Analytics />
      </body>
    </html>
  )
}
```

### 3. **Error Tracking (Sentry - Optional)**

```bash
npm install --save @sentry/nextjs
```

```typescript
// sentry.client.config.ts
import * as Sentry from '@sentry/nextjs'

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  environment: process.env.NODE_ENV,
  tracesSampleRate: 0.1,
})
```

### 4. **Uptime Monitoring**

Free options:
- **UptimeRobot:** https://uptimerobot.com (free up to 50 monitors)
- **Pingdom:** https://pingdom.com (free trial)
- **StatusCake:** https://statuscake.com

Setup monitors for:
- Frontend: https://dailycup.com
- Backend API: https://api.dailycup.com/health
- Database: Connection check endpoint

---

## ✅ Post-Deployment Tasks

### 1. **Test All Features**
- [ ] User registration & login
- [ ] Browse products
- [ ] Add to cart
- [ ] Checkout flow
- [ ] Payment processing
- [ ] Order tracking
- [ ] Admin dashboard
- [ ] Push notifications
- [ ] PWA install

### 2. **Performance Testing**
```bash
# Lighthouse CI
npm install -g @lhci/cli
lhci autorun --url=https://dailycup.com
```

### 3. **Security Scan**
- [ ] SSL Labs test: https://www.ssllabs.com/ssltest/
- [ ] Security Headers: https://securityheaders.com
- [ ] OWASP ZAP scan (optional)

### 4. **SEO Verification**
- [ ] Submit sitemap to Google Search Console
- [ ] Verify robots.txt
- [ ] Test structured data
- [ ] Check meta tags

### 5. **Setup Backups**

**Database Backup Script:**
```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u dailycup_user -p dailycup_db > /backups/dailycup_$DATE.sql
# Keep only last 7 days
find /backups -name "dailycup_*.sql" -mtime +7 -delete
```

**Cron job:**
```bash
# Daily backup at 2 AM
0 2 * * * /path/to/backup.sh
```

### 6. **Monitor First Week**
- Check error logs daily
- Monitor server resources
- Review analytics
- Test from different locations
- Gather user feedback

---

## 🚨 Troubleshooting

### Common Issues

**Issue: 500 Internal Server Error**
```bash
# Check Apache error log
tail -f /var/log/apache2/error.log

# Check PHP errors
tail -f /var/log/php8.2-fpm.log
```

**Issue: Database Connection Failed**
```php
// Test connection
$conn = new PDO("mysql:host=localhost;dbname=dailycup_db", "user", "pass");
var_dump($conn);
```

**Issue: CORS Errors**
```php
// backend/api/cors.php
header('Access-Control-Allow-Origin: https://dailycup.com');
header('Access-Control-Allow-Credentials: true');
```

**Issue: SSL Mixed Content**
```bash
# Ensure all resources use HTTPS
# Check in browser console
```

---

## 📚 Resources

- [Vercel Documentation](https://vercel.com/docs)
- [Next.js Deployment](https://nextjs.org/docs/deployment)
- [Let's Encrypt](https://letsencrypt.org/)
- [Google PageSpeed Insights](https://pagespeed.web.dev/)

---

**Deployment Checklist Complete!** Ready to go live! 🚀
