# Test CORS Configuration - PowerShell Version
# This script tests CORS headers for DailyCup API endpoints

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "DailyCup CORS Testing Script" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$NgrokUrl = if ($args.Count -gt 0) { $args[0] } else { "https://decagonal-subpolygonally-brecken.ngrok-free.dev" }
$ApiPath = "/DailyCup/webapp/backend/api"
$Origin = "https://dailycup.vercel.app"

Write-Host "Testing CORS for:" -ForegroundColor Yellow
Write-Host "  Backend: $NgrokUrl$ApiPath"
Write-Host "  Origin: $Origin"
Write-Host ""

# Function to test endpoint
function Test-Endpoint {
    param(
        [string]$Endpoint,
        [string]$Method = "POST"
    )
    
    Write-Host "----------------------------------------" -ForegroundColor Gray
    Write-Host "Testing: $Endpoint" -ForegroundColor Green
    Write-Host "Method: $Method" -ForegroundColor Green
    Write-Host ""
    
    $Url = "$NgrokUrl$ApiPath/$Endpoint"
    
    # Test OPTIONS (preflight)
    Write-Host "1. Preflight (OPTIONS) Request:" -ForegroundColor Yellow
    try {
        $response = Invoke-WebRequest -Uri $Url `
            -Method OPTIONS `
            -Headers @{
                "Origin" = $Origin
                "Access-Control-Request-Method" = $Method
                "Access-Control-Request-Headers" = "Content-Type, Authorization"
            } `
            -UseBasicParsing `
            -SkipHttpErrorCheck
        
        Write-Host "Status: $($response.StatusCode)" -ForegroundColor $(if ($response.StatusCode -eq 204) { "Green" } else { "Red" })
        Write-Host "Headers:" -ForegroundColor Cyan
        
        # Check important CORS headers
        $corsHeaders = @(
            "Access-Control-Allow-Origin",
            "Access-Control-Allow-Methods",
            "Access-Control-Allow-Headers",
            "Access-Control-Allow-Credentials",
            "Access-Control-Max-Age"
        )
        
        foreach ($header in $corsHeaders) {
            if ($response.Headers[$header]) {
                Write-Host "  ✓ $header: $($response.Headers[$header])" -ForegroundColor Green
            } else {
                Write-Host "  ✗ $header: NOT PRESENT" -ForegroundColor Red
            }
        }
    } catch {
        Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
    Write-Host "2. Actual Request:" -ForegroundColor Yellow
    
    # Test actual request
    try {
        $headers = @{
            "Origin" = $Origin
        }
        
        if ($Endpoint -eq "login.php") {
            $body = @{
                email = "test@example.com"
                password = "test123"
            } | ConvertTo-Json
            
            $response = Invoke-WebRequest -Uri $Url `
                -Method POST `
                -Headers $headers `
                -ContentType "application/json" `
                -Body $body `
                -UseBasicParsing `
                -SkipHttpErrorCheck
        } elseif ($Endpoint -eq "products.php") {
            $response = Invoke-WebRequest -Uri $Url `
                -Method GET `
                -Headers $headers `
                -UseBasicParsing `
                -SkipHttpErrorCheck
        } else {
            $response = Invoke-WebRequest -Uri $Url `
                -Method $Method `
                -Headers $headers `
                -UseBasicParsing `
                -SkipHttpErrorCheck
        }
        
        Write-Host "Status: $($response.StatusCode)" -ForegroundColor $(if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 300) { "Green" } else { "Red" })
        Write-Host "CORS Headers:" -ForegroundColor Cyan
        
        if ($response.Headers["Access-Control-Allow-Origin"]) {
            Write-Host "  ✓ Access-Control-Allow-Origin: $($response.Headers['Access-Control-Allow-Origin'])" -ForegroundColor Green
        } else {
            Write-Host "  ✗ Access-Control-Allow-Origin: NOT PRESENT" -ForegroundColor Red
        }
        
        # Show first 500 chars of response
        $content = $response.Content
        if ($content.Length -gt 500) {
            $content = $content.Substring(0, 500) + "..."
        }
        Write-Host "Response Preview:" -ForegroundColor Cyan
        Write-Host $content -ForegroundColor Gray
        
    } catch {
        Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
    Write-Host ""
}

# Test endpoints
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Starting CORS Tests..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Test-Endpoint -Endpoint "login.php" -Method "POST"
Test-Endpoint -Endpoint "products.php" -Method "GET"
Test-Endpoint -Endpoint "register.php" -Method "POST"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "CORS Testing Complete" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Expected Results:" -ForegroundColor Yellow
Write-Host "  ✓ Access-Control-Allow-Origin: $Origin or *" -ForegroundColor Green
Write-Host "  ✓ Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH" -ForegroundColor Green
Write-Host "  ✓ Access-Control-Allow-Headers: Content-Type, Authorization, ..." -ForegroundColor Green
Write-Host "  ✓ HTTP Status: 204 for OPTIONS, 200 for actual requests" -ForegroundColor Green
Write-Host ""
Write-Host "Usage:" -ForegroundColor Yellow
Write-Host '  .\test_cors.ps1                          # Use default ngrok URL' -ForegroundColor Gray
Write-Host '  .\test_cors.ps1 "https://your.ngrok.url" # Use custom ngrok URL' -ForegroundColor Gray
Write-Host ""
