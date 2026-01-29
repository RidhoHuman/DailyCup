# Admin Dashboard Real Data Implementation

## Overview
Admin Dashboard telah diupdate untuk menggunakan data real dari database MySQL, menggantikan data mock/hardcoded sebelumnya.

## Backend API Endpoints

### 1. Dashboard Statistics
**Endpoint:** `GET /api/admin/get_dashboard_stats.php`

**Authentication:** Requires admin JWT token

**Response:**
```json
{
  "success": true,
  "data": {
    "totalRevenue": 150000,
    "totalOrders": 5,
    "pendingOrders": 2,
    "newCustomers": 1,
    "revenueTrend": 15.5,
    "ordersTrend": 8.2
  }
}
```

**Data Sources:**
- `totalRevenue`: Sum of all paid orders from `orders` table
- `totalOrders`: Count of all orders
- `pendingOrders`: Count of orders with status = 'pending'
- `newCustomers`: Count of customers registered in last 30 days
- `revenueTrend`: Percentage change comparing last 30 days vs previous 30 days
- `ordersTrend`: Percentage change in order count

### 2. Recent Orders
**Endpoint:** `GET /api/admin/get_recent_orders.php?limit=10`

**Authentication:** Requires admin JWT token

**Query Parameters:**
- `limit` (optional): Number of orders to return (default: 10, max: 50)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "ORD-1769251398-3210",
      "customer": "John Doe",
      "email": "john@example.com",
      "total": 50000,
      "status": "pending",
      "items": 2,
      "date": "2026-01-24 11:03:01"
    }
  ]
}
```

**Data Sources:**
- Joins `orders` table with `users` table for customer details
- Counts items from `order_items` table
- Orders by `created_at` DESC

### 3. Top Selling Products
**Endpoint:** `GET /api/admin/get_top_products.php?limit=5`

**Authentication:** Requires admin JWT token

**Query Parameters:**
- `limit` (optional): Number of products to return (default: 5, max: 20)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Caramel Macchiato",
      "price": 45000,
      "image": "/products/prod_123.jpg",
      "sold": 120,
      "revenue": 5400000
    }
  ]
}
```

**Data Sources:**
- Aggregates sales from `order_items` table
- Only includes paid orders
- Groups by product and sorts by total quantity sold

## Frontend Implementation

### Dashboard Page
**Location:** `frontend/app/admin/(panel)/dashboard/page.tsx`

**Features:**
1. **Real-time Data Fetching**
   - Fetches all dashboard data on component mount
   - Uses parallel API calls for better performance
   - Shows loading state while fetching

2. **Statistics Cards**
   - Total Revenue with trend indicator
   - Total Orders with trend indicator
   - Pending Orders count
   - New Customers (last 30 days)

3. **Recent Orders Table**
   - Displays last 10 orders
   - Shows customer name, items count, date, status, and total
   - Color-coded status badges
   - Formatted Indonesian currency and dates

4. **Top Selling Products**
   - Shows top 5 best-selling products
   - Displays product image (or emoji fallback)
   - Shows total sales count and revenue

5. **Error Handling**
   - Displays error message if API fails
   - Provides retry button
   - Falls back gracefully if no data

## Security Features

### Authentication & Authorization
- All endpoints require valid JWT token
- Admin role validation (`role = 'admin'`)
- Returns 401 if not authenticated
- Returns 403 if not admin

### CORS & Headers
- CORS enabled for localhost:3000
- Security headers configured in `.htaccess`:
  - X-Content-Type-Options: nosniff
  - X-Frame-Options: DENY
  - X-XSS-Protection: 1; mode=block

## Database Schema Dependencies

### Required Tables
1. **orders**
   - `id`, `order_id`, `user_id`, `total`, `status`, `created_at`

2. **order_items**
   - `id`, `order_id`, `product_id`, `quantity`, `price`

3. **products**
   - `id`, `name`, `base_price`, `image`

4. **users**
   - `id`, `name`, `email`, `role`, `created_at`

## Testing

### Prerequisites
1. Laragon running (Apache & MySQL)
2. Database `dailycup_db` configured
3. Admin user created with role='admin'
4. Some test orders in database

### Manual Testing Steps
1. Login as admin at `/admin/login`
2. Navigate to `/admin/dashboard`
3. Verify statistics match database data
4. Check recent orders display correctly
5. Confirm top products show real sales data

### Test Admin Login
```
Email: admin@dailycup.com
Password: admin123
```

## Usage Example

### Client-Side API Call
```typescript
const fetchDashboardStats = async () => {
  const token = getAuthToken(); // From localStorage
  
  const response = await fetch(
    'http://localhost/DailyCup/webapp/backend/api/admin/get_dashboard_stats.php',
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    }
  );
  
  const data = await response.json();
  if (data.success) {
    setStats(data.data);
  }
};
```

## Performance Optimization

### Backend
- Efficient SQL queries using aggregations
- Limited result sets (max 50 orders, max 20 products)
- Proper indexes on foreign keys

### Frontend
- Parallel API calls using `Promise.all()`
- Single data fetch on mount
- Loading states for better UX
- Error boundaries for failed requests

## Future Enhancements

### Potential Improvements
1. Real-time updates using WebSocket
2. Date range filtering for statistics
3. Export data to CSV/Excel
4. Charts and graphs for visual analytics
5. Caching with Redis
6. Pagination for large datasets
7. More detailed analytics (sales by category, time-based trends)

## Troubleshooting

### Common Issues

**1. "Authentication required" error**
- Make sure you're logged in as admin
- Check if JWT token is valid
- Verify token is sent in Authorization header

**2. "Admin access required" error**
- User role must be 'admin' in database
- Check `users.role` column value

**3. Empty data on dashboard**
- Verify database has orders and products
- Check order status (revenue only counts 'paid' orders)
- Ensure foreign keys are correct

**4. CORS errors**
- Verify `.htaccess` is in admin folder
- Check Apache mod_headers is enabled
- Confirm API_URL matches in frontend .env

## Changelog

### Version 1.0 (2026-01-24)
- ✅ Created backend API endpoints
- ✅ Updated frontend to use real data
- ✅ Added loading and error states
- ✅ Implemented security validation
- ✅ Added Indonesian currency formatting
- ✅ Optimized parallel API calls

---

**Status:** ✅ COMPLETED
**Priority:** Low/Easy
**Phase:** 11 (Real Backend Integration)
