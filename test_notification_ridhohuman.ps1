# Test Notification System for ridhohuman11@gmail.com
# Test notification badge sync bug fix

$API_URL = "https://api.dailycup.com"
$TEST_EMAIL = "ridhohuman11@gmail.com"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "NOTIFICATION SYSTEM TEST" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Step 1: Get user ID
Write-Host "1. Fetching user data for $TEST_EMAIL..." -ForegroundColor Yellow
$loginPayload = @{
    email = $TEST_EMAIL
    password = "your_password_here"  # Replace with actual password
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$API_URL/login.php" -Method POST -Body $loginPayload -ContentType "application/json"
    
    if ($loginResponse.success) {
        $token = $loginResponse.data.token
        $userId = $loginResponse.data.user.id
        Write-Host "✅ Login successful! User ID: $userId" -ForegroundColor Green
        Write-Host "   Token: $($token.Substring(0, 20))..." -ForegroundColor Gray
    } else {
        Write-Host "❌ Login failed: $($loginResponse.message)" -ForegroundColor Red
        exit
    }
} catch {
    Write-Host "❌ Login error: $_" -ForegroundColor Red
    exit
}

Write-Host ""

# Step 2: Check notification count
Write-Host "2. Checking notification count..." -ForegroundColor Yellow
try {
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    
    $countResponse = Invoke-RestMethod -Uri "$API_URL/notifications/count.php" -Method GET -Headers $headers
    Write-Host "✅ Unread notifications: $($countResponse.count)" -ForegroundColor Green
} catch {
    Write-Host "❌ Count check error: $_" -ForegroundColor Red
}

Write-Host ""

# Step 3: Fetch notifications
Write-Host "3. Fetching notifications list..." -ForegroundColor Yellow
try {
    $notificationsResponse = Invoke-RestMethod -Uri "$API_URL/notifications/get.php?limit=20" -Method GET -Headers $headers
    
    if ($notificationsResponse.success) {
        $notifications = $notificationsResponse.data.notifications
        $unreadCount = $notificationsResponse.data.unread_count
        
        Write-Host "✅ Total notifications: $($notifications.Count)" -ForegroundColor Green
        Write-Host "✅ Unread count from API: $unreadCount" -ForegroundColor Green
        
        Write-Host ""
        Write-Host "Recent notifications:" -ForegroundColor Cyan
        Write-Host "--------------------" -ForegroundColor Cyan
        
        foreach ($notif in $notifications | Select-Object -First 5) {
            $readStatus = if ($notif.is_read -eq 0) { "🔴 UNREAD" } else { "✅ Read" }
            Write-Host "$readStatus | $($notif.title)" -ForegroundColor White
            Write-Host "         $($notif.message)" -ForegroundColor Gray
            Write-Host "         Created: $($notif.created_at)" -ForegroundColor DarkGray
            Write-Host ""
        }
        
        # Verification
        $actualUnread = ($notifications | Where-Object { $_.is_read -eq 0 }).Count
        Write-Host ""
        Write-Host "========================================" -ForegroundColor Cyan
        Write-Host "VERIFICATION:" -ForegroundColor Cyan
        Write-Host "--------------------" -ForegroundColor Cyan
        Write-Host "Badge count (API):     $unreadCount" -ForegroundColor White
        Write-Host "Actual unread in data: $actualUnread" -ForegroundColor White
        
        if ($unreadCount -eq $actualUnread) {
            Write-Host "✅ BADGE SYNC: CORRECT!" -ForegroundColor Green
        } else {
            Write-Host "❌ BADGE SYNC: MISMATCH!" -ForegroundColor Red
            Write-Host "   This is the bug we fixed!" -ForegroundColor Yellow
        }
        
    } else {
        Write-Host "❌ Failed to fetch notifications" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Notification fetch error: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TEST COMPLETED" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
