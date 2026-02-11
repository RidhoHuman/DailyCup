# 🧪 LOW PRIORITY Features Testing Guide
**Comprehensive UI/UX Testing for Features #1-6**

## 📋 Testing Overview

Testing semua 6 fitur LOW PRIORITY yang telah diimplementasikan dari perspektif UI/UX untuk memastikan:
- ✅ Fungsionalitas bekerja dengan baik
- ✅ UI/UX responsif dan user-friendly
- ✅ Tidak ada error atau bug
- ✅ Performa optimal
- ✅ Cross-browser compatibility

---

## 🚀 Setup & Prerequisites

### **A. Laragon (PHP Backend)**
Pastikan Laragon sudah running:
- ✅ Apache Started
- ✅ MySQL Started
- 📍 Test: `http://localhost/DailyCup`

### **B. Next.js Frontend (Optional)**
Untuk test versi modern dengan PWA:

```powershell
# Masuk ke folder Next.js
cd c:\laragon\www\DailyCup\webapp\frontend

# Install dependencies (jika belum)
npm install

# Start development server
npm run dev
```

Tunggu sampai muncul:
```
✓ Ready in 3.2s
✓ Local: http://localhost:3000
```

### **C. Login Requirement**

**Untuk PHP Version:**
- Login di: `http://localhost/DailyCup/auth/login.php`
- Gunakan akun admin

**Untuk Next.js Version:**
- Login di: `http://localhost:3000/login`
- Gunakan akun admin
- ✅ Setelah login, otomatis redirect ke `/admin/dashboard` (Landing Page)
- Dashboard menampilkan overview: stats, recent orders, top products
- Dari Dashboard, bisa navigasi ke Analytics atau menu lain

### **D. Testing Strategy**

**Pilih salah satu:**

✅ **Opsi 1: Test Keduanya** (Recommended untuk validasi lengkap)
- Buka 2 browser tabs
- Tab 1: PHP version → `http://localhost/DailyCup/...`
- Tab 2: Next.js version → `http://localhost:3000/...`
- Bandingkan kedua versi

✅ **Opsi 2: Fokus Next.js** (Modern, dengan PWA)
- Hanya test Next.js (`npm run dev`)
- Lebih interaktif, UI/UX lebih baik
- **PWA features hanya ada di Next.js**

✅ **Opsi 3: Fokus PHP** (Cepat, tidak perlu setup Next.js)
- Langsung test versi PHP
- Tidak perlu `npm run dev`
- Tetapi **tidak bisa test PWA features**

---

## 📍 Quick URL Reference

### **Admin Routes (Next.js)**

| Feature | URL | Authentication |
|---------|-----|----------------|
| Login | `http://localhost:3000/login` | ❌ Public |
| **Dashboard** (Landing) | `http://localhost:3000/admin/dashboard` | ✅ Required |
| **Analytics** (Detailed) | `http://localhost:3000/admin/analytics` | ✅ Required |
| Products | `http://localhost:3000/admin/products` | ✅ Required |
| Orders | `http://localhost:3000/admin/orders` | ✅ Required |
| Customers | `http://localhost:3000/admin/customers` | ✅ Required |
| Settings | `http://localhost:3000/admin/settings` | ✅ Required |

### **Customer Routes (Next.js)**

| Feature | URL | PWA Support |
|---------|-----|-------------|
| Homepage | `http://localhost:3000` | ✅ Yes |
| Menu | `http://localhost:3000/menu` | ✅ Yes |
| Product Detail | `http://localhost:3000/product/:id` | ✅ Yes |
| Cart | `http://localhost:3000/cart` | ✅ Yes |
| Profile | `http://localhost:3000/profile` | ✅ Yes |
| Orders | `http://localhost:3000/orders` | ✅ Yes |
| Offline Page | `http://localhost:3000/offline` | ✅ Yes |

### **Admin Routes (PHP)**

| Feature | URL |
|---------|-----|
| Login | `http://localhost/DailyCup/auth/login.php` |
| **Analytics** | `http://localhost/DailyCup/admin/analytics.php` |
| Products | `http://localhost/DailyCup/admin/products/index.php` |
| Orders | `http://localhost/DailyCup/admin/orders/index.php` |

---

## 🎯 Phase 1: Advanced Analytics Dashboard

### **URL Testing:**

**PHP Version:**
- 📍 `http://localhost/DailyCup/admin/analytics.php`

**Next.js Version:**
- 📍 Landing: `http://localhost:3000/admin/dashboard` (Overview stats)
- 📍 Detail: `http://localhost:3000/admin/analytics` (Charts & trends)

**Perbedaan Dashboard vs Analytics:**
- **Dashboard** = Overview singkat (4 metric cards, recent orders, top products)
- **Analytics** = Detailed analysis (multiple charts, trends, period selector)

### **Step-by-Step Testing:**

#### **1.0 Dashboard Overview (Next.js Only)**
#### **1.1 Access Analytics Page (Detailed)**

**URL:** `http://localhost:3000/admin/analytics` (Next.js) atau `http://localhost/DailyCup/admin/analytics.php` (PHP)

**Test Items:**
- [ ] Buka URL analytics (dari dashboard, klik menu Analytics atau langsung akses URL)
- [ ] Pastikan page load tanpa error
- [ ] Cek loading state/skeleton muncul saat fetch data
- [ ] **Login terlebih dahulu** di `/login` sebagai admin
- [ ] Otomatis redirect ke dashboard setelah login sukses
- [ ] **4 Metric Cards** tampil:
  - [ ] Total Revenue (Rp format)
  - [ ] Total Orders (dengan trend %)
  - [ ] Pending Orders (badge count)
  - [ ] Total Customers
- [ ] **Recent Orders Table** (10 orders terbaru)
- [ ] **Top Products Table** (5 produk terlaris)
- [ ] **Quick Action Cards** (navigasi ke Analytics, Products, Orders)
- [ ] **Alerts Section** (low stock, pending reviews, dll)
- [ ] No console errors (401 atau 404)

#### **1.1 Access Analytics Page (Detailed)**

**URL:** `http://localhost:3000/admin/analytics` (Next.js) atau `http://localhost/DailyCup/admin/analytics.php` (PHP)
- [ ] **Login terlebih dahulu** sebagai admin (lihat Section C di atas)
- [ ] Buka URL admin analytics (pilih PHP atau Next.js)
- [ ] Pastikan page load tanpa error
- [ ] Cek loading state muncul dengan benar
- [ ] Tidak ada error 401 (Unauthorized) atau 404 (Not Found)

#### **1.2 UI/UX Components Check**

**Header & Controls:**
- [ ] **Page Title**: "Analytics Dashboard" tampil jelas
- [ ] **Time Period Selector**: Dropdown dengan options:
  - [ ] Last 7 days
  - [ ] Last 30 days
  - [ ] Last 90 days
  - [ ] This year
- [ ] Dropdown bekerja dengan smooth (no lag)

**Metrics Cards (4 Stats):**
- [ ] **Total Revenue Card**:
  - Currency format benar (Rp 1.234.567)
  - Number formatting dengan thousand separator
  - Icon revenue (💰 atau chart icon)
  
- [ ] **Total Orders Card**:
  - Jumlah order ditampilkan
  - Growth percentage muncul
  - Icon orders (📦 atau shopping icon)
  
- [ ] **Average Order Value Card**:
  - Calculation: Total Revenue / Total Orders
  - Currency format benar
  - Icon average (📊)
  
- [ ] **New Customers Card**:
  - Jumlah customer baru
  - Total customers ditampilkan
  - Icon customers (👥 atau user icon)

**Color Coding:**
- [ ] Growth percentage **positif** → warna hijau (green-600)
- [ ] Growth percentage **negatif** → warna merah (red-600)
- [ ] Color contrast adequate (accessibility)

#### **1.3 Charts Testing**
- [ ] **Daily Revenue Trend Chart**:
  - Chart.js line chart tampil
  - Data loading dari API
  - Hover tooltip bekerja
  - Responsive di berbagai screen size
  
- [ ] **Peak Hours Chart**:
  - Bar chart menampilkan jam sibuk
  - Data akurat (jam 0-23)
  - Legend tampil dengan benar
  - Warna chart konsisten

- [ ] **Top Products Chart**:
  - Doughnut/Pie chart tampil
  - Produk terlaris ditampilkan
  - Percentage calculation akurat
  - Click interaction (jika ada)

- [ ] **Customer Analytics**:
  - New vs Returning customers visualization
  - Data breakdown jelas

#### **1.4 Responsive Design Testing**
- [ ] **Desktop (≥1024px)**: 
  - 4 metrics cards dalam 1 row
  - Charts dalam 2 columns
  - All elements properly aligned
  
- [ ] **Tablet (768px-1023px)**:
  - 2 metrics cards per row
  - Charts stack responsively
  - Adequate spacing
  
- [ ] **Mobile (≤767px)**:
  - 1 metric card per row
  - Charts full width
  - Touch-friendly controls
  - Scrolling smooth

#### **1.5 Data Accuracy Testing**
- [ ] Pilih "Last 7 days" → data update
- [ ] Pilih "Last 30 days" → data update
- [ ] Cross-check dengan database:
  ```sql
  SELECT COUNT(*) as total_orders, SUM(total_amount) as revenue 
  FROM orders 
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
  ```
- [ ] Pastikan growth percentage calculation benar

#### **1.6 Performance Testing**
- [ ] Initial load time < 3 seconds
- [ ] Chart rendering smooth (no lag)
- [ ] Period change < 1 second
- [ ] No memory leaks (cek DevTools Performance)
- [ ] API response time < 500ms

---

## 🎯 Phase 2: Product Recommendations

### **URL Testing:**
- Product Detail: `http://localhost/DailyCup/customer/product_detail.php?id=1`
- Menu Page: `http://localhost/DailyCup/customer/menu.php`
- Next.js: `http://localhost:3000/menu`

### **Step-by-Step Testing:**

#### **2.1 Recommendation Algorithms**

**A. Popular Items (Trending Products)**
- [ ] Buka halaman Menu
- [ ] Scroll ke section "Popular Items" atau "Trending Products"
- [ ] **Verify**:
  - Minimal 4-6 products ditampilkan
  - Sorted by total orders DESC
  - Showcase badge "Popular" atau "Trending"
  - Product images load properly
  
**B. Related Products (Same Category)**
- [ ] Buka product detail page (contoh: Coffee product)
- [ ] Scroll ke section "You May Also Like" atau "Related Products"
- [ ] **Verify**:
  - Products dari kategori yang sama
  - Exclude produk yang sedang dilihat
  - Minimal 3-4 recommendations
  - Click ke produk lain → recommendations update

**C. Collaborative Filtering (Frequently Bought Together)**
- [ ] Buka product detail dengan order history
- [ ] Cari section "Frequently Bought Together" atau "Customers Also Bought"
- [ ] **Verify**:
  - Products based on order patterns
  - Data dari `order_items` table
  - Relevant recommendations (bukan random)
  
**D. Trending Items (Last 7 Days)**
- [ ] Cek homepage atau menu page
- [ ] Section "Trending Now" atau "Hot This Week"
- [ ] **Verify**:
  - Products dengan sales tertinggi dalam 7 hari terakhir
  - Badge "Trending" atau "🔥 Hot"
  - Update setiap hari (cek tomorrow)

#### **2.2 UI/UX Components**

- [ ] **Product Card Design**:
  - Image aspect ratio konsisten
  - Product name clear & readable
  - Price display prominent
  - Badge positioning (top-right corner)
  - Add to cart button visible
  
- [ ] **Carousel/Slider**:
  - Swipe gesture di mobile
  - Arrow navigation di desktop
  - Dots/pagination indicator
  - Smooth transition animation
  - Auto-play (jika enabled)
  
- [ ] **Hover Effects** (Desktop):
  - Product card scale up sedikit
  - Shadow effect
  - Quick view button muncul
  - Smooth transition (300ms)

#### **2.3 Responsive Layout**

- [ ] **Desktop**: 4-5 products per row
- [ ] **Tablet**: 3 products per row
- [ ] **Mobile**: 2 products per row (atau 1 jika design choice)
- [ ] **Large Desktop (≥1440px)**: 5-6 products per row
- [ ] Horizontal scroll jika carousel mode

#### **2.4 Performance Testing**

- [ ] Lazy loading images (only load visible products)
- [ ] Recommendations load without blocking main content
- [ ] API call only once per page load
- [ ] Cache recommendations (session storage)
- [ ] No layout shift during loading

#### **2.5 Data Verification**

```sql
-- Popular Items Query
SELECT p.*, COUNT(oi.id) as order_count
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
GROUP BY p.id
ORDER BY order_count DESC
LIMIT 6;

-- Trending Items (Last 7 Days)
SELECT p.*, COUNT(oi.id) as recent_orders
FROM products p
LEFT JOIN order_items oi ON p.id = oi.product_id
LEFT JOIN orders o ON oi.order_id = o.id
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY p.id
ORDER BY recent_orders DESC
LIMIT 6;
```

---

## 🎯 Phase 3: Seasonal Themes

### **URL Testing:**
- Any customer page: `http://localhost/DailyCup/customer/menu.php`
- Settings: `http://localhost/DailyCup/admin/settings.php` (jika ada theme switcher)

### **Step-by-Step Testing:**

#### **3.1 Theme Detection (Auto-Switching)**

**Test Current Season (February = Valentine)**
- [ ] Buka halaman customer
- [ ] **Expected Theme**: Valentine's Day
- [ ] **Verify Elements**:
  - Primary color: Red/Pink (#e11d48 atau #ec4899)
  - Heart icons atau romantic elements
  - Valentine imagery (roses, hearts, cupid)
  - Special Valentine banner/header

**Manual Date Testing** (Change system date):
- [ ] **March-May**: Spring Theme
  - Pastel colors (green, mint, light blue)
  - Flower/nature imagery
  - Fresh, light design
  
- [ ] **June-August**: Summer Theme
  - Bright colors (yellow, orange, cyan)
  - Sun, beach, tropical elements
  - Energetic, vibrant design
  
- [ ] **September-November**: Fall/Autumn Theme
  - Warm colors (orange, brown, amber)
  - Leaves, pumpkin, harvest imagery
  - Cozy, warm design
  
- [ ] **December**: Christmas Theme
  - Red, green, gold colors
  - Snow, Santa, tree, gifts imagery
  - Festive atmosphere
  
- [ ] **January**: New Year Theme
  - Blue, silver, gold colors
  - Fireworks, celebration imagery
  - Fresh start, optimistic design

- [ ] **Default Theme**: 
  - Brand colors (coffee brown, cream)
  - Clean, minimal design
  - DailyCup signature look

#### **3.2 Theme Components Check**

- [ ] **Color Scheme**:
  - Primary color applied correctly
  - Secondary color complementary
  - Text contrast ratio ≥ 4.5:1 (WCAG AA)
  - Button colors match theme
  - Link colors visible
  
- [ ] **Typography**:
  - Heading fonts consistent
  - Body text readable
  - Font sizes appropriate
  - Line height comfortable (1.5-1.8)
  
- [ ] **Imagery**:
  - Background images/patterns match theme
  - Icons consistent with season
  - Product images not affected by theme
  - Loading placeholders themed
  
- [ ] **Animations**:
  - Theme transition smooth (fade/slide)
  - Seasonal animations (snowfall for Christmas, confetti for New Year)
  - Not too distracting
  - Can be disabled (accessibility)

#### **3.3 UI Elements Testing**

- [ ] **Header/Navigation**:
  - Logo version seasonal (if applicable)
  - Menu background color
  - Active link indicator
  
- [ ] **Buttons**:
  - Primary button style
  - Hover states
  - Active/focus states
  - Disabled states
  
- [ ] **Cards/Products**:
  - Border colors
  - Shadow colors
  - Hover effects
  - Badge colors
  
- [ ] **Footer**:
  - Background color
  - Text color
  - Link colors
  - Social icons

#### **3.4 Theme Persistence**

- [ ] Theme tersimpan di session/cookie
- [ ] Refresh page → theme tetap sama
- [ ] Different tabs → same theme
- [ ] Theme change → apply instantly (no refresh)
- [ ] Manual override (jika ada theme selector)

#### **3.5 Accessibility Testing**

- [ ] Color contrast checker (WebAIM)
- [ ] Screen reader compatibility
- [ ] Keyboard navigation
- [ ] Focus indicators visible
- [ ] Reduced motion respect (prefers-reduced-motion)

#### **3.6 Performance Impact**

- [ ] CSS loaded efficiently (no FOUC - Flash of Unstyled Content)
- [ ] Theme assets cached properly
- [ ] No layout shift during theme change
- [ ] Minimal JavaScript for theme switching
- [ ] Bundle size impact < 50KB

---

## 🎯 Phase 4: Multi-Currency Support

### **URL Testing:**
- Menu: `http://localhost/DailyCup/customer/menu.php`
- Checkout: `http://localhost/DailyCup/customer/checkout.php`

### **Step-by-Step Testing:**

#### **4.1 Currency Selector UI**

- [ ] **Location**: Header/Top bar (easily accessible)
- [ ] **Design**: 
  - Dropdown/Select menu
  - Flag icons untuk each currency
  - Currency code + symbol (e.g., "USD $", "IDR Rp")
  - Current selection highlighted
  
- [ ] **Functionality**:
  - Click to open dropdown
  - Select different currency
  - Instant price update (no page reload)
  - Close dropdown after selection
  - Keyboard navigation (arrow keys)

#### **4.2 Supported Currencies Check**

Test semua 10+ currencies:
- [ ] **IDR** (Indonesian Rupiah) - Rp - Base currency
- [ ] **USD** (US Dollar) - $
- [ ] **EUR** (Euro) - €
- [ ] **GBP** (British Pound) - £
- [ ] **JPY** (Japanese Yen) - ¥
- [ ] **SGD** (Singapore Dollar) - S$
- [ ] **MYR** (Malaysian Ringgit) - RM
- [ ] **AUD** (Australian Dollar) - A$
- [ ] **CNY** (Chinese Yuan) - ¥
- [ ] **THB** (Thai Baht) - ฿

#### **4.3 Exchange Rate Testing**

- [ ] **API Integration**:
  - Check data source (ExchangeRate-API, OpenExchange, atau manual)
  - Last updated timestamp
  - Auto-update interval (daily recommended)
  
- [ ] **Conversion Accuracy**:
  - Pilih IDR (base) → Price normal
  - Pilih USD → Price / exchange_rate
  - Pilih EUR → Price converted correctly
  - Manual calculation check:
    ```
    IDR 50,000 ÷ 15,000 = USD 3.33
    IDR 50,000 ÷ 17,000 = EUR 2.94
    ```
  - Decimal places (2 for most, 0 for JPY/IDR)
  - Rounding consistent (round half up)

#### **4.4 Price Display Testing**

**Menu Page:**
- [ ] Product prices update instantly
- [ ] Currency symbol positioned correctly (before/after based on locale)
- [ ] Thousand separator (comma vs period)
- [ ] Decimal separator (period vs comma)
- [ ] Format examples:
  - IDR: Rp 50.000 (thousand separator: period)
  - USD: $3.33 (decimal: period)
  - EUR: 2,94 € (decimal: comma, symbol after)

**Cart Page:**
- [ ] Item prices
- [ ] Subtotal
- [ ] Taxes (if applicable)
- [ ] Delivery fee
- [ ] Grand total
- [ ] All in selected currency

**Checkout Page:**
- [ ] Order summary prices
- [ ] Payment amount
- [ ] Currency clearly indicated
- [ ] Confirmation dialog shows correct currency

**Order History:**
- [ ] Past orders show original currency
- [ ] Or converted to current selected currency (design choice)
- [ ] Invoice PDF shows correct currency

#### **4.5 Session Persistence**

- [ ] Select currency → Refresh page → Currency remembered
- [ ] Close browser → Reopen → Currency persisted (cookie/localStorage)
- [ ] Different tabs → Same currency
- [ ] Expire after 30 days (session timeout)
- [ ] Guest users → currency saved
- [ ] Logged in users → currency in profile/session

#### **4.6 Edge Cases Testing**

- [ ] **Zero Price**: Rp 0 → $0.00 (handled gracefully)
- [ ] **Large Price**: Rp 10,000,000 → $666.67 (formatted correctly)
- [ ] **Small Price**: Rp 5,000 → $0.33 (no precision loss)
- [ ] **API Failure**: Fallback to cached rates or default rates
- [ ] **Invalid Currency**: Error handling, fallback to IDR
- [ ] **Offline Mode**: Use cached exchange rates

#### **4.7 Responsive Design**

- [ ] **Desktop**: Dropdown in header, full currency list
- [ ] **Mobile**: 
  - Compact selector (icon only, expand on click)
  - Modal/bottom sheet for currency selection
  - Search currency (if many currencies)
  - Touch-friendly list items (min height 44px)

#### **4.8 Performance Testing**

- [ ] Currency change < 200ms (instant feel)
- [ ] No flickering during price update
- [ ] Exchange rates cached client-side
- [ ] Lazy load currency flags
- [ ] Minimal JavaScript overhead

---

## 🎯 Phase 5: Advanced SEO

### **URL Testing:**
- Sitemap: `http://localhost/DailyCup/sitemap.xml`
- Robots: `http://localhost/DailyCup/robots.txt`
- Homepage: `http://localhost/DailyCup/`
- Product: `http://localhost/DailyCup/customer/product_detail.php?id=1`

### **Step-by-Step Testing:**

#### **5.1 Sitemap.xml Validation**

- [ ] **Access**: Open `sitemap.xml` di browser
- [ ] **Format**: Valid XML (no errors)
- [ ] **Structure**:
  ```xml
  <?xml version="1.0" encoding="UTF-8"?>
  <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
      <loc>http://localhost/DailyCup/</loc>
      <lastmod>2026-02-06</lastmod>
      <changefreq>daily</changefreq>
      <priority>1.0</priority>
    </url>
    ...
  </urlset>
  ```
- [ ] **URLs Included** (minimum 17 URLs):
  - Homepage (/)
  - Menu (/customer/menu.php)
  - About (/customer/about.php)
  - Contact (/customer/contact.php)
  - Login (/auth/login.php)
  - Register (/auth/register.php)
  - Cart (/customer/cart.php)
  - Checkout (/customer/checkout.php)
  - Orders (/customer/orders.php)
  - Profile (/customer/profile.php)
  - Track Order (/customer/track_order.php)
  - Product Categories (each category)
  - Top 10 Products (dynamic)
  
- [ ] **Metadata Correct**:
  - `lastmod`: Recent date (auto-update)
  - `changefreq`: Appropriate values (daily/weekly/monthly)
  - `priority`: Homepage = 1.0, others 0.5-0.8
  
- [ ] **Validation Tools**:
  - XML Sitemap Validator: https://www.xml-sitemaps.com/validate-xml-sitemap.html
  - Google Search Console (submit sitemap)

#### **5.2 Robots.txt Validation**

- [ ] **Access**: Open `robots.txt`
- [ ] **Content Check**:
  ```
  User-agent: *
  Allow: /
  Disallow: /admin/
  Disallow: /api/
  Disallow: /config/
  Disallow: /database/
  Disallow: /vendor/
  Disallow: /cache/
  Disallow: /core/
  Disallow: /includes/
  Disallow: /auth/logout.php
  Disallow: /customer/checkout.php
  Disallow: /customer/payment.php
  
  Sitemap: http://localhost/DailyCup/sitemap.xml
  ```

- [ ] **Directives**:
  - Public pages: Allowed
  - Admin pages: Disallowed
  - API endpoints: Disallowed
  - Config files: Disallowed
  - Sitemap URL: Included

- [ ] **Testing Tool**:
  - Robots.txt Tester: https://www.google.com/webmasters/tools/robots-testing-tool
  - Check if /admin/ blocked
  - Check if /customer/menu.php allowed

#### **5.3 Meta Tags Testing**

**Homepage:**
- [ ] Open homepage → View Page Source
- [ ] **Basic Meta**:
  ```html
  <title>DailyCup - Fresh Coffee Delivered Daily</title>
  <meta name="description" content="Order premium coffee, tea, and beverages online. Fresh, fast delivery in Jakarta. Best coffee shop for your daily cup!">
  <meta name="keywords" content="coffee, tea, beverages, online order, delivery, Jakarta, DailyCup">
  <meta name="author" content="DailyCup">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta charset="UTF-8">
  ```

- [ ] **Open Graph (Facebook)**:
  ```html
  <meta property="og:type" content="website">
  <meta property="og:url" content="http://localhost/DailyCup/">
  <meta property="og:title" content="DailyCup - Fresh Coffee Delivered Daily">
  <meta property="og:description" content="Order premium coffee, tea, and beverages...">
  <meta property="og:image" content="http://localhost/DailyCup/assets/images/og-image.jpg">
  <meta property="og:site_name" content="DailyCup">
  ```

- [ ] **Twitter Card**:
  ```html
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:url" content="http://localhost/DailyCup/">
  <meta name="twitter:title" content="DailyCup - Fresh Coffee Delivered Daily">
  <meta name="twitter:description" content="Order premium coffee...">
  <meta name="twitter:image" content="http://localhost/DailyCup/assets/images/twitter-card.jpg">
  ```

**Product Detail Page:**
- [ ] Open product page
- [ ] **Dynamic Meta**:
  - Title: Product name - DailyCup
  - Description: Product description (first 160 chars)
  - OG:type: product
  - OG:image: Product image URL
  - Product price in meta (if applicable)

#### **5.4 Structured Data (JSON-LD)**

**Homepage:**
- [ ] View source → Find JSON-LD script
- [ ] **Organization Schema**:
  ```json
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "DailyCup",
    "url": "http://localhost/DailyCup/",
    "logo": "http://localhost/DailyCup/assets/images/logo.png",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+62-xxx-xxxx",
      "contactType": "Customer Service"
    },
    "sameAs": [
      "https://facebook.com/dailycup",
      "https://instagram.com/dailycup",
      "https://twitter.com/dailycup"
    ]
  }
  ```

**Product Page:**
- [ ] **Product Schema**:
  ```json
  {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "Espresso Premium",
    "image": "http://localhost/DailyCup/assets/images/products/espresso.jpg",
    "description": "Rich, bold espresso made from premium beans",
    "brand": {
      "@type": "Brand",
      "name": "DailyCup"
    },
    "offers": {
      "@type": "Offer",
      "url": "http://localhost/DailyCup/customer/product_detail.php?id=1",
      "priceCurrency": "IDR",
      "price": "25000",
      "availability": "https://schema.org/InStock"
    },
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": "4.5",
      "reviewCount": "89"
    }
  }
  ```

**Breadcrumb Schema** (if applicable):
- [ ] Category → Product breadcrumb
- [ ] JSON-LD BreadcrumbList

#### **5.5 Validation Tools**

- [ ] **Google Rich Results Test**:
  - URL: https://search.google.com/test/rich-results
  - Test homepage URL
  - Test product URL
  - No errors or warnings
  
- [ ] **Schema Markup Validator**:
  - URL: https://validator.schema.org/
  - Paste JSON-LD code
  - Valid schema
  
- [ ] **Meta Tags Checker**:
  - URL: https://metatags.io/
  - Preview Facebook share
  - Preview Twitter share
  - Preview Google search result

#### **5.6 Performance SEO**

- [ ] **Page Speed**:
  - Google PageSpeed Insights
  - Score ≥ 90 (mobile & desktop)
  - Core Web Vitals passed
  
- [ ] **Mobile-Friendly**:
  - Google Mobile-Friendly Test
  - All elements visible
  - No horizontal scroll
  - Touch targets adequate (48x48px)
  
- [ ] **HTTPS** (Production):
  - SSL certificate valid
  - HTTP → HTTPS redirect
  - Mixed content warnings fixed

---

## 🎯 Phase 6: PWA Features

### **URL Testing:**
- Main App: `http://localhost/DailyCup/`
- Manifest: `http://localhost/DailyCup/manifest.json`
- Service Worker: `http://localhost/DailyCup/sw.js`
- Offline Page: `http://localhost/DailyCup/offline.html`

### **Step-by-Step Testing:**

#### **6.1 Manifest.json Validation**

- [ ] **Access**: Open `manifest.json` di browser
- [ ] **Content Check**:
  ```json
  {
    "name": "DailyCup - Coffee Delivery",
    "short_name": "DailyCup",
    "description": "Order premium coffee, tea, and beverages online",
    "start_url": "/DailyCup/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#4a2c2a",
    "orientation": "portrait-primary",
    "icons": [
      {
        "src": "/DailyCup/assets/images/icons/icon-72x72.png",
        "sizes": "72x72",
        "type": "image/png",
        "purpose": "any"
      },
      {
        "src": "/DailyCup/assets/images/icons/icon-192x192.png",
        "sizes": "192x192",
        "type": "image/png",
        "purpose": "any maskable"
      },
      {
        "src": "/DailyCup/assets/images/icons/icon-512x512.png",
        "sizes": "512x512",
        "type": "image/png",
        "purpose": "any maskable"
      }
    ]
  }
  ```

- [ ] **Icons Check**:
  - All icon sizes exist (72, 96, 128, 144, 152, 192, 384, 512)
  - PNG format
  - Square aspect ratio
  - Transparent background (optional)
  - Maskable icons for Android 8+

- [ ] **Validation Tool**:
  - Chrome DevTools → Application → Manifest
  - No errors or warnings
  - Icons preview displays correctly

#### **6.2 Service Worker Registration**

- [ ] **Chrome DevTools**:
  - F12 → Application → Service Workers
  - Status: "activated and is running"
  - Source: `sw.js` or `service-worker.js`
  - Scope: `/DailyCup/`
  
- [ ] **Registration Code** (in main HTML/JS):
  ```javascript
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/DailyCup/sw.js')
      .then(reg => console.log('SW registered:', reg))
      .catch(err => console.error('SW registration failed:', err));
  }
  ```

- [ ] **Console Logs**:
  - No errors
  - "SW registered" message
  - Service Worker installed successfully

#### **6.3 Offline Functionality**

**Offline Page:**
- [ ] Open app while online
- [ ] Turn off internet (Airplane mode or DevTools offline)
- [ ] Navigate to new page
- [ ] **Expected**: Custom offline page displays
- [ ] **Content**:
  - DailyCup logo
  - "You're offline" message
  - Friendly illustration
  - "Try again" button
  - List of cached pages (optional)

**Cached Resources:**
- [ ] DevTools → Application → Cache Storage
- [ ] **Caches**:
  - `dailycup-static-v1`: HTML, CSS, JS
  - `dailycup-images-v1`: Product images, icons
  - `dailycup-api-v1`: API responses (optional)
  
- [ ] **Offline Access**:
  - Homepage loads offline
  - Menu page loads (if cached)
  - Product images show (if cached)
  - Styles and scripts work
  - Cart state persists (localStorage)

**Network Failures:**
- [ ] Offline → Add to cart → Item saved locally
- [ ] Come back online → Sync to server
- [ ] Background sync (if implemented)

#### **6.4 Install Prompt**

**Desktop (Chrome/Edge):**
- [ ] Open app in Chrome
- [ ] Look for install icon in address bar (⊕ or ⬇)
- [ ] Click install
- [ ] **Install Prompt Dialog**:
  - App name: DailyCup
  - App icon displayed
  - "Install" and "Cancel" buttons
  
- [ ] Click "Install"
- [ ] **Result**:
  - App opens in standalone window (no browser UI)
  - App shortcut created on Desktop
  - App listed in Start Menu (Windows) or Applications (Mac)

**Mobile (Android):**
- [ ] Open app in Chrome mobile
- [ ] Banner appears: "Add DailyCup to Home screen"
- [ ] Tap "Add"
- [ ] **Result**:
  - Icon added to home screen
  - Splash screen on launch
  - Fullscreen mode (no browser chrome)
  - App in app drawer

**Custom Install Prompt:**
- [ ] Dismiss browser install prompt
- [ ] Look for custom "Install App" button/banner
- [ ] Click button
- [ ] **UI**:
  - Modal or toast notification
  - App benefits listed:
    - "Access anytime, even offline"
    - "Fast and reliable"
    - "Install to home screen"
  - Install and dismiss buttons
  
- [ ] Click install → Browser prompt appears
- [ ] Complete installation

**Install Detection:**
- [ ] After installation, install button should hide
- [ ] Show "Already installed" or just remove prompt
- [ ] `window.matchMedia('(display-mode: standalone)').matches` = true

#### **6.5 Push Notifications**

**Permission Request:**
- [ ] Open app (first time or after clearing data)
- [ ] Notification permission prompt appears
- [ ] **Timing**: Not immediately (avoid annoyance), trigger on user action
- [ ] **Message**: "Allow DailyCup to send you order updates and offers?"
- [ ] Options: "Allow" / "Block"

**Grant Permission:**
- [ ] Click "Allow"
- [ ] **Console**: Check push subscription object
  ```javascript
  // DevTools Console
  navigator.serviceWorker.ready.then(reg => {
    reg.pushManager.getSubscription().then(sub => {
      console.log('Subscription:', sub);
      // Should show endpoint, keys
    });
  });
  ```

**Send Test Notification:**
- [ ] Admin panel → Notifications section
- [ ] Send test push notification
- [ ] **Or** use admin/test_push.php
- [ ] **Expected**:
  - Notification appears (even if browser minimized)
  - Title, body, icon correct
  - Click notification → Opens app
  - Action buttons work (if any)

**Notification Scenarios:**
- [ ] **Order Status Update**:
  - Title: "Order #1234 is being prepared"
  - Body: "Your order will be ready in 15 minutes"
  - Icon: DailyCup logo
  - Click → Opens order detail page
  
- [ ] **Delivery Update**:
  - Title: "Your order is on the way!"
  - Body: "Driver is 5 minutes away"
  - Icon: Delivery truck icon
  - Click → Opens tracking page
  
- [ ] **Promotional**:
  - Title: "☕ 20% OFF on all Coffee!"
  - Body: "Limited time offer. Order now!"
  - Icon: Coffee cup
  - Click → Opens menu page

**Notification UI/UX:**
- [ ] Badge on app icon (if supported)
- [ ] In-app notification center
- [ ] Mark as read functionality
- [ ] Notification settings (enable/disable categories)

#### **6.6 Background Sync**

- [ ] Add item to cart while offline
- [ ] Come back online
- [ ] **Expected**: Cart syncs to server automatically
- [ ] Check database: cart item saved

**DevTools Check:**
- [ ] Application → Background Sync
- [ ] Registered sync tags
- - Event fires when online

#### **6.7 App-Like Experience**

**Standalone Mode:**
- [ ] Launch installed PWA
- [ ] **Check**:
  - No browser address bar
  - No browser tabs
  - No browser back/forward buttons
  - Full screen immersion
  - Only app header/navigation visible

**Splash Screen (Mobile):**
- [ ] Close PWA completely
- [ ] Tap icon to relaunch
- [ ] **Expected**:
  - Branded splash screen appears for 1-2 seconds
  - Background color = theme_color from manifest
  - App icon centered
  - App name below icon
  - Smooth transition to app

**Status Bar (Mobile):**
- [ ] System status bar (time, battery, signal)
- [ ] Color matches app theme_color
- [ ] Text color contrasts with background

**Navigation:**
- [ ] Back button behavior (Android)
- [ ] Swipe from edge to go back (iOS)
- [ ] App stays in standalone window (no new browser tabs)

#### **6.8 Performance Testing**

**Lighthouse Audit:**
- [ ] DevTools → Lighthouse
- [ ] Run audit (Mobile & Desktop)
- [ ] **PWA Score**: Should be 100 or close
- [ ] **Criteria**:
  - ✅ Installable
  - ✅ PWA optimized
  - ✅ Fast and reliable
  - ✅ Works offline
  - ✅ Configured for custom splash screen
  - ✅ Sets theme color
  - ✅ Content sized correctly for viewport
  - ✅ Display standalone mode
  - ✅ Service worker registered
  - ✅ Redirects HTTP to HTTPS (production)

**Performance Metrics:**
- [ ] **First Contentful Paint (FCP)**: < 1.8s
- [ ] **Largest Contentful Paint (LCP)**: < 2.5s
- [ ] **First Input Delay (FID)**: < 100ms
- [ ] **Cumulative Layout Shift (CLS)**: < 0.1
- [ ] **Time to Interactive (TTI)**: < 3.8s

**Cache Performance:**
- [ ] Second page load (from cache) < 500ms
- [ ] Images load from cache instantly
- [ ] API responses cached (if applicable)
- [ ] Cache size < 50MB (check DevTools)

#### **6.9 Cross-Browser Testing**

- [ ] **Chrome** (Desktop & Mobile): Full PWA support ✅
- [ ] **Edge** (Desktop): Full PWA support ✅
- [ ] **Firefox** (Desktop): Limited (no install, but SW works)
- [ ] **Safari** (iOS 11.3+): PWA support ✅
- [ ] **Samsung Internet** (Android): PWA support ✅
- [ ] **Opera** (Desktop & Mobile): PWA support ✅

**iOS-Specific:**
- [ ] Home screen icon (apple-touch-icon)
  ```html
  <link rel="apple-touch-icon" href="/DailyCup/assets/images/icons/apple-icon-180.png">
  ```
- [ ] Splash screens for different iPhone sizes
- [ ] Status bar styling:
  ```html
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  ```

---

## 📊 Testing Checklist Summary

### **✅ All Features Checklist**

| Feature | UI/UX | Functionality | Responsive | Performance | Accessibility |
|---------|-------|---------------|------------|-------------|---------------|
| **Analytics Dashboard** | ☐ | ☐ | ☐ | ☐ | ☐ |
| **Product Recommendations** | ☐ | ☐ | ☐ | ☐ | ☐ |
| **Seasonal Themes** | ☐ | ☐ | ☐ | ☐ | ☐ |
| **Multi-Currency** | ☐ | ☐ | ☐ | ☐ | ☐ |
| **Advanced SEO** | ☐ | ☐ | ☐ | ☐ | ☐ |
| **PWA Features** | ☐ | ☐ | ☐ | ☐ | ☐ |

---

## 🔧 Testing Tools Checklist

- [ ] **Browser DevTools** (Chrome F12)
- [ ] **Lighthouse** (Performance & PWA Audit)
- [ ] **Google PageSpeed Insights**
- [ ] **Google Mobile-Friendly Test**
- [ ] **Google Rich Results Test**
- [ ] **Schema Markup Validator**
- [ ] **Meta Tags Checker** (metatags.io)
- [ ] **XML Sitemap Validator**
- [ ] **Robots.txt Tester**
- [ ] **WebAIM Contrast Checker**
- [ ] **WAVE Accessibility Tool**
- [ ] **Responsive Design Checker** (responsivedesignchecker.com)
- [ ] **BrowserStack** (Cross-browser testing)

---

## 🐛 Common Issues & Solutions

### **Analytics Dashboard**
- **Issue**: Charts not loading
- **Solution**: Check Chart.js CDN, verify API endpoint, check console errors

### **Product Recommendations**
- **Issue**: Same products repeating
- **Solution**: Check SQL query LIMIT, exclude current product, verify order_items data

### **Seasonal Themes**
- **Issue**: Theme not changing
- **Solution**: Clear session/cookies, verify date detection logic, check CSS loading

### **Multi-Currency**
- **Issue**: Wrong conversion rates
- **Solution**: Update exchange rates, check API key, verify calculation formula

### **Advanced SEO**
- **Issue**: Sitemap 404 error
- **Solution**: Check .htaccess rules, verify file path, check server permissions

### **PWA Features**
- **Issue**: Service worker not registering
- **Solution**: Check HTTPS (required in production), verify sw.js path, check scope

---

## 📝 Testing Report Template

Setelah testing selesai, dokumentasikan hasil dengan format:

```markdown
# Testing Report - LOW PRIORITY Features
Date: [Date]
Tester: [Name]

## Feature 1: Advanced Analytics Dashboard
- ✅ UI/UX: All components display correctly
- ✅ Functionality: Charts load, data accurate
- ✅ Responsive: Works on mobile/tablet/desktop
- ⚠️ Performance: Initial load 4s (target: <3s)
- ✅ Accessibility: Contrast ratio passed

**Issues Found:**
1. Chart tooltip color hard to read on dark backgrounds
2. Mobile: Dropdown overlaps on small screens

**Action Items:**
- Fix tooltip styling
- Adjust dropdown positioning for mobile

---

## Overall Summary
- **PASS**: 5/6 features
- **PARTIAL PASS**: 1/6 features (Performance needs improvement)
- **FAIL**: 0/6 features

**Recommendation**: Ready for production after performance optimization.
```

---

## 🎉 Next Steps After Testing

1. **Fix all critical bugs** found during testing
2. **Optimize performance** (if scores < 90)
3. **Document edge cases** discovered
4. **Create user manual** for admin features
5. **Proceed to HIGH PRIORITY features** implementation

---

**Last Updated**: February 6, 2026
**Version**: 1.0
**Status**: Ready for Testing 🚀
