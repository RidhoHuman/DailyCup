Write-Host "🔍 DailyCup Pre-Test Verification" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

$errorCount = 0

# 1. Check files
Write-Host "📁 Checking Critical Files..." -ForegroundColor Yellow
$files = @(
    "frontend/components/LeafletMapTracker.tsx",
    "frontend/lib/distance-calculator.ts",
    "frontend/lib/geocoding.ts",
    "backend/api/orders/tracking.php",
    "backend/test_order_data.sql",
    "backend/database_order_lifecycle.sql"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "  ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $file MISSING!" -ForegroundColor Red
        $errorCount++
    }
}

Write-Host ""

# 2. Check dependencies
Write-Host "📦 Checking Frontend Dependencies..." -ForegroundColor Yellow
Set-Location frontend

# Check leaflet
try {
    $leafletCheck = npm list leaflet --depth=0 2>&1 | Out-String
    if ($leafletCheck -match "leaflet@") {
        Write-Host "  ✅ Leaflet installed" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Leaflet NOT installed!" -ForegroundColor Red
        Write-Host "     Run: npm install leaflet react-leaflet" -ForegroundColor Yellow
        $errorCount++
    }
} catch {
    Write-Host "  ❌ npm error checking leaflet" -ForegroundColor Red
    $errorCount++
}

# Check react-leaflet
try {
    $reactLeafletCheck = npm list react-leaflet --depth=0 2>&1 | Out-String
    if ($reactLeafletCheck -match "react-leaflet@") {
        Write-Host "  ✅ React-Leaflet installed" -ForegroundColor Green
    } else {
        Write-Host "  ❌ React-Leaflet NOT installed!" -ForegroundColor Red
        $errorCount++
    }
} catch {
    Write-Host "  ❌ npm error checking react-leaflet" -ForegroundColor Red
    $errorCount++
}

Set-Location ..

Write-Host ""

# 3. Check servers
Write-Host "🌐 Checking Servers..." -ForegroundColor Yellow

# Check Next.js
try {
    $nextResponse = Invoke-WebRequest -Uri "http://localhost:3000" -TimeoutSec 3 -UseBasicParsing -ErrorAction Stop
    Write-Host "  ✅ Next.js running on port 3000" -ForegroundColor Green
} catch {
    Write-Host "  ⚠️  Next.js NOT running (start with: npm run dev)" -ForegroundColor Yellow
}

# Check Apache/Laragon
try {
    $apacheResponse = Invoke-WebRequest -Uri "http://localhost/phpmyadmin" -TimeoutSec 3 -UseBasicParsing -ErrorAction Stop
    Write-Host "  ✅ Apache/PHP running (Laragon active)" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Apache NOT running! Start Laragon first!" -ForegroundColor Red
    $errorCount++
}

Write-Host ""

# 4. Check TypeScript compilation
Write-Host "🔧 Checking TypeScript..." -ForegroundColor Yellow
Set-Location frontend

try {
    # Quick syntax check without full build
    $tscCheck = npx tsc --noEmit --skipLibCheck 2>&1 | Out-String
    if ($tscCheck -match "error TS") {
        Write-Host "  ❌ TypeScript errors found!" -ForegroundColor Red
        Write-Host $tscCheck -ForegroundColor Red
        $errorCount++
    } else {
        Write-Host "  ✅ TypeScript compiles successfully" -ForegroundColor Green
    }
} catch {
    Write-Host "  ⚠️  Could not run TypeScript check" -ForegroundColor Yellow
}

Set-Location ..

Write-Host ""

# 5. Check database (if MySQL CLI available)
Write-Host "🗄️  Database Check..." -ForegroundColor Yellow
try {
    # Try to connect to MySQL
    $mysqlTest = mysql -u root -e "SELECT 1" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  ✅ MySQL connection successful" -ForegroundColor Green
        
        # Check database exists
        $dbCheck = mysql -u root -e "SHOW DATABASES LIKE 'dailycup'" 2>&1 | Out-String
        if ($dbCheck -match "dailycup") {
            Write-Host "  ✅ Database 'dailycup' exists" -ForegroundColor Green
        } else {
            Write-Host "  ❌ Database 'dailycup' NOT found!" -ForegroundColor Red
            $errorCount++
        }
        
        # Check couriers table
        $courierCheck = mysql -u root dailycup -e "SHOW TABLES LIKE 'couriers'" 2>&1 | Out-String
        if ($courierCheck -match "couriers") {
            Write-Host "  ✅ Table 'couriers' exists" -ForegroundColor Green
        } else {
            Write-Host "  ⚠️  Table 'couriers' NOT found (run migration)" -ForegroundColor Yellow
        }
    } else {
        Write-Host "  ⚠️  MySQL connection failed (check Laragon)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ⚠️  MySQL CLI not available (manual check needed)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=================================" -ForegroundColor Cyan

if ($errorCount -eq 0) {
    Write-Host "✅ ALL CHECKS PASSED!" -ForegroundColor Green
    Write-Host ""
    Write-Host "🚀 You're ready to test!" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Make sure Next.js is running (npm run dev)" -ForegroundColor White
    Write-Host "2. Run: .\test_tracking.ps1" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host "⚠️  FOUND $errorCount ISSUE(S)!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Fix the ❌ errors above before testing." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Quick Fixes:" -ForegroundColor Yellow
    Write-Host "- Install dependencies: cd frontend && npm install leaflet react-leaflet" -ForegroundColor White
    Write-Host "- Start Laragon: Right-click tray icon > Start All" -ForegroundColor White
    Write-Host "- Run migration: mysql -u root -p dailycup < backend/database_order_lifecycle.sql" -ForegroundColor White
    Write-Host ""
}

Write-Host "For detailed checklist, see: PRE_TEST_CHECKLIST.md" -ForegroundColor Gray
Write-Host ""
