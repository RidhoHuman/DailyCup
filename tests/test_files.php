<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

echo "=== TESTING API ENDPOINTS ===\n\n";

$apiTests = [
    'Cart API' => 'api/cart.php',
    'Favorites API' => 'api/favorites.php',
    'Notifications API' => 'api/notifications.php',
    'Redeem Code API' => 'api/redeem_code.php',
    'Reviews API' => 'api/reviews.php',
    'Refund API' => 'api/refund.php',
    'Track Location API' => 'api/track_location.php',
    'Update Kurir Location API' => 'api/update_kurir_location.php',
    'Get All Kurir Locations API' => 'api/get_all_kurir_locations.php'
];

foreach ($apiTests as $name => $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "✓ $name - File exists\n";
    } else {
        echo "✗ $name - FILE NOT FOUND at $file\n";
    }
}

echo "\n=== TESTING CRITICAL FILES ===\n\n";

$criticalFiles = [
    'Customer Pages' => [
        '../customer/menu.php',
        '../customer/cart.php',
        '../customer/checkout.php',
        '../customer/orders.php',
        '../customer/order_detail.php',
        '../customer/profile.php',
        '../customer/notifications.php'
    ],
    'Kurir Pages' => [
        '../kurir/login.php',
        '../kurir/index.php',
        '../kurir/history.php',
        '../kurir/profile.php',
        '../kurir/info.php'
    ],
    'Admin Pages' => [
        '../admin/index.php',
        '../admin/orders/index.php',
        '../admin/products/index.php',
        '../admin/kurir/index.php',
        '../admin/reviews/index.php',
        '../admin/returns/index.php'
    ]
];

foreach ($criticalFiles as $category => $files) {
    echo "=== $category ===\n";
    $missing = 0;
    foreach ($files as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo "  ✓ $file\n";
        } else {
            echo "  ✗ $file - MISSING\n";
            $missing++;
        }
    }
    echo $missing == 0 ? "All files present!\n\n" : "Missing $missing file(s)\n\n";
}

echo "=== TESTING CONFIGURATION ===\n\n";

// Test database connection
try {
    $db = getDB();
    echo "✓ Database connection works\n";
} catch (Exception $e) {
    echo "✗ Database connection FAILED: " . $e->getMessage() . "\n";
}

// Test constants
echo defined('SITE_URL') ? "✓ SITE_URL defined: " . SITE_URL . "\n" : "✗ SITE_URL not defined\n";
echo defined('ADMIN_EMAIL') ? "✓ ADMIN_EMAIL defined: " . ADMIN_EMAIL . "\n" : "⚠ ADMIN_EMAIL not defined\n";

// Test sessions
session_start();
echo isset($_SESSION) ? "✓ Sessions work\n" : "✗ Sessions not working\n";

echo "\n=== TEST SUMMARY ===\n";
echo "Status: READY FOR MANUAL TESTING\n";
echo "\nNext steps:\n";
echo "1. Browse to http://localhost/DailyCup\n";
echo "2. Test customer registration & login\n";
echo "3. Test placing an order\n";
echo "4. Test kurir dashboard with login 081234567890 / password123\n";
echo "5. Test admin panel\n";
