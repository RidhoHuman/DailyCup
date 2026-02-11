# Test-AllFeatures.ps1

$baseUrl = "http://localhost/DailyCup/webapp/backend/api"
$adminEmail = "admin@dailycup.com"
$password = "admin123"

Write-Host "--- 1. Testing Authentication ---" -ForegroundColor Cyan
$loginUrl = "$baseUrl/login.php"
$body = @{
    email = $adminEmail
    password = $password
    role = "admin"
} | ConvertTo-Json

$token = ""

try {
    $response = Invoke-RestMethod -Uri $loginUrl -Method Post -Body $body -ContentType "application/json"
    if ($response.token) {
        $token = $response.token
        Write-Host "? Login successful" -ForegroundColor Green
    } else {
        Write-Host "? Login failed: No token received" -ForegroundColor Red
        exit
    }
} catch {
    Write-Host "? Login failed: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) { Write-Host (New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())).ReadToEnd() }
    exit
}

$headers = @{
    Authorization = "Bearer $token"
}

Write-Host "`n--- 2. Testing Admin Dashboard Stats ---" -ForegroundColor Cyan
try {
    $stats = Invoke-RestMethod -Uri "$baseUrl/admin/get_dashboard_stats.php" -Headers $headers -Method Get
    if ($stats.success) {
        Write-Host "? Stats loaded:" -ForegroundColor Green
        Write-Host "  - Revenue: $($stats.data.totalRevenue)" -ForegroundColor Gray
        Write-Host "  - Orders:  $($stats.data.totalOrders)" -ForegroundColor Gray
    } else {
        Write-Host "? API returned success=false" -ForegroundColor Red
    }
} catch {
    Write-Host "? get_dashboard_stats.php failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n--- 3. Testing Recent Orders ---" -ForegroundColor Cyan
try {
    $res = Invoke-RestMethod -Uri "$baseUrl/admin/get_recent_orders.php?limit=5" -Headers $headers -Method Get
    if ($res.success) {
        Write-Host "? Recent orders loaded: $($res.data.Count) orders found" -ForegroundColor Green
    } else {
         Write-Host "? API returned success=false" -ForegroundColor Red
    }
} catch {
    Write-Host "? get_recent_orders.php failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n--- 4. Testing Top Products ---" -ForegroundColor Cyan
try {
    $res = Invoke-RestMethod -Uri "$baseUrl/admin/get_top_products.php?limit=5" -Headers $headers -Method Get
    if ($res.success) {
        Write-Host "? Top products loaded: $($res.data.Count) found" -ForegroundColor Green
        foreach ($p in $res.data) {
            Write-Host "  - $($p.name): Sold $($p.sold)" -ForegroundColor Gray
        }
    } else {
         Write-Host "? API returned success=false" -ForegroundColor Red
    }
} catch {
    Write-Host "? get_top_products.php failed: $($_.Exception.Message)" -ForegroundColor Red
}
