<?php
/**
 * Email Test Script
 * 
 * Simple script to test email sending functionality
 * Usage: php test_email.php
 */

require_once __DIR__ . '/../api/email/EmailService.php';

echo "=== DailyCup Email Test ===\n\n";

// Test data
$testCustomer = [
    'name' => 'Test Customer',
    'email' => 'test@example.com', // Change this to your test email
    'address' => 'Jl. Test No. 123, Jakarta'
];

$testOrder = [
    'order_number' => 'ORD-TEST-' . time(),
    'items' => [
        [
            'name' => 'Espresso',
            'quantity' => 2,
            'price' => 25000
        ],
        [
            'name' => 'Cappuccino',
            'quantity' => 1,
            'price' => 30000
        ]
    ],
    'total' => 80000,
    'subtotal' => 80000,
    'discount' => 0,
    'delivery_method' => 'takeaway',
    'payment_method' => 'midtrans',
    'created_at' => date('Y-m-d H:i:s')
];

// Test 1: Order Confirmation Email
echo "1. Testing Order Confirmation Email...\n";
try {
    $result = EmailService::sendOrderConfirmation($testOrder, $testCustomer);
    echo $result ? "   ✅ Order confirmation sent!\n" : "   ❌ Failed to send\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Payment Confirmation Email
echo "2. Testing Payment Confirmation Email...\n";
try {
    $result = EmailService::sendPaymentConfirmation($testOrder, $testCustomer);
    echo $result ? "   ✅ Payment confirmation sent!\n" : "   ❌ Failed to send\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Status Update Email
echo "3. Testing Status Update Email...\n";
try {
    $result = EmailService::sendStatusUpdate($testOrder, $testCustomer, 'processing');
    echo $result ? "   ✅ Status update sent!\n" : "   ❌ Failed to send\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Welcome Email
echo "4. Testing Welcome Email...\n";
try {
    $testUser = [
        'name' => 'New User',
        'email' => 'newuser@example.com'
    ];
    $result = EmailService::sendWelcomeEmail($testUser);
    echo $result ? "   ✅ Welcome email sent!\n" : "   ❌ Failed to send\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== Test Complete ===\n";
echo "\nNote: Check your email at: " . $testCustomer['email'] . "\n";
echo "If emails not received, check:\n";
echo "1. SMTP_ENABLED in .env is set to 'true'\n";
echo "2. PHP mail() function is configured on your server\n";
echo "3. Spam/Junk folder\n";
echo "4. Server error logs\n";
?>
