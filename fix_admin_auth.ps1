# Fix Admin Authentication Issues
# This script will:
# 1. Enable JWT debugging
# 2. Restart PHP to reload .env
# 3. Test admin authentication

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "DailyCup Admin Auth Fix" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Step 1: Enable JWT_DEBUG in all .env files
Write-Host "[1/4] Enabling JWT debug mode..." -ForegroundColor Yellow

$envFiles = @(
    ".\webapp\backend\.env",
    ".\webapp\backend\api\.env",
    ".\.env"
)

foreach ($envFile in $envFiles) {
    if (Test-Path $envFile) {
        $content = Get-Content $envFile -Raw
        
        # Add JWT_DEBUG if not exists
        if ($content -notmatch "JWT_DEBUG") {
            Add-Content -Path $envFile -Value "`nJWT_DEBUG=1"
            Write-Host "  ✓ Added JWT_DEBUG=1 to $envFile" -ForegroundColor Green
        } else {
            # Update existing JWT_DEBUG
            $content = $content -replace "JWT_DEBUG=0", "JWT_DEBUG=1"
            Set-Content -Path $envFile -Value $content
            Write-Host "  ✓ Updated JWT_DEBUG in $envFile" -ForegroundColor Green
        }
    }
}

# Step 2: Restart Laragon (Apache/Nginx + MySQL)
Write-Host "`n[2/4] Restarting Laragon services..." -ForegroundColor Yellow
Write-Host "  Please manually restart Laragon to reload PHP .env files:" -ForegroundColor Cyan
Write-Host "  1. Open Laragon" -ForegroundColor White
Write-Host "  2. Click 'Stop All'" -ForegroundColor White
Write-Host "  3. Wait 3 seconds" -ForegroundColor White
Write-Host "  4. Click 'Start All'" -ForegroundColor White
Write-Host "`n  Press Enter after restarting Laragon..." -ForegroundColor Yellow
Read-Host

# Step 3: Check if Ngrok is running
Write-Host "`n[3/4] Checking Ngrok status..." -ForegroundColor Yellow
$ngrokProcess = Get-Process ngrok -ErrorAction SilentlyContinue

if ($ngrokProcess) {
    Write-Host "  ✓ Ngrok is running" -ForegroundColor Green
    Write-Host "  Note: If you changed .env, restart Ngrok:" -ForegroundColor Cyan
    Write-Host "  ngrok http 80 --host-header=rewrite" -ForegroundColor White
} else {
    Write-Host "  ⚠ Ngrok is not running!" -ForegroundColor Red
    Write-Host "  Start it with: ngrok http 80 --host-header=rewrite" -ForegroundColor Yellow
}

# Step 4: Provide instructions to test
Write-Host "`n[4/4] Testing Instructions:" -ForegroundColor Yellow
Write-Host "  1. Open your browser DevTools (F12)" -ForegroundColor White
Write-Host "  2. Go to Application/Storage → Local Storage" -ForegroundColor White
Write-Host "  3. Delete 'dailycup-auth' key" -ForegroundColor White
Write-Host "  4. Login again at: https://dailycup.vercel.app/login" -ForegroundColor White
Write-Host "  5. Go to admin analytics: https://dailycup.vercel.app/admin/analytics" -ForegroundColor White
Write-Host "  6. Check browser Console and Network tab for errors" -ForegroundColor White
Write-Host "`n  Backend logs with JWT debug info:" -ForegroundColor Cyan
Write-Host "  - Check Laragon → Apache/Nginx error.log" -ForegroundColor White
Write-Host "  - Or: .\webapp\backend\logs\login_debug.log" -ForegroundColor White

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Additional Debugging:" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Test JWT token decoding
Write-Host "To manually test a JWT token:" -ForegroundColor Yellow
Write-Host '  $token = "eyJ0eXAi..."' -ForegroundColor Gray
Write-Host '  $payload = [System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($token.Split(".")[1]))' -ForegroundColor Gray
Write-Host '  $payload' -ForegroundColor Gray

Write-Host "`n✓ Setup complete!" -ForegroundColor Green
Write-Host "Next: Clear localStorage → Login → Test admin panel`n" -ForegroundColor Cyan
