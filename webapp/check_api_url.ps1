# Quick Fix - Check and Fix NEXT_PUBLIC_API_URL
# This script helps validate the API URL format

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "DailyCup - API URL Validator & Fixer" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Read current value
$currentUrl = $env:NEXT_PUBLIC_API_URL
if (-not $currentUrl) {
    $currentUrl = "NOT SET"
}

Write-Host "Current NEXT_PUBLIC_API_URL:" -ForegroundColor Yellow
Write-Host "  $currentUrl" -ForegroundColor White
Write-Host ""

# Check for common issues
$hasIssue = $false

# Check 1: Double https://
if ($currentUrl -match "https://https") {
    Write-Host "❌ ISSUE DETECTED: Double https:// found!" -ForegroundColor Red
    Write-Host "   This will cause ERR_NAME_NOT_RESOLVED error" -ForegroundColor Red
    $hasIssue = $true
}

# Check 2: Malformed https//
if ($currentUrl -match "https//[^/]") {
    Write-Host "❌ ISSUE DETECTED: Malformed https// (missing colon)" -ForegroundColor Red
    Write-Host "   This will cause ERR_NAME_NOT_RESOLVED error" -ForegroundColor Red
    $hasIssue = $true
}

# Check 3: Missing protocol
if ($currentUrl -ne "NOT SET" -and -not ($currentUrl -match "^https?://")) {
    Write-Host "⚠️  WARNING: No http:// or https:// protocol found" -ForegroundColor Yellow
    Write-Host "   URL should start with http:// or https://" -ForegroundColor Yellow
}

# Check 4: Trailing slash
if ($currentUrl -match "/$") {
    Write-Host "⚠️  WARNING: URL ends with trailing slash /" -ForegroundColor Yellow
    Write-Host "   This might cause double slashes in API calls" -ForegroundColor Yellow
}

Write-Host ""

if ($hasIssue) {
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host "FIXING URL..." -ForegroundColor Yellow
    Write-Host "=========================================" -ForegroundColor Red
    Write-Host ""
    
    # Fix double https://
    $fixedUrl = $currentUrl -replace "^(https?://)(https?:?//)+", '$1'
    
    # Remove trailing slash
    $fixedUrl = $fixedUrl -replace "/$", ""
    
    Write-Host "Fixed URL:" -ForegroundColor Green
    Write-Host "  $fixedUrl" -ForegroundColor White
    Write-Host ""
    
    Write-Host "=========================================" -ForegroundColor Cyan
    Write-Host "ACTION REQUIRED" -ForegroundColor Cyan
    Write-Host "=========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Please update your Vercel Environment Variable:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "1. Go to: https://vercel.com → Your Project → Settings → Environment Variables" -ForegroundColor White
    Write-Host "2. Find: NEXT_PUBLIC_API_URL" -ForegroundColor White
    Write-Host "3. Click Edit and change the value to:" -ForegroundColor White
    Write-Host ""
    Write-Host "   $fixedUrl" -ForegroundColor Green
    Write-Host ""
    Write-Host "4. Click Save" -ForegroundColor White
    Write-Host "5. Redeploy your application" -ForegroundColor White
    Write-Host ""
    
} else {
    Write-Host "✅ URL format looks correct!" -ForegroundColor Green
    Write-Host ""
}

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Common Ngrok URL Patterns" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ CORRECT Examples:" -ForegroundColor Green
Write-Host "   https://abc-xyz-123.ngrok-free.dev/DailyCup/webapp/backend/api" -ForegroundColor White
Write-Host "   https://abc-xyz-123.ngrok.io/api" -ForegroundColor White
Write-Host "   http://localhost/DailyCup/webapp/backend/api" -ForegroundColor White
Write-Host ""
Write-Host "❌ WRONG Examples:" -ForegroundColor Red
Write-Host "   https://https//abc-xyz-123.ngrok-free.dev/api" -ForegroundColor DarkGray
Write-Host "   https//abc-xyz-123.ngrok-free.dev/api" -ForegroundColor DarkGray
Write-Host "   https://abc-xyz-123.ngrok-free.dev/api/" -ForegroundColor DarkGray
Write-Host ""

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Testing Tips" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Test your ngrok URL in browser first:" -ForegroundColor Yellow
Write-Host "   https://YOUR-NGROK-URL/DailyCup/webapp/backend/api/products.php" -ForegroundColor White
Write-Host ""
Write-Host "If you see product JSON, the URL is correct!" -ForegroundColor Green
Write-Host "Then use that EXACT URL (without products.php) as NEXT_PUBLIC_API_URL" -ForegroundColor Green
Write-Host ""
