# Product Recommendations System - Implementation Guide

## Overview
Product Recommendations System menggunakan algoritma berbasis data real untuk memberikan rekomendasi produk yang personal dan relevan kepada customer.

## Features Implemented

### 1. Backend API (`/webapp/backend/api/recommendations.php`)

#### Endpoint
```
GET /webapp/backend/api/recommendations.php
```

#### Parameters
- `type` (required): Type of recommendation
  - `related` - Products in same category or frequently bought together
  - `personalized` - Based on user's purchase history
  - `trending` - Best selling products in last 30 days
  - `cart` - Complementary products based on cart items
  
- `product_id` (optional): Product ID for related/similar products
- `user_id` (optional): User ID for personalized recommendations
- `cart_items` (optional): JSON array of cart items `[{"product_id": 1}, {"product_id": 2}]`
- `limit` (optional): Number of recommendations to return (default: 8)

#### Authentication
No authentication required (public endpoint)

#### Response Structure
```json
{
  "success": true,
  "type": "personalized",
  "count": 8,
  "recommendations": [
    {
      "id": 1,
      "name": "Cappuccino",
      "description": "Classic Italian coffee with steamed milk",
      "price": 35000,
      "category": "Coffee",
      "image": "/assets/images/products/cappuccino.jpg",
      "stock": 50,
      "avg_rating": 4.5,
      "review_count": 125,
      "reason": "Based on your preferences"
    }
  ]
}
```

### 2. Recommendation Algorithms

#### A. Related Products (`type=related`)
**Use Case**: Product detail pages, "Similar Products"

**Algorithm**:
1. Get product category
2. Find products in same category
3. Exclude current product
4. Order by purchase count and rating
5. Return top results

**Data Sources**:
- `products` table
- `order_items` table (purchase history)
- `reviews` table (ratings)

**SQL Logic**:
```sql
SELECT products in same category
WHERE category = current_product_category
  AND id != current_product_id
  AND status = 'active'
  AND stock > 0
ORDER BY purchase_count DESC, avg_rating DESC
```

#### B. Personalized Recommendations (`type=personalized`)
**Use Case**: Homepage, customer dashboard, menu page

**Algorithm**:
1. Get user's purchase history
2. Find top 3 frequently bought categories
3. Recommend products from those categories
4. Exclude already purchased products
5. Order by popularity and rating

**Data Sources**:
- `orders` table (user purchase history)
- `order_items` table
- `products` table
- `reviews` table

**SQL Logic**:
```sql
-- Get user's favorite categories
SELECT category, COUNT(*) 
FROM orders + order_items + products
WHERE user_id = X AND order status = 'completed'
GROUP BY category
ORDER BY count DESC LIMIT 3

-- Get products from favorite categories
SELECT products FROM favorite_categories
WHERE product NOT IN (user's purchased products)
  AND status = 'active'
  AND stock > 0
ORDER BY total_orders DESC, avg_rating DESC
```

**Fallback**: If user has no purchase history, show trending products

#### C. Trending Products (`type=trending`)
**Use Case**: Homepage featured section, new users

**Algorithm**:
1. Get products with sales in last 30 days
2. Calculate total quantity sold
3. Order by sales volume and rating
4. Return top sellers

**Data Sources**:
- `products` table
- `order_items` table (last 30 days)
- `orders` table (completed only)
- `reviews` table

**SQL Logic**:
```sql
SELECT products
JOIN order_items ON products.id = order_items.product_id
JOIN orders ON order_items.order_id = orders.id
WHERE orders.status = 'completed'
  AND orders.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND products.status = 'active'
  AND products.stock > 0
GROUP BY product_id
ORDER BY SUM(quantity) DESC, AVG(rating) DESC
```

#### D. Cart Recommendations (`type=cart`)
**Use Case**: Cart page, "Complete Your Order"

**Algorithm**:
1. Get product IDs from cart
2. Find orders containing those products
3. Find other products in those orders (frequently bought together)
4. Exclude cart items
5. Order by co-occurrence frequency

**Data Sources**:
- `order_items` table (collaborative filtering)
- `products` table
- `reviews` table

**SQL Logic**:
```sql
-- Find products frequently bought with cart items
SELECT products
FROM order_items
WHERE order_id IN (
  SELECT order_id FROM order_items 
  WHERE product_id IN (cart_product_ids)
)
AND product_id NOT IN (cart_product_ids)
AND status = 'active'
AND stock > 0
GROUP BY product_id
ORDER BY COUNT(DISTINCT order_id) DESC, avg_rating DESC
```

### 3. Frontend Integration

#### A. React Component (`components/ProductRecommendations.tsx`)
**Features**:
- Reusable component for Next.js pages
- Loading skeleton
- Responsive grid layout
- Rating display
- Stock indicators
- Reason badges

**Usage**:
```tsx
import ProductRecommendations from '@/components/ProductRecommendations';

// Related products
<ProductRecommendations 
  type="related" 
  productId={123}
  limit={4}
  title="Related Products"
/>

// Personalized
<ProductRecommendations 
  type="personalized"
  title="Recommended For You"
/>

// Cart recommendations
<ProductRecommendations 
  type="cart"
  cartItems={[{product_id: 1}, {product_id: 2}]}
  title="Complete Your Order"
/>
```

#### B. PHP Pages Integration

**Cart Page (`customer/cart.php`)**:
- Shows "You May Also Like" section
- Uses `cart` type recommendations
- Fallback to `trending` if cart is empty
- Quick add to cart functionality
- Real-time cart updates

**Menu Page (`customer/menu.php`)**:
- Shows "Recommended For You" or "Trending Now"
- Uses `personalized` type for logged-in users
- Uses `trending` type for guests
- Loading skeleton while fetching
- Add to cart + View details buttons

### 4. Data Flow

```
User Visit Page
    ↓
JavaScript Fetch API Call
    ↓
PHP Backend (recommendations.php)
    ↓
Database Queries (Real Data)
    ├── products
    ├── orders
    ├── order_items
    └── reviews
    ↓
Algorithm Processing
    ├── Filter by type
    ├── Calculate scores
    ├── Order by relevance
    └── Limit results
    ↓
JSON Response
    ↓
Frontend Display
    ├── Product cards
    ├── Ratings
    ├── Prices
    ├── Stock status
    └── Add to cart
```

## Installation

### 1. Backend Files
Already created:
- `/webapp/backend/api/recommendations.php` - Main API

### 2. Frontend Files
Already created:
- `/webapp/frontend/components/ProductRecommendations.tsx` - React component
- `/customer/cart.php` - Updated with recommendations
- `/customer/menu.php` - Updated with recommendations

### 3. Database
No additional tables needed. Uses existing:
- `products`
- `orders`
- `order_items`
- `reviews`

## Usage Examples

### 1. Get Related Products
```javascript
fetch('/webapp/backend/api/recommendations.php?type=related&product_id=5&limit=4')
  .then(res => res.json())
  .then(data => console.log(data.recommendations));
```

### 2. Get Personalized Recommendations
```javascript
const userId = 123;
fetch(`/webapp/backend/api/recommendations.php?type=personalized&user_id=${userId}&limit=8`)
  .then(res => res.json())
  .then(data => console.log(data.recommendations));
```

### 3. Get Trending Products
```javascript
fetch('/webapp/backend/api/recommendations.php?type=trending&limit=6')
  .then(res => res.json())
  .then(data => console.log(data.recommendations));
```

### 4. Get Cart Recommendations
```javascript
const cartItems = [{product_id: 1}, {product_id: 3}];
fetch(`/webapp/backend/api/recommendations.php?type=cart&cart_items=${JSON.stringify(cartItems)}&limit=4`)
  .then(res => res.json())
  .then(data => console.log(data.recommendations));
```

## Features Highlights

### ✅ Real Data Based
- **NO hardcoded/sample data**
- All recommendations calculated from actual database
- Real-time purchase history analysis
- Actual user behavior patterns

### ✅ Multiple Algorithms
- **Collaborative filtering** - "Customers who bought X also bought Y"
- **Category-based** - Products in same category
- **Popularity-based** - Trending/best sellers
- **User history-based** - Personal preferences

### ✅ Smart Fallbacks
- New users → Trending products
- Empty cart → Trending products
- No related products → Category products
- No purchase history → Popular items

### ✅ Performance Optimized
- Single API call per page
- Efficient SQL queries with JOINs
- Limited result sets (default 8 items)
- Indexed database columns

### ✅ User Experience
- Loading skeletons
- Responsive design
- Quick add to cart
- Rating displays
- Stock indicators
- Reason badges ("Trending", "Based on preferences")

## Technical Details

### SQL Optimization
- Uses prepared statements (security)
- Efficient JOINs (performance)
- Indexed columns (speed)
- Limited result sets (memory)

### Scoring Logic
Products scored by:
1. **Purchase Count** - How many times sold
2. **Average Rating** - Customer satisfaction
3. **Review Count** - Social proof
4. **Category Match** - Relevance
5. **Stock Availability** - Only in-stock items

### Security
- No authentication required (public data)
- SQL injection prevention (prepared statements)
- XSS protection (JSON encoding)
- Input validation (parameter checking)

## Testing

### Test Related Products
```
http://localhost/DailyCup/webapp/backend/api/recommendations.php?type=related&product_id=1&limit=4
```

### Test Personalized
```
http://localhost/DailyCup/webapp/backend/api/recommendations.php?type=personalized&user_id=1&limit=8
```

### Test Trending
```
http://localhost/DailyCup/webapp/backend/api/recommendations.php?type=trending&limit=6
```

### Test Cart
```
http://localhost/DailyCup/webapp/backend/api/recommendations.php?type=cart&cart_items=[{"product_id":1},{"product_id":2}]&limit=4
```

## Future Enhancements
- Machine learning integration
- A/B testing different algorithms
- Click tracking and conversion analytics
- Real-time personalization
- Image-based similarity
- Price range filtering
- Time-based recommendations (breakfast, lunch, dinner)
- Seasonal product suggestions
- New product discovery algorithm

## Files Modified/Created

### Created:
- `/webapp/backend/api/recommendations.php` - Main recommendation API
- `/webapp/frontend/components/ProductRecommendations.tsx` - React component

### Modified:
- `/customer/cart.php` - Added "You May Also Like" section
- `/customer/menu.php` - Added "Recommended For You" section

## Summary
Product Recommendations System telah berhasil diimplementasikan dengan:
✅ 4 recommendation algorithms (related, personalized, trending, cart)
✅ Real data from 4 database tables
✅ React component + PHP integration
✅ Smart fallback mechanisms
✅ Performance optimized queries
✅ Responsive UI with loading states
✅ Quick add to cart functionality
✅ Rating & stock indicators
✅ NO hardcoded/sample data
