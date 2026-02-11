# 📦 Order Lifecycle Management System - Phase 2

## ✅ Implementation Complete

### 🎯 Overview
Real-time order tracking system dengan state machine, WebSocket updates, courier management, photo verification, dan COD OTP system.

---

## 🗄️ Database Schema

### Migration File
📁 **`backend/database_order_lifecycle.sql`**

Run migration:
```sql
mysql -u root -p dailycup < backend/database_order_lifecycle.sql
```

### Tables Created
1. **`orders`** - Updated dengan status baru + courier fields
2. **`couriers`** - Driver management (5 sample couriers)
3. **`order_status_log`** - Status change history
4. **`cod_verifications`** - OTP verification records
5. **`order_locations`** - Real-time GPS tracking trail
6. **`order_tracking_summary`** - View untuk tracking overview

### Order Status Flow
```
pending_payment 
  ↓
waiting_confirmation (COD OTP)
  ↓
queueing
  ↓
preparing
  ↓
on_delivery (Courier assigned)
  ↓
completed (Photo uploaded)
  ↓
cancelled (Any stage)
```

---

## 🔌 Backend API Endpoints

### 1. **Update Order Status**
```
POST /api/orders/update_status.php
Authorization: Bearer <JWT>

Body:
{
  "order_id": "ORD-xxx",
  "status": "preparing",
  "courier_id": 1,  // optional
  "message": "Custom message",  // optional
  "metadata": {}  // optional
}

Response:
{
  "success": true,
  "order": {
    "order_id": "ORD-xxx",
    "status": "preparing",
    "updated_at": "2026-02-05 14:30:00"
  }
}
```

**State Machine Validation:**
- ✅ Enforces valid status transitions
- ✅ Admin/courier can update any order
- ✅ Customer can only cancel pending orders

---

### 2. **Get Order Tracking**
```
GET /api/orders/tracking.php?order_id=ORD-xxx
Authorization: Bearer <JWT> (optional)

Response:
{
  "success": true,
  "order": { ... },
  "courier": { ... },
  "items": [ ... ],
  "status_history": [ ... ],
  "location_trail": [ ... ],
  "cod_verification": { ... }
}
```

**Public Access:** Guest users can track orders tanpa login

---

### 3. **Assign Courier**
```
POST /api/orders/assign_courier.php
Authorization: Bearer <JWT> (Admin only)

Body:
{
  "order_id": "ORD-xxx",
  "courier_id": 2
}

Response:
{
  "success": true,
  "courier": {
    "id": 2,
    "name": "Andi Wijaya",
    "phone": "08198765432"
  }
}
```

**Actions:**
- ✅ Assigns courier to order
- ✅ Updates status to `on_delivery`
- ✅ Sets estimated delivery (+30 min)
- ✅ Marks courier as unavailable

---

### 4. **Courier Complete Delivery**
```
POST /api/orders/courier_complete.php
Authorization: Bearer <JWT> (Courier/Admin)
Content-Type: multipart/form-data

Body:
- order_id: "ORD-xxx"
- photo: <file>

Response:
{
  "success": true,
  "photo_url": "/backend/data/courier_photos/delivery_xxx.jpg",
  "completed_at": "2026-02-05 15:00:00"
}
```

**Photo Verification:**
- ✅ Required before marking as completed
- ✅ Max 5MB (JPEG, PNG, WebP)
- ✅ Automatically marks courier available again
- ✅ Updates courier's total_deliveries count

---

### 5. **COD OTP System**

#### Generate OTP
```
POST /api/orders/cod_generate_otp.php
Authorization: Bearer <JWT>

Body:
{
  "order_id": "ORD-xxx"
}

Response (New User):
{
  "success": true,
  "auto_approved": false,
  "simulated_otp": "123456",  // SIMULATED - In production sent via WhatsApp
  "expires_at": "2026-02-05 14:45:00",
  "note": "This is a SIMULATED OTP..."
}

Response (Trusted User - 5+ completed orders):
{
  "success": true,
  "auto_approved": true,
  "message": "Trusted user - Order automatically approved!",
  "order_status": "queueing"
}
```

#### Verify OTP
```
POST /api/orders/cod_verify_otp.php
Authorization: Bearer <JWT>

Body:
{
  "order_id": "ORD-xxx",
  "otp_code": "123456"
}

Response:
{
  "success": true,
  "message": "OTP verified successfully!",
  "order_status": "queueing"
}

Error (Invalid):
{
  "error": "Invalid OTP",
  "remaining_attempts": 3
}
```

**OTP Features:**
- ✅ 6-digit random code
- ✅ 10-minute expiration
- ✅ Max 5 verification attempts
- ✅ Auto-approve trusted users (5+ completed orders)
- ✅ **SIMULATED** - No real WhatsApp API (displayed in console/alert)

---

## 🌐 WebSocket Real-Time Updates

### Server
📁 **`backend/api/orders/websocket_server.php`**

**Start Server:**
```bash
cd backend/api/orders
php websocket_server.php
```

**Requirements:**
```bash
cd backend
composer require cboden/ratchet
```

**Server URL:** `ws://localhost:8080`

### Client Messages

#### Subscribe to Order
```json
{
  "type": "subscribe",
  "order_id": "ORD-xxx"
}
```

#### Unsubscribe
```json
{
  "type": "unsubscribe",
  "order_id": "ORD-xxx"
}
```

#### Receive Updates
```json
{
  "type": "order_update",
  "order_id": "ORD-xxx",
  "data": {
    "status": "on_delivery",
    "courier": { ... },
    "estimated_delivery": "2026-02-05 15:30:00"
  },
  "timestamp": 1738770000
}
```

---

## 💻 Frontend Components

### 1. Admin Kanban Board
📁 **`frontend/app/admin/(panel)/orders/kanban/page.tsx`**

**URL:** `/admin/orders/kanban`

**Features:**
- ✅ 7-column Kanban board (status-based)
- ✅ Drag-and-drop order cards
- ✅ Real-time auto-refresh (10s)
- ✅ Assign courier modal
- ✅ Quick status updates
- ✅ Order count per column

**Access:** Admin only

---

### 2. Customer Order Tracker
📁 **`frontend/app/track/[order_id]/page.tsx`**

**URL:** `/track/ORD-xxx`

**Features:**
- ✅ Real-time progress timeline
- ✅ Status history log
- ✅ Courier information display
- ✅ Order items & total
- ✅ COD OTP verification modal
- ✅ Delivery photo display
- ✅ Auto-refresh (5s)

**Access:** Public (no login required)

---

### 3. WebSocket Client
📁 **`frontend/lib/websocket-client.ts`**

**Usage Example:**
```typescript
import { getOrderTrackingWebSocket } from '@/lib/websocket-client';

const ws = getOrderTrackingWebSocket();
ws.connect();

ws.subscribeToOrder('ORD-xxx');

ws.onOrderUpdate('ORD-xxx', (data) => {
  console.log('Order updated:', data);
  // Update UI
});

// Cleanup
ws.unsubscribeFromOrder('ORD-xxx');
ws.disconnect();
```

**Features:**
- ✅ Auto-reconnect (max 5 attempts)
- ✅ Subscription management
- ✅ Event listeners
- ✅ Singleton pattern

---

## 🧪 Testing Guide

### 1. Setup Database
```sql
mysql -u root -p dailycup < backend/database_order_lifecycle.sql
```

Verify:
- ✅ 5 sample couriers created
- ✅ Orders table has new status enum
- ✅ New tracking tables exist

---

### 2. Test COD OTP Flow

#### New User (OTP Required)
```bash
# 1. Create order with payment_method='cod'
POST /api/create_order.php
{
  "payment_method": "cod",
  ...
}

# 2. Generate OTP (user has < 5 completed orders)
POST /api/orders/cod_generate_otp.php
{
  "order_id": "ORD-xxx"
}

# Response shows simulated OTP: "123456"

# 3. Verify OTP
POST /api/orders/cod_verify_otp.php
{
  "order_id": "ORD-xxx",
  "otp_code": "123456"
}

# Order status → queueing
```

#### Trusted User (Auto-Approved)
```bash
# User with 5+ completed orders
POST /api/orders/cod_generate_otp.php
{
  "order_id": "ORD-xxx"
}

# Response: auto_approved: true
# Order status → queueing (no OTP input needed)
```

---

### 3. Test Order Lifecycle

```bash
# 1. Start order (admin)
POST /api/orders/update_status.php
{ "order_id": "ORD-xxx", "status": "preparing" }

# 2. Assign courier (admin)
POST /api/orders/assign_courier.php
{ "order_id": "ORD-xxx", "courier_id": 1 }
# Status → on_delivery

# 3. Complete delivery (courier)
POST /api/orders/courier_complete.php
Form Data:
- order_id: ORD-xxx
- photo: [file]
# Status → completed
```

---

### 4. Test WebSocket

**Terminal 1 - Start Server:**
```bash
cd backend/api/orders
php websocket_server.php
```

**Terminal 2 - Test Client:**
```javascript
const ws = new WebSocket('ws://localhost:8080');

ws.onopen = () => {
  console.log('Connected');
  
  // Subscribe
  ws.send(JSON.stringify({
    type: 'subscribe',
    order_id: 'ORD-xxx'
  }));
};

ws.onmessage = (event) => {
  console.log('Message:', JSON.parse(event.data));
};
```

**Terminal 3 - Update Order:**
```bash
# Order status change triggers broadcast to all subscribers
curl -X POST http://localhost/DailyCup/webapp/backend/api/orders/update_status.php \
  -H "Authorization: Bearer <token>" \
  -d '{"order_id":"ORD-xxx","status":"preparing"}'
```

---

## 📊 Sample Couriers

| ID | Name | Phone | Vehicle | Location |
|----|------|-------|---------|----------|
| 1 | Budi Santoso | 08123456789 | Motorcycle | Jakarta |
| 2 | Andi Wijaya | 08198765432 | Motorcycle | Jakarta |
| 3 | Siti Nurhaliza | 08567891234 | Car | Jakarta |
| 4 | Rudi Hartono | 08112233445 | Motorcycle | Jakarta (Unavailable) |
| 5 | Dewi Lestari | 08223344556 | Bicycle | Jakarta |

---

## 🚀 Deployment Checklist

### Backend
- [ ] Run `database_order_lifecycle.sql` migration
- [ ] Install Ratchet: `composer require cboden/ratchet`
- [ ] Start WebSocket server: `php websocket_server.php` (use PM2/supervisor for production)
- [ ] Create `backend/data/courier_photos/` directory (chmod 755)
- [ ] Configure WebSocket URL in `.env`: `WS_URL=wss://your-domain.com`

### Frontend
- [ ] Update `NEXT_PUBLIC_WS_URL` in `.env.local`
- [ ] Test all API endpoints
- [ ] Test WebSocket connection
- [ ] Verify image uploads work

### Production Notes
- **WebSocket:** Use `wss://` (secure) in production
- **WhatsApp OTP:** Integrate real WhatsApp Business API (replace simulated OTP)
- **Google Maps:** Add API key when ready (currently dummy/simulated)
- **Process Manager:** Use PM2 to keep WebSocket server running

---

## 🎉 Features Summary

✅ **State Machine** - 7 order statuses with validation  
✅ **Real-Time** - WebSocket live updates  
✅ **Courier Management** - Assignment, tracking, availability  
✅ **Photo Verification** - Mandatory delivery proof  
✅ **COD OTP** - Simulated 2FA with trusted user auto-approve  
✅ **Admin Kanban** - Visual order management board  
✅ **Customer Tracker** - Public order tracking page  
✅ **Status History** - Complete audit trail  
✅ **Location Trail** - GPS tracking table (ready for Maps integration)  

---

## 📝 TODO (Future Enhancements)

- [ ] Google Maps API integration (replace dummy tracker)
- [ ] Real WhatsApp OTP via Business API
- [ ] SMS fallback for OTP
- [ ] Push notifications for status changes
- [ ] Courier mobile app
- [ ] Analytics dashboard
- [ ] Automated courier assignment (AI-based)
- [ ] Delivery time predictions

---

## 🐛 Troubleshooting

**WebSocket won't start:**
```bash
composer require cboden/ratchet
php -v  # Ensure PHP 7.4+
```

**OTP not working:**
- Check `cod_verifications` table exists
- Verify user authentication (JWT token)
- Check order payment_method is 'cod'

**Photo upload fails:**
- Create directory: `mkdir backend/data/courier_photos`
- Set permissions: `chmod 755 backend/data/courier_photos`

**Status update rejected:**
- Check state machine transitions (e.g., can't go from 'completed' to 'preparing')
- Verify user role (admin/courier required for most updates)

---

**Phase 2 Complete!** 🎊
