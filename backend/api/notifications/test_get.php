<?php
/**
 * Test notification endpoint
 * Visit: http://localhost/DailyCup/webapp/backend/api/notifications/test_get.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Notification System ===\n\n";

// Step 1: Load config
echo "1. Loading config...\n";
require_once __DIR__ . '/../config.php';
echo "   ✓ Config loaded\n\n";

// Step 2: Database connection
echo "2. Connecting to database...\n";
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   ✓ Database connected\n\n";
} catch (PDOException $e) {
    die("   ✗ Database error: " . $e->getMessage() . "\n");
}

// Step 3: Check notifications table
echo "3. Checking notifications table...\n";
try {
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Columns: " . implode(', ', $columns) . "\n\n";
} catch (PDOException $e) {
    die("   ✗ Table error: " . $e->getMessage() . "\n");
}

// Step 4: Load NotificationService
echo "4. Loading NotificationService...\n";
try {
    require_once __DIR__ . '/NotificationService.php';
    echo "   ✓ NotificationService loaded\n\n";
} catch (Throwable $e) {
    die("   ✗ Service error: " . $e->getMessage() . "\n");
}

// Step 5: Test NotificationService
echo "5. Testing NotificationService...\n";
try {
    $notificationService = new NotificationService($pdo);
    
    // Get user 1 notifications
    $notifications = $notificationService->getByUser(1, 10, 0, false);
    echo "   User 1 notifications: " . count($notifications) . "\n";
    
    $unreadCount = $notificationService->getUnreadCount(1);
    echo "   User 1 unread: " . $unreadCount . "\n\n";
    
    if (count($notifications) > 0) {
        echo "   First notification:\n";
        echo "   - ID: " . $notifications[0]['id'] . "\n";
        echo "   - Type: " . $notifications[0]['type'] . "\n";
        echo "   - Title: " . $notifications[0]['title'] . "\n";
        echo "   - Message: " . $notifications[0]['message'] . "\n";
    }
    
    echo "\n   ✓ NotificationService works!\n\n";
} catch (Throwable $e) {
    echo "   ✗ Service test error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 6: Test JWT
echo "6. Testing JWT...\n";
require_once __DIR__ . '/../jwt.php';

// Create test token
$testToken = JWT::generate(['user_id' => 1, 'email' => 'test@test.com']);
echo "   Test token: " . substr($testToken, 0, 50) . "...\n";

$decoded = JWT::verify($testToken);
echo "   Decoded user_id: " . ($decoded['user_id'] ?? 'MISSING') . "\n";
echo "   ✓ JWT works!\n\n";

echo "=== All tests passed! ===\n";
