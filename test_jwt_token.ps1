# Test Admin Authentication and JWT Token
# This script tests the JWT token stored in browser and backend API

param(
    [string]$NgrokUrl = "https://decagonal-subpolygonally-brecken.ngrok-free.dev",
    [string]$Token = ""
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DailyCup JWT Token Test" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# If token not provided, ask user
if ([string]::IsNullOrEmpty($Token)) {
    Write-Host "To get your token:" -ForegroundColor Yellow
    Write-Host "1. Open browser DevTools (F12) on https://dailycup.vercel.app" -ForegroundColor White
    Write-Host "2. Go to Application → Local Storage → dailycup-auth" -ForegroundColor White
    Write-Host "3. Copy the 'token' value (eyJ0eXAi...)" -ForegroundColor White
    Write-Host "`nPaste your JWT token here (or press Enter to test backend debug endpoint only):" -ForegroundColor Yellow
    $Token = Read-Host
}

Write-Host "`n[1/3] Testing Backend Debug Endpoint..." -ForegroundColor Yellow
Write-Host "URL: $NgrokUrl/DailyCup/webapp/backend/api/test_jwt.php" -ForegroundColor Gray

try {
    $debugUrl = "$NgrokUrl/DailyCup/webapp/backend/api/test_jwt.php"
    
    if ([string]::IsNullOrEmpty($Token)) {
        # Test without token
        $response = Invoke-RestMethod -Uri $debugUrl -Method GET -ErrorAction Stop
    } else {
        # Test with token
        $headers = @{
            "Authorization" = "Bearer $Token"
        }
        $response = Invoke-RestMethod -Uri $debugUrl -Method GET -Headers $headers -ErrorAction Stop
    }
    
    Write-Host "`n✓ Backend Response:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 10 | Write-Host
    
    if ($response.jwt_verification.success -eq $true) {
        Write-Host "`n✓✓✓ JWT Token is VALID!" -ForegroundColor Green
        Write-Host "User: $($response.jwt_verification.user.name) ($($response.jwt_verification.user.role))" -ForegroundColor Green
    } elseif ($null -ne $response.jwt_verification) {
        Write-Host "`n✗✗✗ JWT Token is INVALID!" -ForegroundColor Red
        Write-Host "Reason: $($response.jwt_verification.message)" -ForegroundColor Red
    }
    
} catch {
    Write-Host "✗ Error: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}

# Test Analytics API if token provided
if (-not [string]::IsNullOrEmpty($Token)) {
    Write-Host "`n[2/3] Testing Analytics API..." -ForegroundColor Yellow
    Write-Host "URL: $NgrokUrl/DailyCup/webapp/backend/api/admin/analytics.php?action=summary" -ForegroundColor Gray
    
    try {
        $analyticsUrl = "$NgrokUrl/DailyCup/webapp/backend/api/admin/analytics.php?action=summary"
        $headers = @{
            "Authorization" = "Bearer $Token"
        }
        
        $analyticsResponse = Invoke-RestMethod -Uri $analyticsUrl -Method GET -Headers $headers -ErrorAction Stop
        
        Write-Host "✓ Analytics API Response:" -ForegroundColor Green
        $analyticsResponse | ConvertTo-Json -Depth 5 | Write-Host
        
        if ($analyticsResponse.success -eq $true) {
            Write-Host "`n✓✓✓ Analytics API is WORKING!" -ForegroundColor Green
        } else {
            Write-Host "`n✗✗✗ Analytics API returned error!" -ForegroundColor Red
        }
        
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "✗ Error: HTTP $statusCode - $($_.Exception.Message)" -ForegroundColor Red
        
        if ($statusCode -eq 403) {
            Write-Host "`nTroubleshooting 403 Forbidden:" -ForegroundColor Yellow
            Write-Host "- Token signature mismatch (JWT_SECRET different)" -ForegroundColor White
            Write-Host "- User role is not 'admin'" -ForegroundColor White
            Write-Host "- Token expired" -ForegroundColor White
            Write-Host "`nSolution: Restart Laragon and login again to get fresh token" -ForegroundColor Cyan
        } elseif ($statusCode -eq 401) {
            Write-Host "`nTroubleshooting 401 Unauthorized:" -ForegroundColor Yellow
            Write-Host "- Authorization header not sent properly" -ForegroundColor White
            Write-Host "- Token format invalid" -ForegroundColor White
            Write-Host "`nSolution: Check token format (should start with eyJ...)" -ForegroundColor Cyan
        }
    }
} else {
    Write-Host "`n[2/3] Skipping Analytics API test (no token provided)" -ForegroundColor Gray
}

Write-Host "`n[3/3] Recommendations:" -ForegroundColor Yellow

if ([string]::IsNullOrEmpty($Token)) {
    Write-Host "→ Provide a token to test full authentication flow" -ForegroundColor White
    Write-Host "→ Run: .\test_jwt_token.ps1 -Token 'eyJ0eXAi...'" -ForegroundColor Gray
} else {
    Write-Host "→ Check backend error logs for JWT debug messages:" -ForegroundColor White
    Write-Host "  - Laragon → Menu → Apache → error.log (or Nginx → error.log)" -ForegroundColor Gray
    Write-Host "  - Look for lines containing 'JWT:DEBUG'" -ForegroundColor Gray
    Write-Host "`n→ If token is invalid:" -ForegroundColor White
    Write-Host "  1. Stop Laragon services" -ForegroundColor Gray
    Write-Host "  2. Wait 3 seconds" -ForegroundColor Gray
    Write-Host "  3. Start Laragon services" -ForegroundColor Gray
    Write-Host "  4. Clear browser localStorage" -ForegroundColor Gray
    Write-Host "  5. Login again at https://dailycup.vercel.app/login" -ForegroundColor Gray
    Write-Host "  6. Run this test again with new token" -ForegroundColor Gray
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Test Complete!" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan
