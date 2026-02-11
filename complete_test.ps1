Write-Host "DailyCup Complete API Test" -ForegroundColor Cyan
Write-Host "==========================" -ForegroundColor Cyan
Write-Host ""

$BackendUrl = "https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api"
$headers = @{ "ngrok-skip-browser-warning" = "true" }

# Test 1: Products
Write-Host "[1/5] Products API..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$BackendUrl/products.php" -Method GET -Headers $headers -UseBasicParsing -TimeoutSec 10
    $data = $response.Content | ConvertFrom-Json
    Write-Host "  ✓ Status: 200 | Products: $($data.data.Count)" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 2: Categories
Write-Host "[2/5] Categories API..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$BackendUrl/categories.php" -Method GET -Headers $headers -UseBasicParsing -TimeoutSec 10
    $data = $response.Content | ConvertFrom-Json
    Write-Host "  ✓ Status: 200 | Categories: $($data.data.Count)" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 3: Flash Sales
Write-Host "[3/5] Flash Sales API..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$BackendUrl/flash_sales.php" -Method GET -Headers $headers -UseBasicParsing -TimeoutSec 10
    $data = $response.Content | ConvertFrom-Json
    Write-Host "  ✓ Status: 200 | Active Sales: $($data.data.Count)" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 4: Login with test credentials
Write-Host "[4/5] Login API..." -ForegroundColor Yellow
try {
    $loginData = @{
        email = "test@example.com"
        password = "password123"
    } | ConvertTo-Json
    
    $loginHeaders = @{
        "ngrok-skip-browser-warning" = "true"
        "Content-Type" = "application/json"
    }
    
    $response = Invoke-WebRequest -Uri "$BackendUrl/login.php" -Method POST -Headers $loginHeaders -Body $loginData -UseBasicParsing -TimeoutSec 10
    $data = $response.Content | ConvertFrom-Json
    
    if ($data.success) {
        Write-Host "  ✓ Login successful | User: $($data.data.user.name)" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Login failed (expected for test user)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 5: CORS Headers
Write-Host "[5/5] CORS Headers..." -ForegroundColor Yellow
try {
    $corsHeaders = @{
        "Origin" = "https://dailycup.vercel.app"
        "ngrok-skip-browser-warning" = "true"
    }
    $response = Invoke-WebRequest -Uri "$BackendUrl/products.php" -Method GET -Headers $corsHeaders -UseBasicParsing -TimeoutSec 10
    
    $corsHeader = $response.Headers["Access-Control-Allow-Origin"]
    if ($corsHeader) {
        Write-Host "  ✓ CORS Enabled: $corsHeader" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ CORS Header Not Found" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ✗ Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "==========================" -ForegroundColor Cyan
Write-Host "All Tests Completed!" -ForegroundColor Green
Write-Host "==========================" -ForegroundColor Cyan
