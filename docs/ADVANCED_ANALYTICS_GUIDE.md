# Advanced Analytics Dashboard - Implementation Guide

## Overview
Advanced Analytics Dashboard memberikan insights komprehensif tentang performa bisnis DailyCup dengan visualisasi data yang interaktif.

## Features Implemented

### 1. Backend API (`/webapp/backend/api/analytics.php`)

#### Endpoint
```
GET /webapp/backend/api/analytics.php?period={period}
```

#### Parameters
- `period`: Filter periode waktu
  - `7days` - Last 7 days
  - `30days` - Last 30 days (default)
  - `90days` - Last 90 days
  - `1year` - Last year
  - `all` - All time

#### Authentication
Requires Bearer token in Authorization header.

#### Response Structure
```json
{
  "success": true,
  "period": "30days",
  "date_range": {
    "start": "2024-01-01 00:00:00",
    "end": "2024-01-31 23:59:59"
  },
  "revenue": {
    "total": 15000000,
    "avg_order_value": 75000,
    "highest_order": 250000,
    "growth_percentage": 12.5
  },
  "orders": {
    "total": 200,
    "completed": 180,
    "cancelled": 20,
    "growth_percentage": 8.3,
    "status_distribution": [
      { "status": "pending", "count": 10 },
      { "status": "completed", "count": 180 },
      { "status": "cancelled", "count": 20 }
    ]
  },
  "customers": {
    "total": 150,
    "new": 25,
    "top_customers": [
      {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "total_orders": 15,
        "total_spent": 1125000
      }
    ]
  },
  "products": {
    "top_selling": [
      {
        "id": 1,
        "name": "Espresso",
        "category": "Coffee",
        "price": 25000,
        "times_ordered": 50,
        "total_quantity": 120,
        "total_revenue": 3000000
      }
    ],
    "category_performance": [
      {
        "category": "Coffee",
        "orders": 100,
        "items_sold": 250,
        "revenue": 6000000
      }
    ]
  },
  "trends": {
    "daily_revenue": [
      {
        "date": "2024-01-01",
        "orders": 10,
        "revenue": 750000
      }
    ],
    "peak_hours": [
      {
        "hour": 8,
        "orders": 25,
        "revenue": 1875000
      }
    ]
  },
  "payment_methods": [
    {
      "payment_method": "transfer",
      "count": 120,
      "revenue": 9000000
    }
  ],
  "reviews": {
    "avg_rating": 4.5,
    "total": 85
  }
}
```

### 2. Frontend Dashboard (`/webapp/frontend/app/admin/(panel)/analytics/page.tsx`)

#### Key Metrics Cards
- **Total Revenue**: Total revenue dengan growth percentage vs previous period
- **Total Orders**: Jumlah orders dengan growth percentage
- **Avg Order Value**: Rata-rata nilai order dan highest order
- **New Customers**: Jumlah customer baru dan total customers

#### Charts & Visualizations
1. **Daily Revenue Trend** - Line chart showing daily revenue over selected period
2. **Peak Hours** - Bar chart showing orders by hour
3. **Category Performance** - Bar chart showing revenue by category
4. **Payment Methods** - Doughnut chart showing payment method distribution
5. **Order Status Distribution** - Progress bars showing order status breakdown
6. **Reviews Overview** - Star rating display with total review count

#### Top Lists
- **Top Selling Products** - Top 5 products by revenue
- **Top Customers** - Top 5 customers by total spent

### 3. Data Sources
All analytics data is fetched from real database tables:
- `orders` - Order transactions
- `order_items` - Product items in orders
- `users` - Customer data
- `products` - Product catalog
- `reviews` - Product reviews

**NO HARDCODED OR SAMPLE DATA** - All metrics are calculated from actual database records.

## Installation

### 1. Install Dependencies
```bash
cd webapp/frontend
npm install chart.js react-chartjs-2
```

### 2. Database
No additional tables needed. Uses existing tables:
- orders
- order_items
- users
- products
- reviews

### 3. Backend Setup
Backend API is ready at `/webapp/backend/api/analytics.php`
- Uses mysqli connection
- Token-based authentication
- Period-based filtering

### 4. Frontend Setup
Dashboard is accessible at `/admin/analytics`
- Already integrated in admin sidebar
- Uses Chart.js for visualizations
- Responsive design

## Usage

### Admin Access
1. Login to admin panel
2. Click "Analytics" in sidebar
3. Select period from dropdown (7 days, 30 days, 90 days, 1 year, all time)
4. Dashboard will auto-refresh with selected period data

### Features
- **Period Selection**: Change time range to view different periods
- **Real-time Data**: All data fetched from live database
- **Growth Comparison**: Compare current period vs previous period
- **Interactive Charts**: Hover for detailed information
- **Responsive Design**: Works on desktop, tablet, and mobile

## Technical Details

### Performance Optimizations
- Single API call fetches all analytics data
- Efficient SQL queries with proper indexing
- Client-side chart rendering
- Period-based caching possible (future enhancement)

### Security
- Token-based authentication required
- Admin role validation
- SQL injection prevention with prepared statements
- XSS protection with proper sanitization

### Charts Library
Using Chart.js v4 with react-chartjs-2:
- Line charts for trends
- Bar charts for comparisons
- Doughnut charts for distributions
- Fully responsive
- Interactive tooltips

## Testing

### Test Backend API
```bash
# Login as admin first to get token
# Then test analytics endpoint
curl "http://localhost/DailyCup/webapp/backend/api/analytics.php?period=30days" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Test Frontend
1. Start dev server: `npm run dev`
2. Login as admin
3. Navigate to `/admin/analytics`
4. Try different period filters
5. Verify all charts load correctly

## Future Enhancements
- Export analytics to PDF/Excel
- Custom date range selection
- More granular time periods (today, yesterday, this week, this month)
- Predictive analytics
- Comparison between multiple periods
- Real-time dashboard updates
- Email reports
- Alerts for anomalies

## Files Modified/Created

### Created:
- `/webapp/backend/api/analytics.php` - Complete rewrite for comprehensive analytics
- `/webapp/frontend/app/admin/(panel)/analytics/page.tsx` - New analytics dashboard

### Dependencies Added:
- `chart.js` - Chart rendering library
- `react-chartjs-2` - React wrapper for Chart.js

## Summary
Advanced Analytics Dashboard telah berhasil diimplementasikan dengan:
✅ Comprehensive backend API dengan 9 kategori analytics
✅ Beautiful frontend dashboard dengan 6 chart types
✅ Real-time data dari database (NO sample/hardcoded data)
✅ Period filtering (7 days - All time)
✅ Growth comparison dengan previous period
✅ Top products & customers ranking
✅ Responsive & interactive charts
✅ Proper authentication & security
