# ✅ Pre-Test Checklist - Order Tracking System

Sebelum mulai testing, pastikan semua komponen ini sudah siap untuk menghindari error di tengah testing.

---

## 📋 1. Database Check

### A. Migration Status
```sql
-- Check table 'couriers' exists
SHOW TABLES LIKE 'couriers';

-- Check table 'order_status_log' exists
SHOW TABLES LIKE 'order_status_log';

-- Check table 'cod_verifications' exists
SHOW TABLES LIKE 'cod_verifications';

-- Check orders.status has 7 values
SHOW COLUMNS FROM orders LIKE 'status';
-- Expected ENUM: 'pending_payment','waiting_confirmation','queueing','preparing','on_delivery','completed','cancelled'
```

**Action if FAIL:**
```bash
mysql -u root -p dailycup < backend/database_order_lifecycle.sql
```

---

### B. Sample Data
```sql
-- Check 5 couriers exist
SELECT COUNT(*) as courier_count FROM couriers;
-- Expected: 5

-- Check test order exists
SELECT * FROM orders WHERE order_id = 'ORD-TEST-001';
-- Expected: 1 row
```

**Action if FAIL:**
```bash
mysql -u root -p dailycup < backend/test_order_data.sql
```

---

### C. Data Integrity
```sql
-- Check courier locations are set
SELECT id, name, current_location_lat, current_location_lng 
FROM couriers 
WHERE current_location_lat IS NULL;
-- Expected: 0 rows (all couriers must have location)

-- Check order status is valid
SELECT order_id, status FROM orders 
WHERE status NOT IN ('pending_payment','waiting_confirmation','queueing','preparing','on_delivery','completed','cancelled');
-- Expected: 0 rows
```

**Action if FAIL:**
```sql
-- Fix courier locations
UPDATE couriers 
SET current_location_lat = -6.200000, current_location_lng = 106.816666 
WHERE current_location_lat IS NULL;

-- Fix invalid status
UPDATE orders SET status = 'pending_payment' WHERE status NOT IN (...);
```

---

## 📦 2. Dependencies Check

### A. Frontend Packages
```bash
cd frontend

# Check Leaflet installed
npm list leaflet
# Expected: leaflet@1.9.4

# Check React-Leaflet installed
npm list react-leaflet
# Expected: react-leaflet@4.2.1

# Check TypeScript types
npm list @types/leaflet
# Expected: @types/leaflet@1.9.x
```

**Action if FAIL:**
```bash
npm install leaflet react-leaflet
npm install -D @types/leaflet
```

---

### B. Backend Packages (Optional - for WebSocket)
```bash
cd backend

# Check Ratchet installed
composer show cboden/ratchet
# Expected: cboden/ratchet 0.4.x
```

**Action if FAIL:**
```bash
composer require cboden/ratchet
```

---

## 🔧 3. TypeScript Build Check

```bash
cd frontend

# Run TypeScript check
npx tsc --noEmit

# Expected: No errors
```

**Common Errors & Fixes:**

❌ **Error: Property 'image' is missing**
```typescript
// Wrong:
setFormData({ name: "", description: "" });

// Correct:
setFormData({ name: "", description: "", image: null });
```

❌ **Error: Cannot find module 'leaflet'**
```bash
npm install leaflet react-leaflet
```

❌ **Error: Module '"leaflet"' has no exported member**
```bash
npm install -D @types/leaflet
```

---

## 🌐 4. API Endpoint Check

Test backend APIs are accessible:

```powershell
# Test tracking endpoint
curl http://localhost/DailyCup/webapp/backend/api/orders/tracking.php?order_id=ORD-TEST-001

# Expected: JSON response with order data
```

**Check Laravel/Apache is running:**
```bash
# Check Laragon is running
# Green tray icon = running
# Gray tray icon = stopped

# Start Laragon if needed
# Right-click Laragon tray icon > Start All
```

---

## 📁 5. File Structure Check

Pastikan semua file tracking ada:

```
✅ frontend/components/LeafletMapTracker.tsx
✅ frontend/lib/distance-calculator.ts
✅ frontend/lib/geocoding.ts
✅ frontend/app/track/[order_id]/page.tsx
✅ frontend/app/admin/(panel)/orders/kanban/page.tsx
✅ backend/api/orders/tracking.php
✅ backend/api/orders/update_status.php
✅ backend/api/orders/cod_otp.php
✅ backend/database_order_lifecycle.sql
✅ backend/test_order_data.sql
```

**Check Command:**
```powershell
# Check files exist
Test-Path frontend/components/LeafletMapTracker.tsx
Test-Path frontend/lib/distance-calculator.ts
Test-Path backend/api/orders/tracking.php
```

---

## 🔐 6. Environment Variables

### Frontend (.env.local)
```bash
cd frontend
cat .env.local

# Should contain:
NEXT_PUBLIC_API_URL=http://localhost/DailyCup/webapp/backend/api
```

**Action if missing:**
```bash
echo "NEXT_PUBLIC_API_URL=http://localhost/DailyCup/webapp/backend/api" > .env.local
```

---

### Backend (database.php)
```bash
cat backend/config/database.php

# Check credentials match Laragon:
# host: localhost
# user: root
# password: (empty or your password)
# database: dailycup
```

---

## 🚀 7. Server Check

### A. Laragon Status
```
✅ Apache: Running (port 80)
✅ MySQL: Running (port 3306)
✅ phpMyAdmin: Accessible at http://localhost/phpmyadmin
```

**Action if FAIL:**
- Right-click Laragon tray icon
- Click "Start All"
- Wait for green icon

---

### B. Frontend Dev Server
```bash
cd frontend
npm run dev

# Expected output:
# ▲ Next.js 16.1.6 (Turbopack)
# ✓ Ready on http://localhost:3000
```

**Check Accessibility:**
```
http://localhost:3000 → Should load
http://localhost:3000/track/ORD-TEST-001 → Should load (might be empty before test data)
```

---

## 🧪 8. Quick API Tests

### Test 1: Tracking API
```powershell
# PowerShell
Invoke-WebRequest -Uri "http://localhost/DailyCup/webapp/backend/api/orders/tracking.php?order_id=ORD-TEST-001" | Select-Object -Expand Content

# Expected: JSON with order data
```

### Test 2: Orders List
```powershell
$headers = @{"Authorization" = "Bearer YOUR_ADMIN_TOKEN"}
Invoke-WebRequest -Uri "http://localhost/DailyCup/webapp/backend/api/orders.php" -Headers $headers | Select-Object -Expand Content

# Expected: JSON array of orders
```

---

## 🎨 9. Browser Check

**Recommended Browser:** Chrome/Edge (untuk DevTools terbaik)

**Extensions to Disable (might interfere):**
- ❌ Ad blockers
- ❌ Script blockers
- ❌ VPN extensions

**Open DevTools (F12):**
```
✅ Console tab → No errors
✅ Network tab → Clear
✅ Application tab → LocalStorage accessible
```

---

## 📊 10. Memory & Resources

**Minimum Requirements:**
- RAM: 4GB available
- Disk: 500MB free (for logs, cache)
- CPU: Not at 100% usage

**Check Task Manager:**
```
✅ Node.js process: < 500MB RAM
✅ Apache: < 100MB RAM
✅ MySQL: < 200MB RAM
```

---

## ✅ Final Verification Script

Run this PowerShell script to check everything:

```powershell
Write-Host "🔍 DailyCup Pre-Test Verification" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# 1. Check files
Write-Host "📁 Checking Files..." -ForegroundColor Yellow
$files = @(
    "frontend/components/LeafletMapTracker.tsx",
    "frontend/lib/distance-calculator.ts",
    "backend/api/orders/tracking.php",
    "backend/test_order_data.sql"
)
foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "  ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $file MISSING!" -ForegroundColor Red
    }
}

Write-Host ""

# 2. Check dependencies
Write-Host "📦 Checking Dependencies..." -ForegroundColor Yellow
cd frontend
$leaflet = npm list leaflet --depth=0 2>$null
if ($leaflet -match "leaflet@") {
    Write-Host "  ✅ Leaflet installed" -ForegroundColor Green
} else {
    Write-Host "  ❌ Leaflet NOT installed!" -ForegroundColor Red
}

Write-Host ""

# 3. Check servers
Write-Host "🌐 Checking Servers..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:3000" -TimeoutSec 2 -UseBasicParsing -ErrorAction SilentlyContinue
    Write-Host "  ✅ Next.js running on port 3000" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Next.js NOT running!" -ForegroundColor Red
}

try {
    $response = Invoke-WebRequest -Uri "http://localhost/phpmyadmin" -TimeoutSec 2 -UseBasicParsing -ErrorAction SilentlyContinue
    Write-Host "  ✅ Apache/PHP running" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Apache NOT running!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "✅ Verification Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Fix any ❌ errors above" -ForegroundColor White
Write-Host "2. Run: .\test_tracking.ps1" -ForegroundColor White
Write-Host ""
```

---

## 🚦 Pre-Test Status

Run through this checklist:

- [ ] Database migration completed
- [ ] Test order data inserted
- [ ] Leaflet packages installed
- [ ] TypeScript compiles without errors
- [ ] Laragon Apache + MySQL running
- [ ] Next.js dev server running (port 3000)
- [ ] Backend API accessible (tracking endpoint responds)
- [ ] Browser DevTools shows no console errors
- [ ] All required files exist
- [ ] Environment variables configured

**Status: ALL GREEN? → Proceed to testing! 🚀**

**Status: ANY RED? → Fix issues first! ⚠️**

---

## 🆘 Quick Troubleshooting

### "npm run dev" fails
```bash
# Clear cache
rm -rf .next
rm -rf node_modules/.cache

# Reinstall
npm install

# Try again
npm run dev
```

---

### TypeScript errors persist
```bash
# Check tsconfig.json exists
cat tsconfig.json

# Force rebuild
npm run build

# If still fails, check error message and fix manually
```

---

### Database connection fails
```sql
-- Test MySQL connection
mysql -u root -p

-- Check database exists
SHOW DATABASES LIKE 'dailycup';

-- Check user permissions
SHOW GRANTS FOR 'root'@'localhost';
```

---

### API returns 404
```bash
# Check .htaccess in backend/api/
cat backend/api/.htaccess

# Check Apache mod_rewrite enabled
# Laragon: Should be enabled by default

# Test direct PHP access
curl http://localhost/DailyCup/webapp/backend/api/orders/tracking.php?order_id=ORD-TEST-001
```

---

**Once ALL checks pass, proceed with:**
```powershell
.\test_tracking.ps1
```

Good luck! 🍀
