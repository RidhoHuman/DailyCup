# Test-RemainingEndpoints.ps1

$baseUrl = "http://localhost/DailyCup/webapp/backend/api"
$customerEmail = "customer_test@dailycup.com"
$customerPassword = "password123"

function Print-Result {
    param($Title, $Success, $Message)
    if ($Success) {
        Write-Host "V $Title : $Message" -ForegroundColor Green
    } else {
        Write-Host "X $Title : $Message" -ForegroundColor Red
    }
}

Write-Host "=== DAILYCUP API TEST SUITE (REMAINING ENDPOINTS) ===" -ForegroundColor Yellow

# 1. Public Endpoints
Write-Host "`n--- 1. Testing Public Endpoints ---" -ForegroundColor Cyan

# 1.1 Categories
try {
    $cats = Invoke-RestMethod -Uri "$baseUrl/categories.php" -Method Get
    if ($cats.success) {
        Print-Result "Categories" $true "$($cats.data.Count) categories found"
    } else {
        Print-Result "Categories" $false "Success flag false"
    }
} catch {
    Print-Result "Categories" $false $_.Exception.Message
}

# 1.2 Products
try {
    $prods = Invoke-RestMethod -Uri "$baseUrl/products.php" -Method Get
    if ($prods.success) {
        Print-Result "Products" $true "$($prods.data.Count) products found"
        $firstProductId = $prods.data[0].id
        $firstProductPrice = $prods.data[0].price
        $firstProductName = $prods.data[0].name
    } else {
        Print-Result "Products" $false "Success flag false"
    }
} catch {
    Print-Result "Products" $false $_.Exception.Message
}

# 2. Customer Authentication
Write-Host "`n--- 2. Testing Customer Authentication ---" -ForegroundColor Cyan

# 2.1 Register
$regBody = @{
    name = "Test Customer"
    email = $customerEmail
    password = $customerPassword
    phone = "081234567890"
} | ConvertTo-Json

try {
    $regResponse = Invoke-RestMethod -Uri "$baseUrl/register.php" -Method Post -Body $regBody -ContentType "application/json"
    Print-Result "Registration" $true "User created or exists"
} catch {
    if ($_.Exception.Response -and $_.Exception.Response.StatusCode -eq "Conflict") {
        Print-Result "Registration" $true "User already exists (409)"
    } else {
        Print-Result "Registration" $false $_.Exception.Message
         if ($_.Exception.Response) {
                 $stream = $_.Exception.Response.GetResponseStream()
                 if ($stream) {
                     Write-Host (New-Object System.IO.StreamReader($stream)).ReadToEnd() -ForegroundColor DarkRed
                 }
            }
    }
}

# 2.2 Login
$loginBody = @{
    email = $customerEmail
    password = $customerPassword
} | ConvertTo-Json

$token = ""

try {
    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/login.php" -Method Post -Body $loginBody -ContentType "application/json"
    if ($loginResponse.token) {
        $token = $loginResponse.token
        Print-Result "Customer Login" $true "Token received"
    } else {
        Print-Result "Customer Login" $false "No token in response"
        exit
    }
} catch {
    Print-Result "Customer Login" $false $_.Exception.Message
    exit
}

$headers = @{
    Authorization = "Bearer $token"
}

# 3. Customer Features
Write-Host "`n--- 3. Testing Customer Features ---" -ForegroundColor Cyan

# 3.1 Profile
try {
    $me = Invoke-RestMethod -Uri "$baseUrl/me.php" -Headers $headers -Method Get
    if ($me.success) {
        Print-Result "Get Profile" $true "User: $($me.data.name)"
    } else {
        Print-Result "Get Profile" $false "Failed to get profile"
    }
} catch {
    Write-Host "X Get Profile : Failed $_"
    Print-Result "Get Profile" $false "Failed"
}

# 3.2 Create Order
Write-Host "`n--- 4. Testing Order System ---" -ForegroundColor Cyan

if ($firstProductId) {
    $orderBody = @{
        items = @(
            @{
                id = $firstProductId
                name = $firstProductName
                price = $firstProductPrice
                quantity = 1
            }
        )
        total = $firstProductPrice
        customer = @{
            name = "Test User"
            email = $customerEmail
            phone = "08123456789"
            address = "Test Address 123"
        }
        paymentMethod = "cod"
        deliveryMethod = "takeaway"
        notes = "Test order"
    } | ConvertTo-Json

    try {
        $orderRes = Invoke-RestMethod -Uri "$baseUrl/create_order.php" -Headers $headers -Method Post -Body $orderBody -ContentType "application/json"
        
        if ($orderRes.success) {
            Print-Result "Create Order" $true "Order created: $($orderRes.data.order_number)"
        } else {
            Print-Result "Create Order" $false "API Error: $($orderRes.message)"
        }
    } catch {
        Write-Host "X Create Order : Failed $_"
        Print-Result "Create Order" $false "Failed"
    }
}

# 3.3 List Orders
try {
    $orderRes = Invoke-RestMethod -Uri "$baseUrl/orders.php" -Headers $headers -Method Get
    if ($orderRes.success) {
        Print-Result "List Orders" $true "Found $($orderRes.data.Count) orders"
    } else {
        Print-Result "List Orders" $false "Failed to list orders"
    }
} catch {
    Print-Result "List Orders" $false "Failed"
}

# 4. Chat System
Write-Host "`n--- 5. Testing Chat System ---" -ForegroundColor Cyan

# 4.0 Create Conversation
try {
    $chatBody = @{
        subject = "Help with Order"
        message = "My order is late"
    } | ConvertTo-Json
    
    $chatRes = Invoke-RestMethod -Uri "$baseUrl/chat/conversations.php" -Headers $headers -Method Post -Body $chatBody -ContentType "application/json"
    
    if ($chatRes.success) {
        Print-Result "Create Chat" $true "ID: $($chatRes.conversation_id)"
    } else {
         Print-Result "Create Chat" $false "Failed $($chatRes.error)"
    }
} catch {
    Print-Result "Create Chat" $false "Failed $_"
}

# 4.1 Get Conversations
$convId = 0
try {
    $convs = Invoke-RestMethod -Uri "$baseUrl/chat/conversations.php" -Headers $headers -Method Get
    if ($convs.success) {
        Print-Result "Get Conversations" $true "Found $($convs.conversations.Count) conversations"
        if ($convs.conversations.Count -gt 0) {
            $convId = $convs.conversations[0].id
        }
    } else {
       Write-Host "X Get Conversations : Failed"
    }
} catch {
     Write-Host "X Get Conversations : Failed $_"
}

# 4.2 Send Message
$msgBody = @{
    conversation_id = $convId
    message = "Test message from script"
} | ConvertTo-Json

$sendUri = "$baseUrl/chat/messages.php"

try {
    $sendRes = Invoke-RestMethod -Uri $sendUri -Headers $headers -Method Post -Body $msgBody -ContentType "application/json"
    
    if ($sendRes.success) {
        Print-Result "Send Message" $true "Message sent"
    } else {
        Print-Result "Send Message" $false "Failed"
    }
} catch {
    Print-Result "Send Message" $false "Failed"
}
