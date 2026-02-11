# ========================================
# Quick Test Script - Order Lifecycle System
# ========================================

Write-Host "🧪 DailyCup Order Tracking Test Script" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Check Database
Write-Host "📊 Step 1: Checking Database..." -ForegroundColor Yellow
Write-Host "Run this SQL query in phpMyAdmin/MySQL:"
Write-Host ""
Write-Host "SELECT COUNT(*) as courier_count FROM couriers;" -ForegroundColor Green
Write-Host "SELECT COUNT(*) as order_count FROM orders WHERE status IN ('on_delivery', 'preparing');" -ForegroundColor Green
Write-Host ""
Write-Host "Expected: 5 couriers, at least 1 order" -ForegroundColor Gray
Write-Host ""
Read-Host "Press Enter when database is ready"

# Step 2: Start Frontend
Write-Host ""
Write-Host "🚀 Step 2: Starting Frontend Server..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Opening new terminal for frontend..." -ForegroundColor Gray

Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\frontend'; Write-Host '🎨 Starting Next.js Dev Server...' -ForegroundColor Cyan; npm run dev"

Start-Sleep -Seconds 3

Write-Host "✅ Frontend starting at http://localhost:3000" -ForegroundColor Green
Write-Host ""

# Step 3: Test Order Tracking
Write-Host "🗺️  Step 3: Test Order Tracking..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Opening browser windows..." -ForegroundColor Gray
Write-Host ""

# Wait a bit for server to start
Start-Sleep -Seconds 5

# Open tracking page with a test order ID
Write-Host "Opening Order Tracker..." -ForegroundColor Cyan
Start-Process "http://localhost:3000/track/ORD-TEST-001"

Start-Sleep -Seconds 2

# Open admin kanban
Write-Host "Opening Admin Kanban..." -ForegroundColor Cyan
Start-Process "http://localhost:3000/admin/orders/kanban"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ TEST ENVIRONMENT READY!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📍 Test URLs:" -ForegroundColor Yellow
Write-Host "   - Order Tracker: http://localhost:3000/track/ORD-TEST-001" -ForegroundColor White
Write-Host "   - Admin Kanban:  http://localhost:3000/admin/orders/kanban" -ForegroundColor White
Write-Host ""
Write-Host "🗺️  Map Features to Test:" -ForegroundColor Yellow
Write-Host "   ✓ Leaflet map loads automatically" -ForegroundColor Gray
Write-Host "   ✓ Courier marker (🏍️) animates towards destination" -ForegroundColor Gray
Write-Host "   ✓ Distance & ETA updates real-time" -ForegroundColor Gray
Write-Host "   ✓ Route line shows delivery path" -ForegroundColor Gray
Write-Host "   ✓ '100% FREE' badge displays" -ForegroundColor Gray
Write-Host ""
Write-Host "Press Ctrl+C to stop all servers" -ForegroundColor Red
Write-Host ""

# Keep script running
Read-Host "Press Enter to finish test"
