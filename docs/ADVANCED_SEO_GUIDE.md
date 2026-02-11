# 🔍 ADVANCED SEO IMPLEMENTATION GUIDE
**DailyCup Coffee Shop - Complete SEO Solution**

---

## 📋 OVERVIEW

Advanced SEO system with:
- ✅ Dynamic XML Sitemap generation
- ✅ Robots.txt configuration
- ✅ Meta tags optimization (title, description, keywords)
- ✅ Open Graph tags (Social media sharing)
- ✅ Twitter Card tags
- ✅ Structured Data (JSON-LD schema.org)
- ✅ SEO Analytics tracking
- ✅ 301/302 Redirects management
- ✅ Canonical URLs

---

## 🗄️ DATABASE SCHEMA

### Tables Created:

#### 1. `seo_metadata`
Stores SEO metadata for pages, products, categories.

**Key columns:**
- `slug` - URL slug (unique identifier)
- `title` - SEO title (60 chars recommended)
- `meta_description` - Meta description (160 chars)
- `meta_keywords` - Keywords (comma-separated)
- `canonical_url` - Canonical URL
- `og_*` - Open Graph tags
- `twitter_*` - Twitter Card tags
- `structured_data` - JSON-LD structured data
- `robots` - Robots meta tag

#### 2. `sitemap_config`
Controls sitemap generation settings.

**Columns:**
- `entity_type` - page, product, category, article
- `priority` - Sitemap priority (0.0 - 1.0)
- `change_frequency` - daily, weekly, monthly, etc.

#### 3. `seo_redirects`
Manages 301/302 redirects.

**Columns:**
- `old_url` - Old URL path
- `new_url` - New URL path
- `redirect_type` - 301, 302, 307
- `hit_count` - Usage tracking

#### 4. `seo_analytics`
Tracks SEO performance metrics.

**Columns:**
- `page_url` - Page visited
- `search_keyword` - Search keyword
- `referrer` - Referrer URL
- `device_type` - desktop, mobile, tablet, bot

---

## 🔌 API ENDPOINTS

### Base URL
```
http://localhost/DailyCup/webapp/backend/api/seo.php
```

### Public Endpoints (No Auth Required)

#### 1. **Get Page Metadata**
```http
GET /seo.php?action=get_meta&slug=menu
```

**Response:**
```json
{
  "success": true,
  "message": "Metadata retrieved successfully",
  "data": {
    "title": "Our Menu - Fresh Coffee & Delicious Food | DailyCup",
    "meta_description": "Browse our extensive menu...",
    "meta_keywords": "coffee menu, drink menu",
    "og_title": "DailyCup Full Menu - Coffee, Food & Beverages",
    "og_description": "Explore our delicious menu...",
    "structured_data": {...}
  }
}
```

#### 2. **Generate Sitemap**
```http
GET /seo.php?action=sitemap
```

**Response:** XML sitemap
```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://dailycup.com/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
    <lastmod>2026-02-05</lastmod>
  </url>
  ...
</urlset>
```

#### 3. **Generate Robots.txt**
```http
GET /seo.php?action=robots
```

**Response:** Text file
```
User-agent: *
Allow: /
Disallow: /admin/
Sitemap: https://dailycup.com/sitemap.xml
```

#### 4. **Check Redirect**
```http
GET /seo.php?action=check_redirect&url=/old-menu
```

**Response:**
```json
{
  "success": true,
  "message": "Redirect found",
  "data": {
    "new_url": "/menu",
    "redirect_type": "301"
  }
}
```

#### 5. **Get SEO Analytics**
```http
GET /seo.php?action=analytics&days=30
```

**Response:**
```json
{
  "success": true,
  "data": {
    "top_pages": [...],
    "top_keywords": [...],
    "device_breakdown": {
      "desktop": 1250,
      "mobile": 890,
      "tablet": 120,
      "bot": 45
    }
  }
}
```

---

### Admin Endpoints (Auth Required)

#### 6. **Update Metadata**
```http
POST /seo.php?action=update_meta
Content-Type: application/json

{
  "slug": "menu",
  "entity_type": "page",
  "title": "Our Menu - DailyCup",
  "meta_description": "Browse our extensive menu...",
  "meta_keywords": "coffee, menu, drinks",
  "og_title": "DailyCup Menu",
  "og_description": "Delicious coffee and food",
  "og_image": "https://dailycup.com/images/menu-og.jpg",
  "structured_data": {
    "@context": "https://schema.org",
    "@type": "Menu",
    "name": "DailyCup Menu"
  }
}
```

#### 7. **Add Redirect**
```http
POST /seo.php?action=add_redirect
Content-Type: application/json

{
  "old_url": "/products",
  "new_url": "/menu",
  "redirect_type": "301"
}
```

---

## 🛠️ PHP HELPER FUNCTIONS

### Location
```
webapp/backend/helpers/seo_helper.php
```

### Usage in Legacy Pages

#### 1. **Render Meta Tags**
```php
<?php
require_once __DIR__ . '/../webapp/backend/helpers/seo_helper.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $pageTitle ?? 'DailyCup'; ?></title>
    
    <?php echo renderMetaTags('menu'); ?>
    <?php echo renderStructuredData('menu'); ?>
</head>
<body>
```

**Output:**
```html
<meta name="description" content="Browse our extensive menu...">
<meta name="keywords" content="coffee menu, drink menu">
<meta name="robots" content="index, follow">
<meta property="og:title" content="DailyCup Full Menu">
<meta property="og:description" content="Explore our delicious menu...">
<meta name="twitter:card" content="summary_large_image">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Menu",
  "name": "DailyCup Menu"
}
</script>
```

#### 2. **Check SEO Redirects**
```php
<?php
require_once __DIR__ . '/../webapp/backend/helpers/seo_helper.php';

// Place at top of page to handle redirects
checkSeoRedirect();
?>
```

#### 3. **Track SEO Analytics**
```php
<?php
// Track page visit
trackSeoVisit();
?>
```

#### 4. **Generate Product Schema**
```php
<?php
$product = [
    'id' => 123,
    'name' => 'Espresso',
    'description' => 'Rich Italian espresso',
    'price' => 25000,
    'stock' => 100,
    'avg_rating' => 4.8,
    'review_count' => 42
];

$schema = generateProductSchema($product);
echo renderStructuredData(null, $schema);
?>
```

#### 5. **Generate Breadcrumbs**
```php
<?php
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Menu', 'url' => '/menu'],
    ['name' => 'Coffee', 'url' => '/menu/coffee']
];

$schema = generateBreadcrumbSchema($breadcrumbs);
echo renderStructuredData(null, $schema);
?>
```

---

## ⚛️ NEXT.JS INTEGRATION

### 1. **Metadata API (app/layout.tsx)**
```typescript
import { Metadata } from 'next';

export const metadata: Metadata = {
  title: {
    default: 'DailyCup - Premium Coffee Shop',
    template: '%s | DailyCup'
  },
  description: 'Fresh coffee and delicious food delivered to your door',
  keywords: ['coffee', 'daily cup', 'coffee shop', 'delivery'],
  authors: [{ name: 'DailyCup Team' }],
  openGraph: {
    title: 'DailyCup - Premium Coffee Shop',
    description: 'Experience the finest coffee in town',
    url: 'https://dailycup.com',
    siteName: 'DailyCup',
    images: [
      {
        url: 'https://dailycup.com/og-image.jpg',
        width: 1200,
        height: 630,
      }
    ],
    locale: 'id_ID',
    type: 'website',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'DailyCup - Premium Coffee Shop',
    description: 'Fresh coffee daily',
    images: ['https://dailycup.com/twitter-image.jpg'],
  },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      'max-image-preview': 'large',
      'max-snippet': -1,
    },
  },
};
```

### 2. **Dynamic Page Metadata**
```typescript
// app/menu/page.tsx
import { Metadata } from 'next';

export async function generateMetadata(): Promise<Metadata> {
  const meta = await fetch('http://localhost/DailyCup/webapp/backend/api/seo.php?action=get_meta&slug=menu')
    .then(res => res.json());

  return {
    title: meta.data.title,
    description: meta.data.meta_description,
    keywords: meta.data.meta_keywords.split(','),
    openGraph: {
      title: meta.data.og_title,
      description: meta.data.og_description,
      images: [meta.data.og_image],
    },
  };
}
```

### 3. **Sitemap Generation (app/sitemap.ts)**
```typescript
import { MetadataRoute } from 'next';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = 'https://dailycup.com';
  
  // Fetch from API
  const response = await fetch(`${baseUrl}/webapp/backend/api/seo.php?action=sitemap`);
  const xml = await response.text();
  
  // Or generate manually
  return [
    {
      url: baseUrl,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1,
    },
    {
      url: `${baseUrl}/menu`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.9,
    },
    // ... more URLs
  ];
}
```

### 4. **Robots.txt (app/robots.ts)**
```typescript
import { MetadataRoute } from 'next';

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: '*',
        allow: '/',
        disallow: ['/admin/', '/api/', '/customer/cart', '/customer/checkout'],
      },
      {
        userAgent: 'Googlebot',
        allow: '/',
        crawlDelay: 1,
      },
    ],
    sitemap: 'https://dailycup.com/sitemap.xml',
  };
}
```

### 5. **JSON-LD Structured Data Component**
```typescript
// components/StructuredData.tsx
export function StructuredData({ data }: { data: object }) {
  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
    />
  );
}

// Usage in page
import { StructuredData } from '@/components/StructuredData';

export default function ProductPage({ product }) {
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.name,
    description: product.description,
    offers: {
      '@type': 'Offer',
      price: product.price,
      priceCurrency: 'IDR',
    },
  };

  return (
    <>
      <StructuredData data={schema} />
      {/* page content */}
    </>
  );
}
```

---

## 🧪 TESTING & VALIDATION

### 1. **Test Metadata**
```bash
curl "http://localhost/DailyCup/webapp/backend/api/seo.php?action=get_meta&slug=menu"
```

### 2. **Test Sitemap**
```bash
curl "http://localhost/DailyCup/webapp/backend/api/seo.php?action=sitemap"
```

### 3. **Test Robots.txt**
```bash
curl "http://localhost/DailyCup/webapp/backend/api/seo.php?action=robots"
```

### 4. **Validate with Tools**

- **Google Rich Results Test:** https://search.google.com/test/rich-results
- **Schema Markup Validator:** https://validator.schema.org/
- **Open Graph Debugger:** https://developers.facebook.com/tools/debug/
- **Twitter Card Validator:** https://cards-dev.twitter.com/validator
- **Google Search Console:** Submit sitemap

---

## 📊 SEO BEST PRACTICES

### Title Tag
- Length: 50-60 characters
- Include primary keyword
- Brand at end
- Example: "Fresh Coffee Menu | DailyCup"

### Meta Description
- Length: 150-160 characters
- Include call-to-action
- Compelling and descriptive
- Example: "Browse our extensive menu of premium coffee drinks, fresh pastries, and delicious meals. Order now for fast delivery!"

### Keywords
- 5-10 relevant keywords
- Mix of short and long-tail keywords
- Don't stuff keywords

### URL Structure
- Short and descriptive
- Use hyphens, not underscores
- Include keywords
- Example: `/menu/coffee/espresso`

### Structured Data
- Use schema.org vocabulary
- Implement breadcrumbs
- Add product, organization, article schemas
- Validate with Google Rich Results Test

### Images
- Use descriptive alt tags
- Optimize file size
- Use modern formats (WebP)
- Include in Open Graph

---

## 🚀 PRODUCTION CHECKLIST

- [ ] Sitemap submitted to Google Search Console
- [ ] Robots.txt configured correctly
- [ ] All pages have unique meta titles & descriptions
- [ ] Open Graph tags on all pages
- [ ] Twitter Card tags on all pages
- [ ] Structured data validated
- [ ] Canonical URLs set
- [ ] 301 redirects for old URLs
- [ ] SSL certificate installed (HTTPS)
- [ ] Mobile-friendly test passed
- [ ] Page speed optimized (Core Web Vitals)
- [ ] XML sitemap includes all important pages
- [ ] Analytics tracking implemented

---

## 📈 MONITORING

### Google Search Console
- Monitor indexing status
- Check for crawl errors
- Review search analytics
- Submit sitemaps

### SEO Analytics Dashboard
```sql
-- Top performing pages
SELECT page_url, COUNT(*) as visits
FROM seo_analytics
WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY page_url
ORDER BY visits DESC
LIMIT 10;

-- Top search keywords
SELECT search_keyword, COUNT(*) as count
FROM seo_analytics
WHERE search_keyword IS NOT NULL
GROUP BY search_keyword
ORDER BY count DESC
LIMIT 10;
```

---

## ✅ IMPLEMENTATION STATUS

- [x] Database schema created
- [x] SEO API endpoints built
- [x] PHP helper functions created
- [x] Sitemap generation working
- [x] Robots.txt generation working
- [x] Meta tags implementation
- [x] Open Graph tags
- [x] Twitter Cards
- [x] Structured data (JSON-LD)
- [x] SEO analytics tracking
- [x] Redirect management
- [ ] Next.js Metadata API (recommended for new pages)
- [ ] Google Search Console setup
- [ ] Production testing

---

## 🔗 USEFUL RESOURCES

- Schema.org Documentation: https://schema.org/
- Google SEO Starter Guide: https://developers.google.com/search/docs/fundamentals/seo-starter-guide
- Open Graph Protocol: https://ogp.me/
- Twitter Cards: https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards
- Next.js Metadata: https://nextjs.org/docs/app/building-your-application/optimizing/metadata

---

**Implementation Complete! 🎉**
All SEO features are now ready for use in both legacy PHP pages and Next.js application.
