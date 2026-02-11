Write-Host "DailyCup Quick Test" -ForegroundColor Cyan
Write-Host "===================" -ForegroundColor Cyan
Write-Host ""

$BackendUrl = "https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api"

Write-Host "Testing Products API..." -ForegroundColor Yellow
try {
    $headers = @{ "ngrok-skip-browser-warning" = "true" }
    $response = Invoke-WebRequest -Uri "$BackendUrl/products.php" -Method GET -Headers $headers -UseBasicParsing -TimeoutSec 10
    $data = $response.Content | ConvertFrom-Json
    
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Success: $($data.success)" -ForegroundColor Green
    Write-Host "Products: $($data.data.Count)" -ForegroundColor Cyan
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Testing Categories API..." -ForegroundColor Yellow
try {
    $headers = @{ "ngrok-skip-browser-warning" = "true" }
    $response = Invoke-WebRequest -Uri "$BackendUrl/categories.php" -Method GET -Headers $headers -UseBasicParsing -TimeoutSec 10
    $data = $response.Content | ConvertFrom-Json
    
    Write-Host "Status: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Success: $($data.success)" -ForegroundColor Green
    Write-Host "Categories: $($data.data.Count)" -ForegroundColor Cyan
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Test Complete!" -ForegroundColor Cyan
