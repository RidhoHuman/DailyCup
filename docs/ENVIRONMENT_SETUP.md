# 🔐 Environment Variables Setup Guide

This document explains all environment variables needed for DailyCup deployment.

---

## 📋 Frontend Environment Variables

Create `.env.production` in `frontend/` directory:

```env
# ============================================
# API Configuration
# ============================================
NEXT_PUBLIC_API_URL=https://api.dailycup.com
NEXT_PUBLIC_APP_URL=https://dailycup.com

# ============================================
# Push Notifications (VAPID Keys)
# ============================================
# Generate VAPID keys using:
# npx web-push generate-vapid-keys
NEXT_PUBLIC_VAPID_PUBLIC_KEY=BDw8o...your_public_key_here

# ============================================
# Analytics & Monitoring
# ============================================
# Google Analytics
NEXT_PUBLIC_GA_ID=G-XXXXXXXXXX

# Google Tag Manager (Optional)
NEXT_PUBLIC_GTM_ID=GTM-XXXXXXX

# ============================================
# Feature Flags
# ============================================
NEXT_PUBLIC_ENABLE_PWA=true
NEXT_PUBLIC_ENABLE_PUSH_NOTIFICATIONS=true
NEXT_PUBLIC_ENABLE_ANALYTICS=true

# ============================================
# Error Tracking (Optional)
# ============================================
# Sentry DSN
NEXT_PUBLIC_SENTRY_DSN=https://xxx@sentry.io/xxx
NEXT_PUBLIC_SENTRY_ENVIRONMENT=production

# ============================================
# Build Configuration
# ============================================
NEXT_TELEMETRY_DISABLED=1
NODE_ENV=production
```

---

## 🖥️ Backend Environment Variables

Create `.env` in `backend/` directory:

```env
# ============================================
# Application
# ============================================
APP_ENV=production
APP_DEBUG=false
APP_NAME="DailyCup"
APP_URL=https://api.dailycup.com

# ============================================
# Database Configuration
# ============================================
DB_HOST=localhost
DB_NAME=dailycup_db
DB_USER=dailycup_user
DB_PASS=your_very_secure_database_password_here

# Database Charset
DB_CHARSET=utf8mb4

# ============================================
# JWT Authentication
# ============================================
# Generate with: openssl rand -base64 64
JWT_SECRET=your_very_long_random_secret_key_minimum_32_characters_change_this_immediately
JWT_EXPIRATION=3600
JWT_REFRESH_EXPIRATION=604800

# ============================================
# CORS Configuration
# ============================================
ALLOWED_ORIGINS=https://dailycup.com,https://www.dailycup.com
ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
ALLOWED_HEADERS=Content-Type,Authorization,X-CSRF-Token

# ============================================
# Email Configuration (SMTP)
# ============================================
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your_gmail_app_password_here
SMTP_FROM_EMAIL=noreply@dailycup.com
SMTP_FROM_NAME="DailyCup"

# Email Queue
EMAIL_QUEUE_ENABLED=true
EMAIL_QUEUE_BATCH_SIZE=10

# ============================================
# Push Notifications (Web Push)
# ============================================
# Generate with: npx web-push generate-vapid-keys
VAPID_PUBLIC_KEY=BDw8oXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
VAPID_PRIVATE_KEY=your_private_key_here_keep_this_secret
VAPID_SUBJECT=mailto:admin@dailycup.com

# ============================================
# Payment Gateway - Midtrans
# ============================================
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=true
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true

# ============================================
# Payment Gateway - Xendit (Optional)
# ============================================
XENDIT_API_KEY=xnd_production_xxxxxxxxxxxxx
XENDIT_WEBHOOK_TOKEN=your_xendit_webhook_verification_token

# ============================================
# Security
# ============================================
# CSRF Protection
CSRF_TOKEN_EXPIRY=3600

# Rate Limiting
RATE_LIMIT_ENABLED=true
RATE_LIMIT_MAX_REQUESTS=100
RATE_LIMIT_WINDOW=60

# Session
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# ============================================
# File Upload
# ============================================
MAX_FILE_SIZE=5242880
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,webp
UPLOAD_PATH=/var/www/dailycup/uploads

# ============================================
# Cache
# ============================================
CACHE_DRIVER=file
CACHE_ENABLED=true
CACHE_TTL=300

# ============================================
# Logging
# ============================================
LOG_LEVEL=error
LOG_PATH=/var/www/dailycup/logs
LOG_MAX_FILES=30

# ============================================
# Third-Party Services
# ============================================
# Google Maps API (for delivery tracking)
GOOGLE_MAPS_API_KEY=your_google_maps_api_key

# Firebase (for additional push notifications)
FIREBASE_SERVER_KEY=your_firebase_server_key

# ============================================
# Business Configuration
# ============================================
# Shipping
FREE_SHIPPING_MINIMUM=50000
SHIPPING_COST=10000

# Loyalty Points
LOYALTY_POINTS_PER_RUPIAH=1
LOYALTY_POINTS_VALUE=100

# Order
ORDER_AUTO_CANCEL_MINUTES=30
ORDER_PREPARATION_TIME=15
```

---

## 🔑 How to Generate Secure Keys

### JWT Secret
```bash
# Option 1: Using OpenSSL
openssl rand -base64 64

# Option 2: Using Node.js
node -e "console.log(require('crypto').randomBytes(64).toString('base64'))"

# Option 3: Using PHP
php -r "echo base64_encode(random_bytes(64));"
```

### VAPID Keys (Push Notifications)
```bash
# Install web-push globally
npm install -g web-push

# Generate VAPID keys
npx web-push generate-vapid-keys

# Output:
# Public Key: BDw8o...
# Private Key: xxx...
```

### CSRF Token
```bash
# Generate random token
openssl rand -hex 32
```

---

## 📦 Vercel Environment Variables Setup

### Via Vercel Dashboard:

1. Go to your project → **Settings** → **Environment Variables**

2. Add variables **one by one**:

   **Production Variables:**
   ```
   Name: NEXT_PUBLIC_API_URL
   Value: https://api.dailycup.com
   Environment: Production ✓
   
   Name: NEXT_PUBLIC_VAPID_PUBLIC_KEY
   Value: BDw8o...
   Environment: Production ✓
   
   Name: NEXT_PUBLIC_ENABLE_PWA
   Value: true
   Environment: Production ✓
   ```

3. **Redeploy** after adding variables

### Via Vercel CLI:

```bash
# Add environment variable
vercel env add NEXT_PUBLIC_API_URL

# You'll be prompted:
# What's the value of NEXT_PUBLIC_API_URL? https://api.dailycup.com
# Add to which environments? Production

# Pull environment variables
vercel env pull .env.production
```

---

## 🔒 Security Best Practices

### ✅ DO:
- Use strong, random passwords (minimum 32 characters)
- Different passwords for each service
- Store secrets in `.env` files (never in git)
- Use environment-specific values (dev vs production)
- Rotate keys regularly (every 90 days)
- Use password managers for team access

### ❌ DON'T:
- Commit `.env` files to git
- Use default/example passwords
- Share secrets via email/chat
- Use same password across services
- Hard-code secrets in code

---

## 📝 Environment Variables Checklist

### Frontend (Vercel):
- [ ] `NEXT_PUBLIC_API_URL` - Backend API URL
- [ ] `NEXT_PUBLIC_APP_URL` - Frontend URL
- [ ] `NEXT_PUBLIC_VAPID_PUBLIC_KEY` - Push notification key
- [ ] `NEXT_PUBLIC_GA_ID` - Google Analytics (optional)
- [ ] `NEXT_PUBLIC_ENABLE_PWA` - Enable PWA features

### Backend:
- [ ] `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` - Database credentials
- [ ] `JWT_SECRET` - JWT signing key (64+ characters)
- [ ] `ALLOWED_ORIGINS` - CORS allowed domains
- [ ] `SMTP_*` - Email configuration
- [ ] `VAPID_*` - Push notification keys (matching frontend)
- [ ] `MIDTRANS_*` - Payment gateway credentials
- [ ] `APP_ENV=production` - Production environment flag

---

## 🧪 Testing Environment Variables

### Test Backend Connection:
```bash
# Test database connection
php -r "
\$pdo = new PDO('mysql:host=localhost;dbname=dailycup_db', 'user', 'pass');
echo 'Connected successfully';
"
```

### Test SMTP:
```bash
# Use test_smtp_connection.php
curl https://api.dailycup.com/test_smtp_connection.php
```

### Test VAPID Keys Match:
```javascript
// Frontend and backend should have matching VAPID public key
console.log(process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY)
```

---

## 🚨 Troubleshooting

### "Database connection failed"
- Check `DB_HOST`, `DB_USER`, `DB_PASS`
- Verify database user has proper privileges
- Test connection with MySQL client

### "CORS error"
- Check `ALLOWED_ORIGINS` includes your frontend URL
- Ensure no trailing slashes
- Include both www and non-www versions

### "JWT invalid"
- Ensure `JWT_SECRET` is same across all backend instances
- Check JWT is not expired
- Verify secret is at least 32 characters

### "Push notifications not working"
- Verify VAPID keys match between frontend and backend
- Check VAPID_SUBJECT is valid email format
- Ensure HTTPS is enabled (required for push)

---

## 📚 Resources

- [Vercel Environment Variables](https://vercel.com/docs/concepts/projects/environment-variables)
- [Web Push Protocol](https://web.dev/push-notifications-overview/)
- [12 Factor App - Config](https://12factor.net/config)

---

**Remember:** Never commit `.env` files to version control! Always use `.env.example` for documentation.
