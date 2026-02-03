# Quick Deploy Script
# Run this to commit and push all fixes to trigger Vercel deployment

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "DailyCup - Quick Deploy" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Check git status
Write-Host "Checking git status..." -ForegroundColor Yellow
git status --short

Write-Host ""
Write-Host "Files to be committed:" -ForegroundColor Yellow
Write-Host "  ✅ frontend/lib/api-client.ts - Add ngrok bypass header" -ForegroundColor Green
Write-Host "  ✅ frontend/utils/api.ts - Update ngrok detection" -ForegroundColor Green
Write-Host "  ✅ frontend/app/admin/(auth)/login/page.tsx - Add ngrok bypass" -ForegroundColor Green
Write-Host "  ✅ frontend/next.config.ts - Auto-fix double https" -ForegroundColor Green
Write-Host "  ✅ Documentation files" -ForegroundColor Green
Write-Host ""

# Confirm
$confirm = Read-Host "Commit and push changes? (y/n)"

if ($confirm -ne 'y') {
    Write-Host "Cancelled." -ForegroundColor Red
    exit
}

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Committing Changes..." -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Add all changes
git add .

# Commit
git commit -m "fix: Add ngrok bypass header and sanitize double https in API URL

- Add ngrok-skip-browser-warning header to all API requests
- Auto-detect and sanitize double https:// in environment variables
- Update CORS to allow ngrok bypass header
- Fix admin login to include ngrok bypass
- Add comprehensive documentation and testing tools

Fixes CORS and ngrok browser warning issues"

Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Pushing to Remote..." -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Push
git push origin main

Write-Host ""
Write-Host "=========================================" -ForegroundColor Green
Write-Host "✅ Deployment Triggered!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Go to Vercel Dashboard:" -ForegroundColor White
Write-Host "   https://vercel.com/dashboard" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Watch deployment progress" -ForegroundColor White
Write-Host ""
Write-Host "3. After deployment completes, test login:" -ForegroundColor White
Write-Host "   https://dailycup.vercel.app/login" -ForegroundColor Cyan
Write-Host ""
Write-Host "4. Open DevTools → Network tab" -ForegroundColor White
Write-Host ""
Write-Host "5. Check request headers include:" -ForegroundColor White
Write-Host "   ngrok-skip-browser-warning: 69420" -ForegroundColor Green
Write-Host ""
Write-Host "6. Verify response is JSON (not HTML)" -ForegroundColor White
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Estimated deployment time: 2-5 minutes" -ForegroundColor Gray
Write-Host ""
