<?php
/**
 * Run Email Tests
 * Helper script untuk email_checklist.php
 */

require_once __DIR__ . '/../api/email/EmailService.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$testEmail = $input['email'] ?? null;

if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

// Test data
$testCustomer = [
    'name' => 'Test User',
    'email' => $testEmail,
    'address' => 'Test Address'
];

$testOrder = [
    'order_number' => 'TEST-' . time(),
    'items' => [
        ['name' => 'Espresso', 'quantity' => 2, 'price' => 25000]
    ],
    'total' => 50000,
    'subtotal' => 50000,
    'discount' => 0,
    'delivery_method' => 'takeaway',
    'payment_method' => 'test',
    'created_at' => date('Y-m-d H:i:s')
];

$testUser = [
    'name' => 'Test User',
    'email' => $testEmail
];

// Run tests
$results = [
    'email' => $testEmail,
    'order_confirmation' => false,
    'payment_confirmation' => false,
    'status_update' => false,
    'welcome' => false,
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    $results['order_confirmation'] = EmailService::sendOrderConfirmation($testOrder, $testCustomer);
} catch (Exception $e) {
    error_log("Order confirmation test failed: " . $e->getMessage());
}

try {
    $results['payment_confirmation'] = EmailService::sendPaymentConfirmation($testOrder, $testCustomer);
} catch (Exception $e) {
    error_log("Payment confirmation test failed: " . $e->getMessage());
}

try {
    $results['status_update'] = EmailService::sendStatusUpdate($testOrder, $testCustomer, 'processing');
} catch (Exception $e) {
    error_log("Status update test failed: " . $e->getMessage());
}

try {
    $results['welcome'] = EmailService::sendWelcomeEmail($testUser);
} catch (Exception $e) {
    error_log("Welcome email test failed: " . $e->getMessage());
}

echo json_encode($results);
?>
