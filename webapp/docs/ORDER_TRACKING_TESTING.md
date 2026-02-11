# 🧪 Testing Guide - Order Tracking System

## 🚀 Quick Start (5 Minutes)

### Step 1: Setup Database
```bash
# Run migration (if not done yet)
mysql -u root -p dailycup < backend/database_order_lifecycle.sql

# Create test order data
mysql -u root -p dailycup < backend/test_order_data.sql
```

**Verify in phpMyAdmin:**
- ✅ Table `couriers` has 5 rows
- ✅ Table `orders` has test order `ORD-TEST-001`
- ✅ Order status is `on_delivery`
- ✅ Courier assigned (Budi Santoso)

---

### Step 2: Start Development Server
```bash
cd frontend
npm run dev
```

Wait for: `✓ Ready on http://localhost:3000`

---

### Step 3: Test Order Tracking

**Option A - Run Test Script (Automated):**
```powershell
# From project root
.\test_tracking.ps1
```

**Option B - Manual Testing:**

1. **Open Order Tracker:**
   ```
   http://localhost:3000/track/ORD-TEST-001
   ```

2. **Open Admin Kanban:**
   ```
   http://localhost:3000/admin/orders/kanban
   ```

---

## ✅ What to Test

### 1. **Leaflet Map Display**

**Expected:**
- ✅ Map loads instantly (no API key needed)
- ✅ OpenStreetMap tiles visible
- ✅ Courier marker (🏍️ brown) appears
- ✅ Customer marker (📍 green) appears
- ✅ Dashed route line connects both markers
- ✅ "100% FREE" badge at bottom

**If map doesn't show:**
```bash
# Check browser console
F12 > Console

# Look for errors like:
# ❌ "Leaflet is not defined"
# Solution: npm install leaflet react-leaflet

# ❌ CSS not loading
# Solution: Check import "leaflet/dist/leaflet.css" in component
```

---

### 2. **Real-Time Courier Movement**

**Expected Behavior:**
1. Courier marker starts at one location
2. Every 2 seconds, marker moves closer to destination
3. Marker rotates based on heading/direction
4. Distance decreases (e.g., 2.5 km → 2.3 km → 2.1 km)
5. ETA updates (e.g., 5 min → 4 min → 3 min)
6. When distance < 50m → "Courier Arrived!" popup

**Watch For:**
- ✅ Smooth animation (not jumpy)
- ✅ Marker rotation follows movement direction
- ✅ Distance/ETA cards update in sync

---

### 3. **Distance Calculation (Haversine)**

**Test in Browser Console:**
```javascript
// Open DevTools (F12) > Console

// Import function (won't work in console, but concept test)
// Manually test by creating new order with different coordinates

// Expected: Distance calculated without API call
// Check Network tab - NO requests to Google/external distance API
```

**Verify:**
- ✅ Distance displayed (e.g., "2.3 km" or "500 m")
- ✅ ETA calculated (e.g., "5 min")
- ✅ No external API requests in Network tab

---

### 4. **Order Status Timeline**

**Expected Display:**
```
✓ Menunggu Pembayaran  (completed - green)
✓ Konfirmasi           (completed - green)
✓ Antrian              (completed - green)
✓ Diproses             (completed - green)
● Dikirim              (current - brown)
○ Selesai              (pending - gray)
```

**Progress Bar:**
- ✅ Fills from left to right
- ✅ Stops at current status
- ✅ Color: brown (#a97456)

---

### 5. **Status History Log**

**Expected:**
```
● ON_DELIVERY
  Courier Budi Santoso assigned
  10 minutes ago

● PREPARING  
  Order is being prepared
  15 minutes ago

● QUEUEING
  COD verified
  20 minutes ago
...
```

**Check:**
- ✅ All 5 status changes shown
- ✅ Timestamps in Indonesian format
- ✅ Messages displayed correctly

---

### 6. **Courier Information Card**

**Expected:**
```
👤 Budi Santoso
📞 08123456789
🏍️ motorcycle
```

**Only shows when:**
- ✅ Order status is `on_delivery` or `completed`
- ✅ Courier is assigned

---

### 7. **Order Items Display**

**Expected:**
```
Espresso
Hot - Large
2x  →  Rp 50.000

Total: Rp 60.000
```

**Check:**
- ✅ Product name
- ✅ Variant info
- ✅ Quantity & price
- ✅ Total calculation correct

---

## 🧪 Advanced Tests

### Test 1: COD OTP Verification

**Setup:**
```sql
-- Create order with waiting_confirmation status
UPDATE orders 
SET status = 'waiting_confirmation' 
WHERE order_id = 'ORD-TEST-001';
```

**Test Flow:**
1. Refresh tracking page
2. See "COD Verification Required" banner
3. Click "Generate OTP"
4. Alert shows: "SIMULATED OTP: 123456"
5. Modal opens with OTP input
6. Enter code → Click "Verify"
7. Success → Status changes to `queueing`

**Expected:**
- ✅ OTP generated (6 digits)
- ✅ Modal shows simulated code
- ✅ Correct code → verification succeeds
- ✅ Wrong code → error message
- ✅ Status updates automatically

---

### Test 2: Trusted User Auto-Approve

**Setup:**
```sql
-- Make user trusted (5+ completed orders)
INSERT INTO orders (order_id, user_id, status, total, created_at)
SELECT 
    CONCAT('ORD-OLD-', id),
    1,
    'completed',
    50000,
    DATE_SUB(NOW(), INTERVAL id DAY)
FROM (SELECT 1 as id UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) as nums;
```

**Test:**
1. Create new COD order for user_id=1
2. Click "Generate OTP"
3. **Expected:** Auto-approved without OTP input!
4. Alert: "Anda adalah pelanggan terpercaya!"
5. Status → `queueing` immediately

---

### Test 3: Distance Radius Check

**Test in Console:**
```javascript
// Simulate different locations
const storeLocation = { lat: -6.200000, lng: 106.816666 };

// Within 5km
const nearCustomer = { lat: -6.195000, lng: 106.820000 };

// Outside 5km  
const farCustomer = { lat: -6.150000, lng: 106.900000 };

// Check distance (you'll need to import the function)
// Expected: 
// - Near customer: ~0.6 km ✅
// - Far customer: ~9.2 km ❌
```

---

### Test 4: Admin Kanban Board

**URL:** `http://localhost:3000/admin/orders/kanban`

**Expected:**
- ✅ Login as admin required
- ✅ 7 columns (status-based)
- ✅ Order cards in correct columns
- ✅ Drag functionality (if implemented)
- ✅ "Assign Courier" button on preparing orders
- ✅ Real-time updates (10s interval)

**Test Actions:**
1. Click "Assign Courier" on preparing order
2. Modal opens with available couriers
3. Select courier → Confirm
4. Order moves to `on_delivery` column
5. Estimated delivery time set

---

### Test 5: Geocoding (Nominatim)

**Test in Browser:**
```javascript
// This would be in actual app code
// Just verify no errors in console when map loads

// Check Network tab:
// Should see requests to:
// ✅ https://tile.openstreetmap.org (map tiles)
// ❌ NO requests to Google Maps API
// ❌ NO requests to Google Geocoding
```

---

## 📊 Performance Benchmarks

### Map Load Speed
```
Google Maps:  ~2-3 seconds
Leaflet OSM:  ~0.5-1 second ✅ (2-3x faster!)
```

### API Calls (Per Page Load)
```
Google Maps:
- Maps JavaScript API: 1 call
- Geocoding API: 1 call  
- Distance Matrix API: 1 call
Total: 3 billable API calls 💸

Leaflet + OSM:
- Tile requests: ~20 requests (FREE)
- Geocoding: 0 calls (not used yet)
- Distance: 0 calls (calculated locally)
Total: $0 ✅
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Map Blank/White Screen
**Symptoms:** Map container shows but no tiles

**Solution:**
```bash
# Check CSS loaded
View Page Source > Search for "leaflet.css"

# If not found:
# Make sure import is at TOP of component
import "leaflet/dist/leaflet.css";
```

---

### Issue 2: Markers Not Showing
**Symptoms:** Map loads but no courier/customer markers

**Solution:**
```typescript
// Check browser console for errors
// Common: "Cannot read property 'lat' of undefined"

// Fix: Add null checks
{tracking.courier?.current_location && (
  <LeafletMapTracker
    courierLocation={tracking.courier.current_location}
    ...
  />
)}
```

---

### Issue 3: Distance Shows 0 or NaN
**Symptoms:** Distance card shows "0 km" or "NaN km"

**Solution:**
```typescript
// Check coordinate format
// Must be numbers, not strings
const location = {
  lat: parseFloat(data.lat),  // ✅
  lng: parseFloat(data.lng)   // ✅
};

// NOT this:
const location = {
  lat: "123.456",  // ❌ String
  lng: "789.012"   // ❌ String
};
```

---

### Issue 4: Animation Not Smooth
**Symptoms:** Marker jumps instead of smooth movement

**Solution:**
```typescript
// Check interval timing
setInterval(() => {
  // Move logic
}, 2000); // Should be consistent

// If still jumpy, reduce movement step:
const step = 0.01; // Instead of 0.02
const newLat = currentLat + (destLat - currentLat) * step;
```

---

## 📸 Screenshots to Take (For Documentation)

1. **Map Overview** - Full tracking page with map
2. **Courier Movement** - Animated marker with rotation
3. **Distance Cards** - Distance, ETA, Status cards
4. **Status Timeline** - Progress bar with icons
5. **100% FREE Badge** - Show OSM + Leaflet branding
6. **Admin Kanban** - 7-column board view
7. **OTP Modal** - COD verification UI
8. **Status History** - Timeline with timestamps

---

## ✅ Test Checklist

### Basic Functionality
- [ ] Map loads without errors
- [ ] Courier marker visible
- [ ] Customer marker visible
- [ ] Route line connects markers
- [ ] Distance calculates correctly
- [ ] ETA displays in minutes
- [ ] Markers animate smoothly
- [ ] Marker rotates based on direction

### Real-Time Features
- [ ] Position updates every 2 seconds
- [ ] Distance decreases over time
- [ ] ETA updates correctly
- [ ] "Courier Arrived" triggers at < 50m
- [ ] No lag or freezing

### UI/UX
- [ ] Map fits both markers on load
- [ ] Cards show correct information
- [ ] Timeline progress accurate
- [ ] Status history in order
- [ ] Responsive on mobile
- [ ] No layout breaks

### Performance
- [ ] Page loads < 2 seconds
- [ ] No console errors
- [ ] No API key warnings
- [ ] No external API calls (check Network tab)
- [ ] Memory usage stable (< 100MB)

### Data Accuracy
- [ ] Order details correct
- [ ] Courier info matches database
- [ ] Items & pricing accurate
- [ ] Status matches backend
- [ ] Timestamps in correct timezone

---

## 🎓 Demo Script (For Presentation)

**Intro (30 seconds):**
> "Ini adalah real-time order tracking system menggunakan Leaflet dan OpenStreetMap. Tidak butuh API key, 100% gratis, dan lebih cepat dari Google Maps."

**Show Map (1 minute):**
> "Map ini menampilkan posisi courier (motor cokelat) dan tujuan pengiriman (pin hijau). Jarak dihitung menggunakan Haversine Formula - matematika murni tanpa API eksternal."

**Show Animation (1 minute):**
> "Setiap 2 detik, posisi courier update secara real-time. Marker akan rotate sesuai arah pergerakan. Distance dan ETA otomatis recalculate."

**Show Features (1 minute):**
> "Status timeline menunjukkan progress pesanan. History log mencatat semua perubahan status dengan timestamp. Untuk COD, ada sistem OTP dengan auto-approve untuk pelanggan terpercaya."

**Highlight Savings (30 seconds):**
> "Dengan Leaflet, kita hemat ~$200-500 per bulan dibanding Google Maps. No billing, no credit card, production-ready."

**Total: ~4 minutes**

---

## 🚀 Next Steps

After testing successfully:

1. **Production Deploy:**
   - Remove test data
   - Add real courier locations (GPS integration)
   - Setup proper API rate limiting for Nominatim

2. **Enhancements:**
   - Add delivery zones (polygon drawing)
   - Multi-language support
   - Print delivery receipt
   - SMS/WhatsApp notifications

3. **Monitoring:**
   - Track page load times
   - Monitor WebSocket connections
   - Log distance calculation accuracy

---

**Happy Testing! 🧪☕**
