# Simple login test script
$body = '{"email":"admin@dailycup.com","password":"admin123"}'

Write-Host "Testing login..." -ForegroundColor Cyan

try {
    $response = Invoke-WebRequest `
        -Uri "https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api/login.php" `
        -Method POST `
        -ContentType "application/json" `
        -Headers @{"ngrok-skip-browser-warning"="69420"} `
        -Body $body `
        -UseBasicParsing
    
    Write-Host "SUCCESS - Status: $($response.StatusCode)" -ForegroundColor Green
    $response.Content
    
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $result = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($result)
        Write-Host "Response: $($reader.ReadToEnd())" -ForegroundColor Yellow
    }
}
