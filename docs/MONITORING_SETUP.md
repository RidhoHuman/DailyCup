# 📊 Monitoring & Analytics Setup Guide

Complete guide untuk setup monitoring, analytics, dan error tracking untuk DailyCup.

---

## 📋 Table of Contents

1. [Google Analytics Setup](#google-analytics-setup)
2. [Vercel Analytics](#vercel-analytics)
3. [Error Tracking (Sentry)](#error-tracking-sentry)
4. [Uptime Monitoring](#uptime-monitoring)
5. [Performance Monitoring](#performance-monitoring)
6. [Custom Event Tracking](#custom-event-tracking)

---

## 📈 Google Analytics Setup

### Step 1: Create Google Analytics Account

1. Go to [analytics.google.com](https://analytics.google.com)
2. Sign in with Google account
3. Click **Admin** → **Create Property**
4. Property details:
   - **Property name:** DailyCup
   - **Reporting time zone:** (GMT+07:00) Jakarta
   - **Currency:** Indonesian Rupiah (IDR)
5. Click **Next** → **Create**

### Step 2: Get Measurement ID

1. After creating property, go to **Data Streams**
2. Click **Add stream** → **Web**
3. Website details:
   - **Website URL:** https://dailycup.com
   - **Stream name:** DailyCup Website
4. Click **Create stream**
5. Copy **Measurement ID** (format: `G-XXXXXXXXXX`)

### Step 3: Add to Environment Variables

```env
# .env.production
NEXT_PUBLIC_GA_ID=G-XXXXXXXXXX
```

### Step 4: Verify Installation

1. Deploy to Vercel with new env variable
2. Visit your website
3. Open **Google Analytics** → **Realtime**
4. You should see yourself as an active user

### Step 5: Enable E-commerce Tracking

1. **Google Analytics** → **Admin** → **E-commerce Settings**
2. Enable **E-commerce** and **Enhanced E-commerce Reporting**
3. Click **Save**

---

## 📊 Vercel Analytics

### Step 1: Install Package

```bash
cd frontend
npm install @vercel/analytics @vercel/speed-insights
```

### Step 2: Add to Layout

```typescript
// app/layout.tsx
import { Analytics } from '@vercel/analytics/react'
import { SpeedInsights } from '@vercel/speed-insights/next'

export default function RootLayout({ children }) {
  return (
    <html>
      <body>
        {children}
        <Analytics />
        <SpeedInsights />
      </body>
    </html>
  )
}
```

### Step 3: Enable in Vercel Dashboard

1. Go to your project on **Vercel**
2. Click **Analytics** tab
3. Click **Enable**
4. Deploy your project

### Features:
- ✅ Page views tracking
- ✅ Unique visitors
- ✅ Top pages
- ✅ Referrers
- ✅ Devices & browsers
- ✅ Core Web Vitals (LCP, FID, CLS)

---

## 🐛 Error Tracking (Sentry)

### Step 1: Create Sentry Account

1. Go to [sentry.io](https://sentry.io)
2. Sign up for free account
3. Create new project:
   - **Platform:** Next.js
   - **Project name:** DailyCup

### Step 2: Install Sentry

```bash
npm install @sentry/nextjs
npx @sentry/wizard@latest -i nextjs
```

The wizard will:
- Create `sentry.client.config.ts`
- Create `sentry.server.config.ts`
- Create `sentry.edge.config.ts`
- Update `next.config.js`

### Step 3: Configure Sentry

```typescript
// sentry.client.config.ts
import * as Sentry from '@sentry/nextjs'

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  
  // Performance Monitoring
  tracesSampleRate: 0.1, // 10% of transactions
  
  // Session Replay
  replaysSessionSampleRate: 0.1,
  replaysOnErrorSampleRate: 1.0,
  
  environment: process.env.NODE_ENV,
  
  // Don't send errors in development
  enabled: process.env.NODE_ENV === 'production',
})
```

### Step 4: Add Environment Variable

```env
NEXT_PUBLIC_SENTRY_DSN=https://xxx@sentry.io/xxx
```

### Step 5: Test Error Tracking

```typescript
// Test component
'use client'

export default function TestError() {
  return (
    <button onClick={() => {
      throw new Error('Test Sentry Error')
    }}>
      Trigger Error
    </button>
  )
}
```

### Features:
- ✅ Error tracking
- ✅ Performance monitoring
- ✅ Session replay
- ✅ Source map support
- ✅ Release tracking
- ✅ User feedback

---

## 🚨 Uptime Monitoring

### Option 1: UptimeRobot (Free)

**Setup:**
1. Go to [uptimerobot.com](https://uptimerobot.com)
2. Sign up (free account: 50 monitors)
3. Add New Monitor:
   - **Monitor Type:** HTTP(s)
   - **Friendly Name:** DailyCup Frontend
   - **URL:** https://dailycup.com
   - **Monitoring Interval:** 5 minutes
4. Add another monitor for API:
   - **URL:** https://api.dailycup.com/health
5. Setup alert contacts (email, SMS, Slack)

**What it monitors:**
- Website uptime
- Response time
- SSL certificate expiry
- Keyword monitoring

---

### Option 2: Better Uptime

**Setup:**
1. Go to [betteruptime.com](https://betteruptime.com)
2. Free tier: 10 monitors
3. More features than UptimeRobot

---

### Option 3: Pingdom (Paid)

**Advanced features:**
- Transaction monitoring
- Real user monitoring (RUM)
- Page speed monitoring

---

## ⚡ Performance Monitoring

### 1. Lighthouse CI

**Setup:**
```bash
npm install -g @lhci/cli

# Create config
# lighthouserc.json
```

```json
{
  "ci": {
    "collect": {
      "url": ["https://dailycup.com"],
      "numberOfRuns": 3
    },
    "assert": {
      "assertions": {
        "categories:performance": ["error", {"minScore": 0.9}],
        "categories:accessibility": ["error", {"minScore": 0.95}],
        "categories:best-practices": ["error", {"minScore": 0.95}],
        "categories:seo": ["error", {"minScore": 0.9}]
      }
    },
    "upload": {
      "target": "temporary-public-storage"
    }
  }
}
```

**Run Lighthouse:**
```bash
lhci autorun
```

---

### 2. Web Vitals Monitoring

**Already implemented via Vercel Analytics**

Monitor:
- **LCP** (Largest Contentful Paint) - Load performance
- **FID** (First Input Delay) - Interactivity
- **CLS** (Cumulative Layout Shift) - Visual stability
- **FCP** (First Contentful Paint) - Perceived load speed
- **TTFB** (Time to First Byte) - Server response time

---

### 3. Real User Monitoring (RUM)

**Using Vercel Speed Insights:**

```typescript
// Already added in previous step
import { SpeedInsights } from '@vercel/speed-insights/next'

export default function RootLayout({ children }) {
  return (
    <html>
      <body>
        {children}
        <SpeedInsights />
      </body>
    </html>
  )
}
```

**Tracks:**
- Actual user experience
- Device types
- Network conditions
- Geographic distribution

---

## 📊 Custom Event Tracking

### E-commerce Events

**Track when user adds to cart:**
```typescript
// components/ProductCard.tsx
import { trackAddToCart } from '@/lib/analytics'

const handleAddToCart = () => {
  addToCart(product)
  
  // Track in Google Analytics
  trackAddToCart({
    item_id: product.id,
    item_name: product.name,
    price: product.price,
    quantity: 1,
  }, product.price)
}
```

**Track checkout:**
```typescript
// app/checkout/page.tsx
import { trackBeginCheckout } from '@/lib/analytics'

useEffect(() => {
  const items = cart.items.map(item => ({
    item_id: item.id,
    item_name: item.name,
    price: item.price,
    quantity: item.quantity,
  }))
  
  trackBeginCheckout(cart.total, items)
}, [])
```

**Track purchase:**
```typescript
// After successful payment
import { trackPurchase } from '@/lib/analytics'

trackPurchase(
  orderId,
  orderTotal,
  orderItems
)
```

---

### Custom Events

```typescript
import { trackEvent } from '@/lib/analytics'

// Track button clicks
trackEvent('click', 'Button', 'CTA Button')

// Track form submissions
trackEvent('submit', 'Form', 'Newsletter Subscription')

// Track feature usage
trackEvent('feature_use', 'PWA', 'Install Prompt Shown')

// Track errors
trackEvent('error', 'API', 'Product Fetch Failed')
```

---

## 📧 Backend Monitoring

### 1. PHP Error Logging

```php
<?php
// config/error_handler.php

// Production error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $message = "[" . date('Y-m-d H:i:s') . "] ";
    $message .= "Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($message, 3, __DIR__ . '/../logs/app-errors.log');
    
    // Send to Sentry (optional)
    // Sentry\captureException(new Exception($errstr));
    
    return true;
});
```

---

### 2. API Request Logging

```php
<?php
// middleware/logger.php

function logApiRequest() {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'endpoint' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
    
    $logFile = __DIR__ . '/../logs/api-access.log';
    file_put_contents($logFile, json_encode($log) . "\n", FILE_APPEND);
}
```

---

### 3. Database Query Monitoring

```php
<?php
// Slow query logging

$start = microtime(true);

// Execute query
$result = $pdo->query($sql);

$duration = microtime(true) - $start;

// Log slow queries (> 1 second)
if ($duration > 1.0) {
    error_log("Slow query ({$duration}s): {$sql}");
}
```

---

## 📋 Monitoring Checklist

### Frontend:
- [ ] Google Analytics installed & verified
- [ ] Vercel Analytics enabled
- [ ] Sentry error tracking setup
- [ ] Custom event tracking implemented
- [ ] E-commerce events tracked

### Backend:
- [ ] Error logging configured
- [ ] API request logging enabled
- [ ] Slow query detection active
- [ ] Health check endpoint created

### Infrastructure:
- [ ] Uptime monitoring (UptimeRobot/Better Uptime)
- [ ] SSL certificate monitoring
- [ ] Database backup monitoring
- [ ] Disk space alerts

### Alerts:
- [ ] Email alerts for downtime
- [ ] Slack/Discord webhooks for critical errors
- [ ] SMS alerts for emergencies
- [ ] Performance degradation alerts

---

## 📊 Monitoring Dashboard Setup

### Recommended Stack:

**Free Tier:**
- Google Analytics (free)
- Vercel Analytics (free for hobby)
- UptimeRobot (free - 50 monitors)
- Sentry (free - 5k events/month)

**Paid Tier (Advanced):**
- Google Analytics 360
- New Relic
- Datadog
- Better Uptime

---

## 🚀 Quick Start Checklist

1. **Install packages:**
   ```bash
   npm install @vercel/analytics @vercel/speed-insights
   ```

2. **Add to layout:**
   - [x] Analytics component
   - [x] Speed Insights component
   - [x] Google Analytics script

3. **Configure environment:**
   - [ ] `NEXT_PUBLIC_GA_ID`
   - [ ] `NEXT_PUBLIC_SENTRY_DSN`

4. **Setup external services:**
   - [ ] Create Google Analytics property
   - [ ] Create UptimeRobot monitors
   - [ ] Create Sentry project (optional)

5. **Deploy & verify:**
   - [ ] Deploy to production
   - [ ] Check Google Analytics Realtime
   - [ ] Check Vercel Analytics dashboard
   - [ ] Test uptime monitors

---

**Monitoring setup complete!** You're now tracking everything! 📊✅
