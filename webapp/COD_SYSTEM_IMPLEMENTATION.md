# 🛡️ COD PAYMENT SYSTEM - IMPLEMENTATION GUIDE
**DailyCup WebApp - Secure Cash on Delivery System**

---

## 📋 OVERVIEW

Sistem pembayaran COD (Cash on Delivery) yang aman dengan fitur anti-fraud, trust score, dan workflow approval manual oleh admin. Mirip dengan sistem GrabFood, GoFood, dan ShopeeFood.

### ✨ Key Features

- ✅ **Trust Score System** - Reputasi user berdasarkan history transaksi
- ✅ **Amount Limit** - Batasan nominal berbeda untuk new user vs verified user
- ✅ **Distance Restriction** - COD hanya untuk radius 5 KM
- ✅ **Blacklist Protection** - Auto-ban user yang melakukan fraud
- ✅ **Manual Admin Confirmation** - Verifikasi pesanan COD sebelum ditugaskan ke kurir
- ✅ **Auto-Cancel Expired Orders** - Otomatis cancel setelah 60 menit jika belum dibayar
- ✅ **Real-time Status Tracking** - Notifikasi setiap perubahan status pesanan
- ✅ **Photo Proof Required** - Kurir wajib upload foto bukti delivered

---

## 📊 DATABASE SCHEMA

### New/Modified Tables

#### 1. `users` - Enhanced with Trust Score
```sql
ALTER TABLE users ADD COLUMN trust_score INT DEFAULT 0; -- 0-100
ALTER TABLE users ADD COLUMN total_successful_orders INT DEFAULT 0;
ALTER TABLE users ADD COLUMN cod_enabled TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN cod_blacklisted TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN blacklist_reason TEXT NULL;
ALTER TABLE users ADD COLUMN is_verified_user TINYINT(1) DEFAULT 0;
```

#### 2. `orders` - COD Features
```sql
ALTER TABLE orders ADD COLUMN expires_at DATETIME NULL; -- 60 min timeout
ALTER TABLE orders ADD COLUMN delivery_distance DECIMAL(5,2) NULL;
ALTER TABLE orders ADD COLUMN cod_amount_limit DECIMAL(10,2) NULL;
ALTER TABLE orders ADD COLUMN admin_confirmed_at DATETIME NULL;
ALTER TABLE orders ADD COLUMN admin_confirmed_by INT NULL;
ALTER TABLE orders ADD COLUMN cancellation_reason TEXT NULL;
```

#### 3. `cod_validation_rules` - Dynamic Rules
```sql
CREATE TABLE cod_validation_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL UNIQUE,
    rule_type ENUM('amount', 'distance', 'trust_score', 'order_count'),
    rule_value DECIMAL(10,2) NOT NULL,
    rule_operator ENUM('lt', 'lte', 'gt', 'gte', 'eq') DEFAULT 'lte',
    is_active TINYINT(1) DEFAULT 1,
    description TEXT NULL
);

-- Default rules
INSERT INTO cod_validation_rules VALUES
(1, 'max_cod_amount_new_user', 'amount', 50000.00, 'lte', 1, 'New users: max COD Rp 50.000'),
(2, 'max_cod_amount_verified', 'amount', 100000.00, 'lte', 1, 'Verified users: max COD Rp 100.000'),
(3, 'max_delivery_distance', 'distance', 5.00, 'lte', 1, 'Maximum delivery distance: 5 KM'),
(4, 'min_trust_score_cod', 'trust_score', 20.00, 'gte', 1, 'Minimum trust score for COD'),
(5, 'min_orders_for_verified', 'order_count', 1.00, 'gte', 1, 'Min successful orders to be verified');
```

#### 4. `order_status_logs` - Audit Trail
```sql
CREATE TABLE order_status_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    from_status VARCHAR(50) NULL,
    to_status VARCHAR(50) NOT NULL,
    changed_by_type ENUM('system', 'admin', 'kurir', 'customer') DEFAULT 'system',
    changed_by_id INT NULL,
    reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

#### 5. `user_fraud_logs` - Fraud Detection
```sql
CREATE TABLE user_fraud_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_id INT NULL,
    fraud_type ENUM('cod_reject', 'fake_order', 'payment_fraud', 'address_fraud', 'other'),
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NULL,
    admin_action ENUM('none', 'warning', 'cod_ban', 'account_suspend') DEFAULT 'none',
    admin_notes TEXT NULL,
    reported_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔌 API ENDPOINTS

### 1. **Validate COD Eligibility**
**Endpoint:** `POST /webapp/backend/api/validate_cod.php`

**Request:**
```json
{
  "user_id": 1,
  "order_amount": 45000,
  "delivery_distance": 3.5,
  "delivery_address": "Jl. Sudirman No. 123"
}
```

**Response (Eligible):**
```json
{
  "eligible": true,
  "reasons": [
    "✅ Anda memenuhi syarat untuk menggunakan COD",
    "✅ Verified User - Limit COD: Rp 100.000"
  ],
  "user_status": {
    "trust_score": 40,
    "is_verified": true,
    "total_orders": 4,
    "cod_enabled": true,
    "is_blacklisted": false,
    "user_type": "verified"
  },
  "limits": {
    "max_amount": 100000,
    "max_distance": 5.00,
    "min_trust_score": 20
  },
  "has_warnings": false
}
```

**Response (Not Eligible):**
```json
{
  "eligible": false,
  "reasons": [
    "❌ Jumlah pesanan (Rp 120.000) melebihi batas COD (Rp 100.000)",
    "ℹ️ Gunakan pembayaran online untuk pesanan ini"
  ],
  "user_status": { ... },
  "recommendations": [
    "Gunakan pembayaran online untuk pesanan dengan nilai lebih tinggi"
  ]
}
```

---

### 2. **Create Order (Enhanced with COD)**
**Endpoint:** `POST /webapp/backend/api/create_order.php`

**Request:**
```json
{
  "items": [...],
  "total": 45000,
  "customer": {...},
  "paymentMethod": "cod",
  "deliveryDistance": 3.5,
  "deliveryMethod": "delivery",
  "customerLat": -6.2088,
  "customerLng": 106.8456
}
```

**Response (COD Success):**
```json
{
  "success": true,
  "orderId": "ORD-1707377432-5678",
  "order_number": "ORD-1707377432-5678",
  "payment_method": "cod",
  "message": "Pesanan COD dibuat. Menunggu konfirmasi admin dalam 60 menit.",
  "expires_at": "2026-02-08 04:30:00",
  "requires_confirmation": true,
  "redirect": "/checkout/success?orderId=ORD-1707377432-5678"
}
```

**COD Validation Errors:**
```json
{
  "success": false,
  "message": "Akun Anda diblokir dari menggunakan COD",
  "reasons": [
    "❌ Akun Anda diblokir dari menggunakan COD",
    "Alasan: Fake order - COD reject 3x",
    "ℹ️ Gunakan pembayaran online untuk melanjutkan"
  ]
}
```

---

### 3. **Admin Confirm COD Order**
**Endpoint:** `POST /webapp/backend/api/admin_confirm_cod.php`

**Authentication:** Admin session/JWT required

**Request (Approve):**
```json
{
  "order_id": 123,
  "action": "approve",
  "reason": "Order valid, proceeding"
}
```

**Response (Approve Success):**
```json
{
  "success": true,
  "message": "COD order approved and kurir assigned",
  "order_status": "processing",
  "kurir_assigned": true,
  "kurir_name": "Budi Santoso"
}
```

**Request (Reject + Blacklist):**
```json
{
  "order_id": 123,
  "action": "reject",
  "reason": "Fake order - no answer on phone",
  "is_fraud": true
}
```

**Response (Reject + Blacklist):**
```json
{
  "success": true,
  "message": "COD order rejected and user blacklisted",
  "order_status": "cancelled",
  "user_blacklisted": true
}
```

---

### 4. **Kurir Update Delivery Status**
**Endpoint:** `POST /webapp/backend/api/kurir_update_delivery_status.php`

**Status Flow:**
1. `going_to_store` - Kurir menuju outlet
2. `arrived_at_store` - Kurir sampai di outlet (status: ready)
3. `picked_up` - Pesanan diambil (status: delivering)
4. `nearby` - Kurir mendekati lokasi customer
5. `delivered` - Pesanan diterima (status: completed, requires photo)

**Request (Delivered):**
```json
{
  "kurir_id": 1,
  "order_id": 123,
  "status": "delivered",
  "latitude": -6.2088,
  "longitude": 106.8456,
  "notes": "Delivered to customer directly",
  "photo_proof": "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Status updated successfully",
  "order_status": "completed",
  "notification_sent": true
}
```

---

## 🔄 COMPLETE WORKFLOW

### Scenario 1: COD Order - Happy Path

```
[Customer]
   ↓
1. Select items → Checkout
   ↓
2. Choose payment method: "COD"
   ↓ (Frontend calls validate_cod.php)
3. ✅ COD Eligible (trust_score: 40, verified user)
   ↓
4. Submit Order (create_order.php)
   ├─ Order created: status='pending', payment_status='pending'
   ├─ expires_at = NOW() + 60 minutes
   ├─ delivery_distance = 3.5 KM
   └─ cod_amount_limit = 100,000
   ↓
5. 📧 Admin Notification: "COD Order Menunggu Konfirmasi"
   ↓
[Admin Panel]
   ↓
6. Admin reviews order details
   ├─ Check phone number valid
   ├─ Check address valid
   └─ Decide: Approve or Reject
   ↓
7. Admin clicks "Approve" (admin_confirm_cod.php)
   ├─ status → 'confirmed'
   ├─ Admin notification updated
   └─ Customer notification sent
   ↓
8. 🤖 Auto-Assign Kurir (in admin_confirm_cod.php)
   ├─ Find available kurir
   ├─ status → 'processing'
   ├─ kurir.status → 'busy'
   └─ Kurir notification sent
   ↓
[Kurir App]
   ↓
9. Kurir accepts order
   ↓ (kurir_update_delivery_status.php)
10. Status: 'going_to_store'
    📧 Customer: "Kurir menuju outlet"
   ↓
11. Status: 'arrived_at_store'
    ├─ Order status → 'ready'
    └─ 📧 Customer: "Kurir tiba di outlet"
   ↓
12. Status: 'picked_up' (with photo)
    ├─ Order status → 'delivering'
    ├─ pickup_time = NOW()
    └─ 📧 Customer: "Pesanan dalam perjalanan"
   ↓
13. Status: 'nearby'
    └─ 📧 Customer: "Kurir sudah dekat! Tunggu di depan"
   ↓
14. Status: 'delivered' ⚠️ REQUIRES PHOTO
    ├─ Order status → 'completed'
    ├─ payment_status → 'paid' (COD paid on delivery)
    ├─ kurir.status → 'available'
    ├─ user.trust_score += 10
    ├─ user.total_successful_orders += 1
    ├─ Photo saved to: assets/images/deliveries/
    ├─ 📧 Customer: "Pesanan diterima dengan aman"
    └─ 📧 Admin: "Pembayaran COD diterima Rp 45.000"

[Order Complete] ✅
```

---

### Scenario 2: COD Order - Expired (Not Paid)

```
1. Customer creates COD order
   ├─ expires_at = "2026-02-08 04:30:00"
   └─ Admin notification sent
   ↓
2. Admin doesn't review within 60 minutes
   ↓
3. ⏰ Auto-Cancel Event (runs every 5 minutes)
   ├─ MySQL Event Scheduler
   ├─ Procedure: cancel_expired_orders()
   ├─ Check: expires_at < NOW() AND payment_status = 'pending'
   ↓
4. Order cancelled
   ├─ status → 'cancelled'
   ├─ cancellation_reason = "Order expired - payment not received within 60 minutes"
   └─ 📧 Customer notification
```

---

### Scenario 3: COD Order - Rejected by Admin (Fraud)

```
1. Admin reviews COD order
   ├─ Phone number fake
   ├─ Address suspicious
   └─ User history shows 2x reject before
   ↓
2. Admin clicks "Reject" + Mark as Fraud
   ↓ (admin_confirm_cod.php with is_fraud=true)
3. Order cancelled
   ├─ status → 'cancelled'
   ├─ cancellation_reason = "Fake order - no answer"
   └─ 📧 Customer notification
   ↓
4. User blacklisted from COD
   ├─ cod_blacklisted = 1
   ├─ cod_enabled = 0
   ├─ blacklist_reason = "Fake/fraud COD order: ..."
   └─ blacklist_date = NOW()
   ↓
5. Fraud log created
   ├─ user_fraud_logs table
   ├─ fraud_type = 'fake_order'
   ├─ severity = 'high'
   └─ admin_action = 'cod_ban'
   ↓
6. ⚠️ User receives warning notification
   └─ "Akun Anda diblokir dari COD karena: Fake order"

[User can only use online payment from now on]
```

---

### Scenario 4: Online Payment (Xendit) - Auto Flow

```
[Customer]
   ↓
1. Select items → Checkout
   ↓
2. Choose payment method: "Online (Xendit)"
   ↓
3. Submit Order (create_order.php)
   ├─ Order created: status='pending'
   ├─ expires_at = NOW() + 60 minutes
   ├─ Xendit invoice created
   └─ Redirect to invoice_url
   ↓
[Xendit Payment Page]
   ↓
4. Customer pays (VA/QRIS/Card)
   ↓
5. ✅ Payment Success
   ↓ Xendit webhook → notify_xendit.php
6. Payment confirmed
   ├─ payment_status → 'paid'
   ├─ status → 'processing'
   ├─ paid_at = NOW()
   ├─ 📧 Admin: "Pembayaran Diterima"
   └─ 📧 Customer: "Pembayaran Berhasil"
   ↓
7. 🤖 Auto-Assign Kurir
   ├─ Find available kurir
   ├─ kurir.status → 'busy'
   └─ Kurir notification sent
   ↓
[Same as COD Step 9-14 above]
```

---

## 🛡️ SECURITY FEATURES

### 1. Trust Score System

**Initial Score:** 0 (new user)

**Score Rules:**
- ✅ Completed order: **+10 points** (max 100)
- ❌ Cancelled COD order: **-5 points**
- ❌ Fake order (blacklisted): **Trust score reset to 0**

**User Types:**
- **New User** (total_orders = 0): COD limit Rp 50.000, max distance 5 KM
- **Verified User** (total_orders ≥ 1): COD limit Rp 100.000, full features
- **Blacklisted User**: COD disabled permanently

**Auto-Upgrade:**
```sql
-- Trigger: after_order_complete
-- When order status='completed' AND payment_status='paid'
UPDATE users SET
    trust_score = LEAST(100, trust_score + 10),
    total_successful_orders = total_successful_orders + 1,
    is_verified_user = IF(total_successful_orders >= 1, 1, is_verified_user),
    cod_enabled = IF(total_successful_orders >= 1 AND cod_blacklisted = 0, 1, cod_enabled)
WHERE id = {user_id};
```

---

### 2. COD Validation Rules (Dynamic)

Admin dapat mengubah rules melalui database:

```sql
-- Increase new user COD limit to 75k
UPDATE cod_validation_rules 
SET rule_value = 75000 
WHERE rule_name = 'max_cod_amount_new_user';

-- Extend maximum distance to 8 KM
UPDATE cod_validation_rules 
SET rule_value = 8.00 
WHERE rule_name = 'max_delivery_distance';

-- Disable a rule temporarily
UPDATE cod_validation_rules 
SET is_active = 0 
WHERE rule_name = 'min_trust_score_cod';
```

---

### 3. Auto-Cancel Expired Orders

**MySQL Event Scheduler** (runs every 5 minutes):

```sql
-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Event definition
CREATE EVENT auto_cancel_expired_orders
ON SCHEDULE EVERY 5 MINUTE
DO CALL cancel_expired_orders();

-- Stored procedure
CREATE PROCEDURE cancel_expired_orders()
BEGIN
    -- Cancel orders expired + not paid
    UPDATE orders SET
        status = 'cancelled',
        cancellation_reason = 'Order expired - payment not received within 60 minutes'
    WHERE expires_at < NOW() 
      AND payment_status = 'pending'
      AND status IN ('pending', 'confirmed');
    
    -- Send notifications to affected users
    INSERT INTO notifications (user_id, type, title, message)
    SELECT user_id, 'order_cancelled', 'Pesanan Dibatalkan',
           CONCAT('Pesanan #', order_number, ' dibatalkan karena timeout 60 menit')
    FROM orders
    WHERE status = 'cancelled' AND cancellation_reason LIKE '%expired%';
END;
```

**Check Event Status:**
```sql
SHOW EVENTS WHERE Name = 'auto_cancel_expired_orders';
```

---

### 4. Photo Proof Requirements

**Kurir MUST upload photo when status = 'delivered':**

```javascript
// Frontend validation
if (status === 'delivered' && !photoProof) {
  alert('❌ Foto bukti pengiriman wajib diupload!');
  return;
}

// Backend validation (kurir_update_delivery_status.php)
if ($newStatus === 'delivered' && !$photoProof) {
    throw new Exception('Photo proof is required for delivery confirmation');
}
```

**Photo Storage:**
- Path: `assets/images/deliveries/`
- Format: `{order_id}_delivered_{timestamp}.jpg`
- Also saved in `delivery_history` table

---

## 📊 NOTIFICATIONS FLOW

### Customer Notifications
```
Order Created (COD)     → "Pesanan dibuat, menunggu konfirmasi admin"
Order Confirmed         → "Pesanan dikonfirmasi, kurir akan ditugaskan"
Kurir Assigned          → "Kurir {name} ditugaskan untuk pesanan Anda"
Kurir Going             → "Kurir menuju outlet"
Kurir Arrived           → "Kurir tiba di outlet, sedang mengambil pesanan"
Kurir Picked Up         → "🚀 Pesanan dalam perjalanan!"
Kurir Nearby            → "📍 Kurir sudah dekat! Tunggu di depan"
Order Delivered         → "✅ Pesanan diterima dengan aman. Terima kasih!"
Order Cancelled         → "❌ Pesanan dibatalkan: {reason}"
```

### Admin Notifications
```
New COD Order          → "⏳ COD Order Menunggu Konfirmasi"
Payment Received (Online) → "💰 Pembayaran Diterima: Rp {amount}"
COD Payment Received   → "💰 Pembayaran COD Diterima dari Kurir: Rp {amount}"
```

### Kurir Notifications
```
New Order Assigned     → "🆕 Pesanan baru menunggu diambil"
Order Ready            → "✅ Pesanan siap diambil di outlet"
```

---

## 🧪 TESTING GUIDE

### 1. Test COD Validation

```bash
# Test with verified user (eligible)
php test_cod_validation.php

# Expected: eligible=true, user_type=verified, max_amount=100000
```

### 2. Test Create COD Order

```bash
# Create order via API
curl -X POST http://localhost/DailyCup/webapp/backend/api/create_order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"id": 1, "name": "Latte", "price": 35000, "quantity": 1}],
    "total": 50000,
    "customer": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "081234567890",
      "address": "Jl. Test No. 123"
    },
    "paymentMethod": "cod",
    "deliveryDistance": 3.5,
    "deliveryMethod": "delivery"
  }'

# Expected: 
# - success=true
# - requires_confirmation=true
# - expires_at set to +60 minutes
# - Admin notification created
```

### 3. Test

 Admin Confirm COD

```bash
# Approve COD order
curl -X POST http://localhost/DailyCup/webapp/backend/api/admin_confirm_cod.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID={admin_session}" \
  -d '{
    "order_id": 123,
    "action": "approve"
  }'

# Expected: 
# - success=true
# - order_status=processing
# - kurir_assigned=true
```

### 4. Test Auto-Cancel Expired

```sql
-- Manually test expired order cancellation
-- 1. Create order with past expires_at
INSERT INTO orders (user_id, order_number, total_amount, final_amount, 
                    delivery_method, status, payment_method, payment_status, 
                    expires_at, created_at)
VALUES (1, 'TEST-EXPIRED-001', 50000, 50000, 
        'delivery', 'pending', 'cod', 'pending',
        DATE_SUB(NOW(), INTERVAL 1 HOUR), NOW());

-- 2. Run cancel procedure manually
CALL cancel_expired_orders();

-- 3. Check if order cancelled
SELECT status, cancellation_reason FROM orders WHERE order_number = 'TEST-EXPIRED-001';
-- Expected: status='cancelled', cancellation_reason LIKE '%expired%'
```

### 5. Test Kurir Delivery

```bash
# Test delivered (requires photo)
curl -X POST http://localhost/DailyCup/webapp/backend/api/kurir_update_delivery_status.php \
  -H "Content-Type: application/json" \
  -d '{
    "kurir_id": 1,
    "order_id": 123,
    "status": "delivered",
    "photo_proof": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
    "latitude": -6.2088,
    "longitude": 106.8456
  }'

# Expected:
# - success=true
# - order_status=completed
# - payment_status=paid (for COD)
# - kurir status back to 'available'
# - trust_score increased
```

---

## 🚀 FRONTEND INTEGRATION

### Checkout Page Enhancement

```typescript
// webapp/frontend/app/checkout/page.tsx

async function handleCheckout() {
  // 1. Validate COD eligibility if COD selected
  if (paymentMethod === 'cod') {
    const validation = await api.post('/validate_cod.php', {
      user_id: user.id,
      order_amount: total,
      delivery_distance: calculateDistance(userAddress, storeAddress),
      delivery_address: userAddress
    });
    
    if (!validation.eligible) {
      // Show error modal with reasons
      showErrorModal(validation.reasons, validation.recommendations);
      return;
    }
    
    // Show COD confirmation dialog
    const confirmed = await showCODConfirmation({
      amount: total,
      limit: validation.limits.max_amount,
      expires_in: '60 menit',
      requires_admin_approval: true
    });
    
    if (!confirmed) return;
  }
  
  // 2. Create order
  const response = await api.post('/create_order.php', {
    items,
    total,
    customer,
    paymentMethod,
    deliveryDistance,
    deliveryMethod,
    customerLat,
    customerLng
  });
  
  // 3. Handle response
  if (response.success) {
    if (paymentMethod === 'cod') {
      // COD: Redirect to success page with "waiting confirmation" message
      router.push(response.redirect);
    } else {
      // Online: Redirect to Xendit invoice
      window.location.href = response.xendit.invoice_url;
    }
  }
}
```

### Success Page Enhancement

```typescript
// webapp/frontend/app/checkout/success/page.tsx

export default function SuccessPage() {
  const [order, setOrder] = useState(null);
  const orderId = useSearchParams().get('orderId');
  
  useEffect(() => {
    async function fetchOrder() {
      const data = await api.get(`/get_order.php?orderId=${orderId}`);
      setOrder(data);
    }
    fetchOrder();
  }, [orderId]);
  
  const isCOD = order?.payment_method === 'cod';
  const isPending = order?.status === 'pending';
  
  return (
    <div>
      {isCOD && isPending ? (
        <>
          <h1 className="text-orange-600">⏳ Menunggu Konfirmasi Admin</h1>
          <p>Pesanan COD Anda sedang diverifikasi oleh admin.</p>
          <p>Akan expired dalam: <Countdown expiresAt={order.expires_at} /></p>
          <Link href="/orders">Lihat Status Pesanan</Link>
        </>
      ) : (
        <>
          <h1 className="text-green-600">✅ Pesanan Berhasil!</h1>
          <p>Pesanan #{order.order_number} sedang diproses.</p>
          <OrderTracker orderId={orderId} />
        </>
      )}
    </div>
  );
}
```

### Order Tracking Component

```typescript
// Real-time order tracking
function OrderTracker({ orderId }) {
  const [status, setStatus] = useState('pending');
  const [history, setHistory] = useState([]);
  
  useEffect(() => {
    // Poll every 10 seconds
    const interval = setInterval(async () => {
      const data = await api.get(`/track_order.php?orderId=${orderId}`);
      setStatus(data.status);
      setHistory(data.history);
    }, 10000);
    
    return () => clearInterval(interval);
  }, [orderId]);
  
  const steps = [
    { key: 'pending', label: 'Menunggu Konfirmasi', icon: '⏳' },
    { key: 'confirmed', label: 'Dikonfirmasi', icon: '✅' },
    { key: 'processing', label: 'Diproses', icon: '👨‍🍳' },
    { key: 'ready', label: 'Siap Diambil', icon: '📦' },
    { key: 'delivering', label: 'Dalam Pengiriman', icon: '🚴' },
    { key: 'completed', label: 'Selesai', icon: '🎉' }
  ];
  
  return (
    <div className="order-tracker">
      {steps.map(step => (
        <div 
          key={step.key}
          className={status === step.key ? 'active' : ''}
        >
          <span>{step.icon}</span>
          <span>{step.label}</span>
        </div>
      ))}
      
      <div className="history">
        {history.map(log => (
          <div key={log.id}>
            <strong>{log.to_status}</strong>
            <small>{log.created_at}</small>
            <p>{log.notes}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## 📈 MONITORING & ANALYTICS

### Key Metrics to Track

```sql
-- 1. COD Conversion Rate
SELECT 
    COUNT(CASE WHEN payment_method = 'cod' THEN 1 END) as cod_orders,
    COUNT(*) as total_orders,
    ROUND(COUNT(CASE WHEN payment_method = 'cod' THEN 1 END) * 100.0 / COUNT(*), 2) as cod_percentage
FROM orders
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 2. COD Success vs Cancelled Rate
SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
    ROUND(COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*), 2) as success_rate
FROM orders
WHERE payment_method = 'cod' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 3. Average COD Order Value
SELECT 
    AVG(final_amount) as avg_cod_value,
    MIN(final_amount) as min_value,
    MAX(final_amount) as max_value
FROM orders
WHERE payment_method = 'cod' AND status = 'completed';

-- 4. Trust Score Distribution
SELECT 
    CASE 
        WHEN trust_score = 0 THEN '0 (New)'
        WHEN trust_score BETWEEN 1 AND 20 THEN '1-20 (Low)'
        WHEN trust_score BETWEEN 21 AND 50 THEN '21-50 (Medium)'
        WHEN trust_score BETWEEN 51 AND 80 THEN '51-80 (High)'
        ELSE '81-100 (Excellent)'
    END as trust_level,
    COUNT(*) as user_count,
    SUM(cod_enabled) as cod_enabled_count
FROM users
GROUP BY trust_level;

-- 5. Blacklisted Users
SELECT 
    COUNT(*) as blacklisted_users,
    blacklist_reason,
    COUNT(*) as frequency
FROM users
WHERE cod_blacklisted = 1
GROUP BY blacklist_reason
ORDER BY frequency DESC;

-- 6. Admin COD Confirmation Time
SELECT 
    AVG(TIMESTAMPDIFF(MINUTE, created_at, admin_confirmed_at)) as avg_confirmation_minutes,
    MIN(TIMESTAMPDIFF(MINUTE, created_at, admin_confirmed_at)) as fastest,
    MAX(TIMESTAMPDIFF(MINUTE, created_at, admin_confirmed_at)) as slowest
FROM orders
WHERE payment_method = 'cod' AND admin_confirmed_at IS NOT NULL;

-- 7. Expired Orders (Not Confirmed in Time)
SELECT 
    COUNT(*) as expired_orders,
    DATE(created_at) as date
FROM orders
WHERE status = 'cancelled' 
  AND cancellation_reason LIKE '%expired%'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at);
```

---

## 🔧 TROUBLESHOOTING

### Issue 1: COD Validation Always Returns False

**Check:**
1. User trust_score: `SELECT trust_score FROM users WHERE id = {user_id}`
2. COD rules active: `SELECT * FROM cod_validation_rules WHERE is_active = 1`
3. User blacklist status: `SELECT cod_blacklisted, blacklist_reason FROM users WHERE id = {user_id}`

**Solution:**
```sql
-- Reset user COD access
UPDATE users SET cod_blacklisted = 0, cod_enabled = 1 WHERE id = {user_id};
```

---

### Issue 2: Auto-Cancel Not Working

**Check Event Scheduler:**
```sql
SHOW VARIABLES LIKE 'event_scheduler';
-- Should return: ON

-- If OFF, enable it
SET GLOBAL event_scheduler = ON;

-- Check event status
SHOW EVENTS WHERE Name = 'auto_cancel_expired_orders';

-- Manually trigger
CALL cancel_expired_orders();
```

---

### Issue 3: Kurir Not Auto-Assigned

**Check:**
1. Available kurirs: `SELECT * FROM kurir WHERE status = 'available' AND is_active = 1`
2. Order status: Should be 'confirmed' or 'processing'

**Manual Assign:**
```sql
-- Find available kurir
SELECT id, name FROM kurir WHERE status = 'available' LIMIT 1;

-- Assign manually
UPDATE orders SET kurir_id = {kurir_id}, assigned_at = NOW() WHERE id = {order_id};
UPDATE kurir SET status = 'busy' WHERE id = {kurir_id};
```

---

### Issue 4: Photo Upload Fails

**Check:**
1. Directory permissions: `chmod 755 assets/images/deliveries/`
2. PHP upload limits: `upload_max_filesize`, `post_max_size`

**Create directory:**
```bash
mkdir -p webapp/backend/assets/images/deliveries
chmod -R 755 webapp/backend/assets/images/deliveries
```

---

## 📝 NEXT STEPS & ENHANCEMENTS

### Phase 1 Completed ✅
- [x] Trust score system
- [x] COD validation API
- [x] Admin confirmation workflow
- [x] Auto-cancel expired orders
- [x] Kurir delivery tracking
- [x] Photo proof requirements
- [x] User blacklist protection

### Phase 2 (Recommended)
- [ ] **Admin Dashboard COD Panel** - UI untuk review & confirm COD orders
- [ ] **Real-time Kurir GPS Tracking** - Integrate with Google Maps API
- [ ] **Customer Rating System** - Rate kurir after delivery
- [ ] **SMS Notifications** - Backup for critical updates
- [ ] **Fraud Detection ML** - Machine learning untuk detect patterns
- [ ] **Multi-Store Support** - COD rules per outlet location
- [ ] **COD Insurance** - Partner dengan asuransi untuk cover losses

---

## 📞 SUPPORT

**Questions or Issues?**
- Check logs: `c:\laragon\logs\php_error.log`
- Database: `mysql -u root dailycup_db`
- Test APIs: `php test_cod_validation.php`

**Documentation:** This file 😊

---

**Last Updated:** February 8, 2026
**Version:** 1.0.0
**Status:** ✅ Production Ready
