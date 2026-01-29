# 🚀 Performance Optimization Checklist

Complete checklist untuk optimasi performa DailyCup sebelum production.

---

## 📊 Current Performance Baseline

Run Lighthouse audit:
```bash
npm run build
npm run start
# Open Chrome DevTools → Lighthouse → Run audit
```

**Target Metrics:**
- Performance: > 90
- Accessibility: > 95
- Best Practices: > 95
- SEO: > 90

---

## ⚡ Frontend Optimization

### 1. Image Optimization ✅

**Status:** Implemented
- [x] Next.js Image component used throughout
- [x] WebP/AVIF format support
- [x] Responsive image sizes
- [x] Lazy loading enabled
- [x] Image CDN configured

**Additional Steps:**
```bash
# Convert images to WebP
npm install sharp
```

```typescript
// Optimize images before upload
import sharp from 'sharp'

await sharp(inputPath)
  .resize(1200, 630, { fit: 'cover' })
  .webp({ quality: 85 })
  .toFile(outputPath)
```

---

### 2. Code Splitting & Lazy Loading ✅

**Current Implementation:**
```typescript
// Dynamic imports for heavy components
import dynamic from 'next/dynamic'

const AdminDashboard = dynamic(() => import('./AdminDashboard'), {
  loading: () => <Spinner />,
  ssr: false,
})

const PushNotificationManager = dynamic(() => import('./PushNotificationManager'), {
  ssr: false,
})
```

**Additional Optimizations:**
```typescript
// Lazy load third-party libraries
const Chart = dynamic(() => import('react-chartjs-2'), {
  ssr: false,
})

// Lazy load below-the-fold content
const Newsletter = dynamic(() => import('./Newsletter'), {
  loading: () => <div className="h-64" />,
})
```

---

### 3. Bundle Size Analysis

**Run Bundle Analyzer:**
```bash
npm install --save-dev @next/bundle-analyzer
```

```typescript
// next.config.ts
const withBundleAnalyzer = require('@next/bundle-analyzer')({
  enabled: process.env.ANALYZE === 'true',
})

module.exports = withBundleAnalyzer(nextConfig)
```

```bash
# Analyze bundle
ANALYZE=true npm run build
```

**Optimization Strategies:**
- Remove unused dependencies
- Use tree-shaking
- Split vendor bundles
- Lazy load heavy libraries

---

### 4. Font Optimization ✅

**Status:** Already optimized
```typescript
// Using next/font for automatic optimization
import { Poppins, Russo_One } from 'next/font/google'

const poppins = Poppins({
  subsets: ['latin'],
  weight: ['300', '400', '600'],
  display: 'swap', // Prevents layout shift
  preload: true,
})
```

**Best Practices:**
- [x] Font subsetting (latin only)
- [x] Font display: swap
- [x] Preload critical fonts
- [x] Self-hosted via next/font

---

### 5. JavaScript Optimization

**Checklist:**
- [x] Remove console.logs in production
- [x] Minification enabled (SWC)
- [ ] Remove unused code
- [ ] Tree-shaking verified
- [ ] Source maps disabled in production

**Production Build Config:**
```typescript
// next.config.ts
const nextConfig = {
  swcMinify: true, // ✅ Enabled
  compress: true,   // ✅ Enabled
  
  // Disable source maps in production
  productionBrowserSourceMaps: false,
  
  // Remove console logs
  compiler: {
    removeConsole: process.env.NODE_ENV === 'production',
  },
}
```

---

### 6. CSS Optimization

**Tailwind CSS Purge:**
```typescript
// tailwind.config.ts
module.exports = {
  content: [
    './app/**/*.{js,ts,jsx,tsx}',
    './components/**/*.{js,ts,jsx,tsx}',
  ],
  // Purge unused CSS automatically
}
```

**CSS Minification:**
- [x] Automatic via Next.js
- [x] Critical CSS inlined
- [x] Unused CSS purged

---

### 7. Caching Strategy

**Next.js Automatic Caching:**
```typescript
// Static assets: 1 year cache
// Images: 1 year cache
// Service Worker: no-cache (always fresh)
```

**Custom Headers (already in next.config.ts):**
```typescript
async headers() {
  return [
    {
      source: '/:all*(jpg|jpeg|png|webp)',
      headers: [
        {
          key: 'Cache-Control',
          value: 'public, max-age=31536000, immutable',
        },
      ],
    },
  ]
}
```

---

## 🗄️ Backend Optimization

### 1. Database Optimization

**Indexing:**
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_products_stock ON products(stock);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);

-- Composite indexes for common queries
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
CREATE INDEX idx_products_category_featured ON products(category_id, is_featured);

-- Show indexes
SHOW INDEXES FROM products;
```

**Query Optimization:**
```sql
-- Before: N+1 query problem
SELECT * FROM orders WHERE user_id = 1;
-- Then for each order:
SELECT * FROM order_items WHERE order_id = ?;

-- After: Single query with JOIN
SELECT 
  o.*, 
  oi.*, 
  p.name as product_name
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN products p ON oi.product_id = p.id
WHERE o.user_id = 1;
```

**Database Configuration:**
```ini
# my.cnf / my.ini
[mysqld]
# Query cache
query_cache_type = 1
query_cache_size = 64M

# InnoDB buffer pool (adjust based on RAM)
innodb_buffer_pool_size = 512M

# Connection pool
max_connections = 100
```

---

### 2. PHP Optimization

**OPcache Configuration:**
```ini
; php.ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

**Composer Autoloader Optimization:**
```bash
cd backend
composer install --optimize-autoloader --no-dev
composer dump-autoload --optimize --no-dev
```

---

### 3. API Response Caching

**Simple File-Based Cache:**
```php
<?php
// cache_helper.php
function getCache($key, $ttl = 300) {
    $cache_file = __DIR__ . "/cache/{$key}.json";
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    return null;
}

function setCache($key, $data) {
    $cache_file = __DIR__ . "/cache/{$key}.json";
    file_put_contents($cache_file, json_encode($data));
}

// Usage in products.php
$cache_key = 'products_list_' . md5(serialize($_GET));
$cached = getCache($cache_key, 300); // 5 minutes

if ($cached) {
    echo json_encode($cached);
    exit;
}

// Fetch from database
$products = fetchProducts();

// Save to cache
setCache($cache_key, $products);
echo json_encode($products);
```

**Redis Cache (Advanced):**
```php
<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$cache_key = 'products_featured';
$cached = $redis->get($cache_key);

if ($cached) {
    echo $cached;
    exit;
}

$products = fetchProducts();
$redis->setex($cache_key, 300, json_encode($products)); // 5 min TTL
echo json_encode($products);
```

---

### 4. Gzip Compression

**Apache (.htaccess):**
```apache
# Enable Gzip compression
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
```

**Nginx:**
```nginx
gzip on;
gzip_comp_level 6;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
```

---

## 📦 CDN & Static Assets

### 1. Use CDN for Static Assets

**Options:**
- **Cloudflare** (Free tier available)
- **BunnyCDN** ($1/mo)
- **AWS CloudFront**

**Setup Cloudflare:**
1. Sign up at cloudflare.com
2. Add your domain
3. Update nameservers
4. Enable caching rules
5. Enable minification (JS, CSS, HTML)

---

### 2. Optimize Asset Delivery

**Image CDN:**
```typescript
// next.config.ts
images: {
  loader: 'cloudinary', // or 'imgix', 'cloudflare'
  path: 'https://your-cdn.com/',
}
```

---

## 📊 Monitoring & Testing

### 1. Performance Monitoring

**Install Vercel Analytics:**
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

---

### 2. Lighthouse CI

```bash
npm install -g @lhci/cli

# Run Lighthouse
lhci autorun --url=https://dailycup.com

# Or via npm script
npm run lighthouse
```

---

### 3. Web Vitals Tracking

**Already Implemented via Vercel Analytics**

Core Web Vitals:
- **LCP** (Largest Contentful Paint): < 2.5s
- **FID** (First Input Delay): < 100ms
- **CLS** (Cumulative Layout Shift): < 0.1

---

## ✅ Final Checklist

### Images:
- [x] Next.js Image component used
- [x] WebP/AVIF support
- [x] Lazy loading enabled
- [ ] All images compressed (<100KB)
- [ ] Responsive images for all sizes

### Code:
- [x] Code splitting implemented
- [x] Dynamic imports for heavy components
- [x] Tree-shaking enabled
- [ ] Bundle size analyzed
- [ ] Unused code removed

### CSS:
- [x] Tailwind purge enabled
- [x] CSS minified
- [x] Critical CSS inlined

### JavaScript:
- [x] Minification enabled (SWC)
- [x] Console logs removed
- [x] Source maps disabled (production)

### Caching:
- [x] Static assets cached (1 year)
- [x] Service Worker caching
- [ ] API response caching
- [ ] Database query caching

### Database:
- [ ] Indexes added
- [ ] Queries optimized
- [ ] Connection pooling configured

### PHP:
- [ ] OPcache enabled
- [ ] Composer optimized
- [ ] Gzip enabled

### CDN:
- [ ] Cloudflare configured
- [ ] Asset delivery optimized
- [ ] DNS optimized

### Monitoring:
- [ ] Analytics installed
- [ ] Error tracking setup
- [ ] Performance monitoring active

---

## 🎯 Performance Goals

**Before Optimization:**
- Load Time: ~5s
- Bundle Size: ~500KB
- Lighthouse Score: 70

**After Optimization (Target):**
- Load Time: < 2s ⚡
- Bundle Size: < 200KB 📦
- Lighthouse Score: > 90 🎯

---

**Ready for optimization? Let's boost that performance!** 🚀
