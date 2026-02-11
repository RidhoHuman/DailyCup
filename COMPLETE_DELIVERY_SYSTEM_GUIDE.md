# DailyCup Delivery Management System - Complete Implementation Guide

## 📦 System Overview

DailyCup Delivery Management System adalah implementasi lengkap sistem pengiriman dan pembayaran COD (Cash on Delivery) yang aman dan fleksibel, mirip dengan GrabFood, GoFood, dan ShopeeFood.

### Key Features
✅ **COD Payment System** dengan trust score dan fraud protection  
✅ **Manual Admin Approval** untuk COD orders  
✅ **Real-time Delivery Tracking** dengan 5 status stages  
✅ **Kurir Management** dengan GPS tracking  
✅ **Photo Proof Delivery** untuk accountability  
✅ **Auto-Assign Kurir** based on availability  
✅ **Trust Score System** untuk customer reputation  
✅ **Auto-Cancel Expired Orders** dengan MySQL Event  

---

## 🏗️ Architecture

### Backend (PHP 8.2 + MySQL 8.0)
```
webapp/backend/api/
├── validate_cod.php                  # COD eligibility validation
├── admin_confirm_cod.php             # Admin approval/rejection
├── kurir_update_delivery_status.php  # Kurir status updates
├── get_pending_cod_orders.php        # COD orders awaiting approval
├── get_delivery_tracking.php         # Real-time delivery tracking
├── get_kurir_list.php                # Kurir roster with stats
├── manual_assign_kurir.php           # Admin kurir assignment
├── get_order_detail.php              # Complete order information
└── get_delivery_stats.php            # Dashboard statistics
```

### Frontend (Next.js 14 + TypeScript + Tailwind CSS)
```
webapp/frontend/
├── types/delivery.ts                 # TypeScript type definitions
├── components/admin/
│   ├── OrderStatusBadge.tsx          # Status badge component
│   └── DeliveryTimeline.tsx          # Timeline component
└── app/admin/
    ├── orders/cod/page.tsx           # COD approval panel
    ├── deliveries/page.tsx           # Delivery monitoring
    ├── kurir/page.tsx                # Kurir management
    └── orders/[id]/page.tsx          # Order detail page
```

### Database Schema
```sql
-- New Tables
- trust_score                 # Customer trust score tracking
- cod_validation_rules        # Dynamic COD rules
- order_status_logs           # Order status history
- user_fraud_logs             # Fraud detection logs
- delivery_history            # Delivery timeline with photos
- kurir_notifications         # Kurir-specific notifications

-- Modified Tables
- orders                      # Added COD fields, kurir_id, trust_score
- kurir                       # Added vehicle info, location tracking
- kurir_location              # Real-time GPS coordinates
```

---

## 🚀 Getting Started

### 1. Database Setup

```bash
# Execute database migration
mysql -u root -p dailycup_db < database/upgrade_cod_system.sql

# Verify MySQL Event Scheduler is enabled
mysql -u root -p -e "SET GLOBAL event_scheduler = ON;"
```

**Important Tables:**
- `trust_score`: Tracks customer reputation (0-100 scale)
- `cod_validation_rules`: Default COD limits and restrictions
- `order_status_logs`: Complete order history
- `delivery_history`: Kurir delivery progress with photos

### 2. Backend Configuration

Ensure `config/database.php` is properly configured:
```php
<?php
class Database {
    private static $host = 'localhost';
    private static $db = 'dailycup_db';
    private static $user = 'root';
    private static $pass = '';
    
    public static function getConnection() {
        $conn = new mysqli(self::$host, self::$user, self::$pass, self::$db);
        if ($conn->connect_error) {
            throw new Exception("Connection failed");
        }
        return $conn;
    }
}
```

### 3. Frontend Setup

```bash
cd webapp/frontend

# Install dependencies
npm install date-fns  # For date formatting

# Start development server
npm run dev
```

**API Configuration** (`lib/api-client.ts`):
```typescript
const API_BASE_URL = 'http://localhost/DailyCup/webapp/backend/api';

export const api = {
  get: async (endpoint: string) => {
    const response = await fetch(`${API_BASE_URL}${endpoint}`);
    return await response.json();
  },
  post: async (endpoint: string, data: any) => {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    return await response.json();
  }
};
```

---

## 📱 User Flow

### Customer Order Flow

1. **Customer creates order** → `POST /create_order.php`
   - Selects COD as payment method
   - System validates eligibility via `validate_cod.php`
   - Order created with status `pending_cod_approval`

2. **COD Validation** → `GET /validate_cod.php?user_id=X`
   - Checks trust score (min: 10)
   - Validates max amount (50K new user, 100K verified)
   - Checks recent cancellations (<3 in 7 days)
   - Verifies blacklist status
   - Checks delivery distance (<5KM from outlet)

3. **Admin Approval** → `/admin/orders/cod`
   - Admin reviews pending COD orders
   - Risk analysis: Low/Medium/High
   - Actions: Approve, Reject, Mark as Fraud

4. **Kurir Assignment** → Auto or Manual
   - **Auto**: System assigns nearest available kurir
   - **Manual**: Admin assigns via `/admin/orders/[id]`

5. **Delivery Tracking** → Customer receives updates
   - going_to_store → arrived_at_store → picked_up → nearby → delivered
   - Real-time GPS tracking available
   - Photo proof at each stage

6. **Completion**
   - Kurir uploads delivery photo
   - Customer trust score +10
   - Payment status updated to `paid`

### Admin Flow

```
┌──────────────────────┐
│   COD Approval Panel │  /admin/orders/cod
└──────────────────────┘
         │
         ├─→ View risk analysis
         ├─→ Approve COD order
         ├─→ Reject with reason
         └─→ Mark as fraud (blacklist)
         
┌──────────────────────┐
│ Delivery Monitoring  │  /admin/deliveries
└──────────────────────┘
         │
         ├─→ View all active deliveries
         ├─→ Filter by status
         ├─→ Check progress bars
         ├─→ View kurir location
         └─→ Drill down to order detail
         
┌──────────────────────┐
│  Kurir Management    │  /admin/kurir
└──────────────────────┘
         │
         ├─→ View all kurirs
         ├─→ Check availability
         ├─→ Monitor location freshness
         ├─→ View today's earnings
         └─→ Assign to orders
```

---

## 🔧 API Reference

### 1. Validate COD Eligibility

**Endpoint:** `GET /validate_cod.php?user_id={id}`

**Response:**
```json
{
  "success": true,
  "eligible": true,
  "reasons": [],
  "user_info": {
    "trust_score": 40,
    "total_successful_orders": 5,
    "is_verified_user": true,
    "recent_cancellations": 0
  },
  "limits": {
    "max_amount": 100000,
    "max_distance": 5.00,
    "min_trust_score": 10
  }
}
```

### 2. Admin Confirm COD

**Endpoint:** `POST /admin_confirm_cod.php`

**Payload:**
```json
{
  "order_id": 123,
  "action": "approve",  // or "reject"
  "notes": "Order approved",
  "is_fraud": false,
  "admin_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order approved successfully",
  "kurir_assigned": true,
  "kurir_id": 5
}
```

### 3. Kurir Update Delivery Status

**Endpoint:** `POST /kurir_update_delivery_status.php`

**Payload:**
```json
{
  "order_id": 123,
  "kurir_id": 5,
  "status": "picked_up",
  "photo": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "notes": "Coffee packed and ready"
}
```

**Status Flow:**
- `going_to_store` → Kurir menuju outlet
- `arrived_at_store` → Kurir tiba di outlet
- `picked_up` → Pesanan diambil (requires photo)
- `nearby` → Kurir dekat customer (auto if <500m)
- `delivered` → Pesanan diterima (requires photo)

### 4. Get Pending COD Orders

**Endpoint:** `GET /get_pending_cod_orders.php?limit=50`

**Response:**
```json
{
  "success": true,
  "orders": [
    {
      "id": 123,
      "order_number": "DC20240115001",
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "customer_phone": "08123456789",
      "final_amount": 75000,
      "trust_score": 40,
      "recent_cancellations": 0,
      "total_successful_orders": 5,
      "delivery_distance": 2.5,
      "risk_level": "low",
      "is_expiring_soon": false,
      "minutes_remaining": 25,
      "created_at": "2024-01-15 10:30:00"
    }
  ],
  "total": 1
}
```

**Risk Calculation:**
- **Low**: trust_score ≥ 50 AND cancellations < 2
- **High**: trust_score < 20 OR cancellations ≥ 3
- **Medium**: Everything else

### 5. Get Delivery Tracking

**Endpoint:** `GET /get_delivery_tracking.php?status=processing`

**Response:**
```json
{
  "success": true,
  "deliveries": [
    {
      "id": 123,
      "order_number": "DC20240115001",
      "status": "picked_up",
      "customer_name": "John Doe",
      "final_amount": 75000,
      "kurir_id": 5,
      "kurir_name": "Budi Santoso",
      "kurir_phone": "08234567890",
      "vehicle_type": "motor",
      "progress_percentage": 60,
      "has_delay_warning": false,
      "delay_reason": null,
      "latitude": -6.2088,
      "longitude": 106.8456,
      "location_updated_at": "2024-01-15 11:05:00",
      "created_at": "2024-01-15 10:30:00"
    }
  ],
  "stats": {
    "total_active": 15,
    "confirmed": 3,
    "processing": 7,
    "ready": 2,
    "delivering": 3
  }
}
```

**Progress Calculation:**
- `confirmed` → 20%
- `processing` OR `going_to_store` → 40%
- `arrived_at_store` → 50%
- `picked_up` → 60%
- `nearby` → 80%
- `delivered` → 100%

### 6. Get Kurir List

**Endpoint:** `GET /get_kurir_list.php?status=available`

**Response:**
```json
{
  "success": true,
  "kurirs": [
    {
      "id": 5,
      "name": "Budi Santoso",
      "phone": "08234567890",
      "status": "available",
      "is_active": 1,
      "vehicle_type": "motor",
      "vehicle_number": "B 1234 XYZ",
      "rating": 4.8,
      "total_deliveries": 150,
      "today_deliveries": 8,
      "today_earnings": 120000,
      "active_deliveries": 0,
      "latitude": -6.2088,
      "longitude": 106.8456,
      "location_updated_at": "2024-01-15 11:10:00",
      "location_is_fresh": true,
      "is_available": true
    }
  ],
  "stats": {
    "total_kurirs": 10,
    "available": 5,
    "busy": 3,
    "offline": 2
  }
}
```

**Location Freshness:**
- Fresh: Updated within last 5 minutes
- Stale: Older than 5 minutes

### 7. Manual Assign Kurir

**Endpoint:** `POST /manual_assign_kurir.php`

**Payload:**
```json
{
  "order_id": 123,
  "kurir_id": 5,
  "notes": "Nearest kurir to customer location",
  "admin_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Kurir assigned successfully",
  "kurir_name": "Budi Santoso",
  "notification_sent": true
}
```

### 8. Get Order Detail

**Endpoint:** `GET /get_order_detail.php?order_id=123`

**Response:**
```json
{
  "success": true,
  "order": {
    "id": 123,
    "order_number": "DC20240115001",
    "user_id": 10,
    "customer_name": "John Doe",
    "customer_email": "john@example.com",
    "customer_phone": "08123456789",
    "status": "picked_up",
    "payment_method": "cod",
    "payment_status": "pending",
    "final_amount": 75000,
    "delivery_address": "Jl. Example No. 123",
    "trust_score": 40,
    "is_verified_user": true,
    "total_successful_orders": 5,
    "kurir_id": 5,
    "kurir_name": "Budi Santoso",
    "kurir_phone": "08234567890",
    "vehicle_type": "motor",
    "created_at": "2024-01-15 10:30:00"
  },
  "items": [
    {
      "id": 456,
      "product_id": 20,
      "product_name": "Cappuccino",
      "size": "Grande",
      "temperature": "Hot",
      "quantity": 2,
      "price": 35000,
      "subtotal": 70000,
      "notes": "Extra foam",
      "addons": "[{\"id\":5,\"name\":\"Extra Shot\",\"price\":5000}]",
      "addons_parsed": [
        {"id": 5, "name": "Extra Shot", "price": 5000}
      ]
    }
  ],
  "history": [
    {
      "id": 100,
      "order_id": 123,
      "status": "pending_cod_approval",
      "changed_by": 10,
      "changed_by_name": "John Doe",
      "notes": "Order created",
      "created_at": "2024-01-15 10:30:00"
    }
  ],
  "delivery_history": [
    {
      "id": 200,
      "order_id": 123,
      "kurir_id": 5,
      "status": "picked_up",
      "photo_path": "/uploads/delivery_photos/123_picked_up.jpg",
      "notes": "Coffee packed",
      "created_at": "2024-01-15 10:50:00"
    }
  ],
  "kurir_location": {
    "latitude": -6.2088,
    "longitude": 106.8456,
    "updated_at": "2024-01-15 11:10:00"
  }
}
```

### 9. Get Delivery Stats

**Endpoint:** `GET /get_delivery_stats.php?period=today`

**Periods:** `today`, `week`, `month`

**Response:**
```json
{
  "success": true,
  "overall_stats": {
    "total_orders": 45,
    "pending_cod_approval": 5,
    "confirmed": 8,
    "processing": 12,
    "ready": 3,
    "delivering": 7,
    "delivered": 10,
    "total_revenue": 3500000,
    "cod_revenue": 2100000
  },
  "kurir_stats": {
    "total_kurirs": 10,
    "available": 5,
    "busy": 3,
    "offline": 2
  },
  "top_performers": [
    {
      "kurir_id": 5,
      "kurir_name": "Budi Santoso",
      "total_deliveries": 15,
      "total_earnings": 450000
    }
  ],
  "performance_metrics": {
    "avg_pickup_time": 12.5,
    "avg_delivery_time": 28.3
  },
  "hourly_distribution": [
    {"hour": "08", "count": 5},
    {"hour": "09", "count": 12}
  ]
}
```

---

## 🎨 Frontend Components

### OrderStatusBadge Component

**Usage:**
```tsx
import OrderStatusBadge from '@/components/admin/OrderStatusBadge';

<OrderStatusBadge status="picked_up" size="md" showIcon />
```

**Props:**
- `status`: OrderStatus enum
- `size`: 'sm' | 'md' | 'lg' (default: 'md')
- `showIcon`: boolean (default: true)

### DeliveryTimeline Component

**Usage:**
```tsx
import DeliveryTimeline from '@/components/admin/DeliveryTimeline';

<DeliveryTimeline 
  history={orderStatusLogs} 
  currentStatus="picked_up" 
/>
```

**Props:**
- `history`: OrderStatusLog[]
- `currentStatus`: OrderStatus

---

## 🔐 Security Features

### 1. Trust Score System
- Range: 0-100
- Default: 10 for new users
- +10 on successful delivery
- -20 on cancellation or rejection
- Triggers at <5 for account review

### 2. Fraud Detection
- Recent cancellation tracking (7 days)
- Blacklist system with permanent ban
- Admin manual review for suspicious activity
- Logged in `user_fraud_logs` table

### 3. COD Validation Rules
- Dynamic limits based on trust score
- Verified users get higher limits
- Distance restriction (<5KM)
- 30-minute order expiry

### 4. Auto-Cancel System
```sql
-- MySQL Event runs every 5 minutes
CREATE EVENT auto_cancel_expired_cod
ON SCHEDULE EVERY 5 MINUTE
DO
  UPDATE orders 
  SET status = 'cancelled', 
      cancellation_reason = 'COD order expired (not approved within 30 minutes)'
  WHERE status = 'pending_cod_approval' 
    AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > 30;
```

---

## 📊 Admin Dashboard Pages

### 1. COD Approval Panel (`/admin/orders/cod`)

**Features:**
- List of pending COD orders
- Risk analysis badges (Low/Medium/High)
- Expiring soon warnings (<10 minutes)
- Quick approve/reject buttons
- Fraud marking with blacklist
- Auto-refresh every 30 seconds

**Risk Indicators:**
- 🟢 Low: Trust score ≥50, <2 cancellations
- 🟡 Medium: Trust score 20-49, 2 cancellations
- 🔴 High: Trust score <20 OR ≥3 cancellations

### 2. Delivery Monitoring (`/admin/deliveries`)

**Features:**
- Real-time tracking of all active deliveries
- Status filter dropdown
- Progress bars for each delivery
- Kurir information cards
- Delay warnings
- Auto-refresh every 10 seconds

**Delay Warnings:**
- Processing >30 minutes → ⚠️ Slow pickup
- Delivering >45 minutes → 🚨 Delayed delivery

### 3. Kurir Management (`/admin/kurir`)

**Features:**
- Kurir roster with status badges
- Availability indicators (Available/Busy/Offline)
- Today's deliveries and earnings
- GPS location freshness status
- View on Google Maps
- Rating and performance stats
- Auto-refresh every 15 seconds

### 4. Order Detail (`/admin/orders/[id]`)

**Features:**
- Complete order information
- Customer trust score and verification status
- Order items with addons
- DeliveryTimeline component
- Kurir assignment
- GPS location tracking
- Payment status
- Manual kurir reassignment

---

## 🧪 Testing Guide

### Backend API Testing

**Test validate_cod.php:**
```bash
curl "http://localhost/DailyCup/webapp/backend/api/validate_cod.php?user_id=1"
```

**Expected Response:**
```json
{
  "success": true,
  "eligible": true,
  "user_info": {
    "trust_score": 40,
    "is_verified_user": true
  }
}
```

**Test admin_confirm_cod.php:**
```bash
curl -X POST http://localhost/DailyCup/webapp/backend/api/admin_confirm_cod.php \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "action": "approve",
    "admin_id": 1
  }'
```

### Frontend Integration Testing

1. **COD Approval Flow:**
   - Navigate to `/admin/orders/cod`
   - Verify pending orders load
   - Click "Approve" on an order
   - Check order status changes to `confirmed`
   - Verify kurir auto-assigned

2. **Delivery Monitoring:**
   - Navigate to `/admin/deliveries`
   - Verify real-time updates (10s interval)
   - Test status filters
   - Click on order to view details

3. **Kurir Management:**
   - Navigate to `/admin/kurir`
   - Verify kurir list with stats
   - Check location indicators
   - Test "View on Map" button

### Database Validation

```sql
-- Check trust score triggers
SELECT * FROM trust_score WHERE user_id = 1;

-- Verify order status logs
SELECT * FROM order_status_logs WHERE order_id = 123 ORDER BY created_at DESC;

-- Check delivery history with photos
SELECT * FROM delivery_history WHERE order_id = 123;

-- Verify auto-cancel event is running
SHOW EVENTS WHERE name = 'auto_cancel_expired_cod';
```

---

## 🚨 Troubleshooting

### Issue: "COD validation failed - trust score too low"
**Solution:** Check `trust_score` table for user. Minimum required is 10.
```sql
UPDATE trust_score SET score = 20 WHERE user_id = 1;
```

### Issue: "No kurir available for auto-assignment"
**Solution:** Ensure kurirs have `status='available'` and `is_active=1`
```sql
UPDATE kurir SET status = 'available', is_active = 1 WHERE id = 5;
```

### Issue: "Order expired before approval"
**Solution:** Check MySQL Event Scheduler is running
```sql
SET GLOBAL event_scheduler = ON;
SHOW PROCESSLIST;  -- Should show "Event Scheduler" process
```

### Issue: "Location freshness indicator always red"
**Solution:** Update kurir location within 5 minutes
```sql
UPDATE kurir_location 
SET latitude = -6.2088, longitude = 106.8456, updated_at = NOW() 
WHERE kurir_id = 5;
```

### Issue: "Frontend not loading data"
**Solution:** 
1. Check CORS headers in backend APIs
2. Verify `API_BASE_URL` in `api-client.ts`
3. Open browser console for errors
4. Test API directly with curl

---

## 📈 Performance Optimization

### Backend Optimizations
- Use prepared statements for all queries
- Add indexes on frequently queried columns
- Cache validation rules in memory
- Batch notification sending

### Frontend Optimizations
- Implement debouncing on auto-refresh
- Use React Query for caching
- Lazy load order detail page
- Optimize re-renders with useMemo/useCallback

### Database Indexes
```sql
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user_payment ON orders(user_id, payment_method);
CREATE INDEX idx_kurir_status ON kurir(status, is_active);
CREATE INDEX idx_trust_score_user ON trust_score(user_id);
CREATE INDEX idx_order_logs_order ON order_status_logs(order_id, created_at);
```

---

## 🔮 Future Enhancements

### Phase 3 (Optional)
- [ ] WebSocket/SSE for real-time updates (replace polling)
- [ ] Google Maps integration for GPS tracking
- [ ] Push notifications for kurir and customers
- [ ] Machine learning for fraud detection
- [ ] Route optimization for kurir
- [ ] Heatmap of delivery zones
- [ ] Customer feedback and ratings
- [ ] Automated trust score adjustment

### Phase 4 (Advanced)
- [ ] Multi-outlet support
- [ ] Shift management for kurirs
- [ ] Earnings dashboard for kurirs
- [ ] Advanced analytics and reporting
- [ ] A/B testing for COD limits
- [ ] Integration with external payment gateways

---

## 📝 Maintenance Checklist

### Daily
- [ ] Monitor pending COD orders (should not exceed 30 minutes)
- [ ] Check kurir availability
- [ ] Review fraud logs
- [ ] Monitor delivery delays

### Weekly
- [ ] Analyze trust score distribution
- [ ] Review blacklisted users
- [ ] Optimize COD validation rules
- [ ] Check kurirs' performance metrics

### Monthly
- [ ] Database backup
- [ ] Update COD limits based on data
- [ ] Generate performance reports
- [ ] Clean up old delivery photos

---

## 🤝 Support

For issues or questions:
1. Check troubleshooting section
2. Review API responses for error messages
3. Check database logs
4. Test with curl for backend issues
5. Check browser console for frontend errors

---

## 📄 License

Proprietary - DailyCup Internal Use Only

---

**Last Updated:** January 15, 2024  
**Version:** 1.0.0  
**Author:** GitHub Copilot + Development Team
