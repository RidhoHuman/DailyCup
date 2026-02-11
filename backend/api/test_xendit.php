<?php
/**
 * Test Xendit Integration
 * 
 * This script tests the Xendit API configuration and creates a test invoice.
 * Run: http://localhost/backend/api/test_xendit.php
 */

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        putenv("$name=$value");
    }
}

header('Content-Type: application/json');

echo "<pre>";
echo "=== XENDIT INTEGRATION TEST ===\n\n";

// Step 1: Check environment variables
echo "1. Environment Configuration:\n";
$xendit_key = getenv('XENDIT_SECRET_KEY');
$callback_url = getenv('XENDIT_CALLBACK_URL');
$callback_token = getenv('XENDIT_CALLBACK_TOKEN');

if (!$xendit_key) {
    echo "   ❌ XENDIT_SECRET_KEY not found in .env\n";
    exit;
}

echo "   ✓ XENDIT_SECRET_KEY: " . substr($xendit_key, 0, 20) . "...\n";
echo "   ✓ XENDIT_CALLBACK_URL: " . ($callback_url ?: "Not set") . "\n";
echo "   ✓ XENDIT_CALLBACK_TOKEN: " . ($callback_token ? "Set" : "Not set") . "\n\n";

// Step 2: Create test invoice
echo "2. Creating Test Invoice:\n";

$test_order_id = 'TEST_' . time();
$payload = [
    'external_id' => $test_order_id,
    'amount' => 50000,
    'payer_email' => 'test@example.com',
    'description' => 'Test Invoice - DailyCup',
    'success_redirect_url' => 'http://localhost:3000/checkout/success',
    'failure_redirect_url' => 'http://localhost:3000/checkout/payment?failed=1'
];

if ($callback_url) {
    $full_callback = $callback_url;
    if ($callback_token) {
        $full_callback .= (strpos($callback_url, '?') === false) ? '?token=' . urlencode($callback_token) : '&token=' . urlencode($callback_token);
    }
    $payload['callback_url'] = $full_callback;
}

echo "   Request Payload:\n";
echo "   " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Make API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.xendit.co/v2/invoices');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($xendit_key . ':')
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "3. API Response:\n";
echo "   HTTP Code: " . $http_code . "\n";

if ($curl_error) {
    echo "   ❌ cURL Error: " . $curl_error . "\n";
    exit;
}

if ($http_code >= 200 && $http_code < 300) {
    $json = json_decode($response, true);
    echo "   ✓ Success!\n\n";
    echo "   Invoice Details:\n";
    echo "   - ID: " . ($json['id'] ?? 'N/A') . "\n";
    echo "   - External ID: " . ($json['external_id'] ?? 'N/A') . "\n";
    echo "   - Amount: Rp " . number_format($json['amount'] ?? 0) . "\n";
    echo "   - Status: " . ($json['status'] ?? 'N/A') . "\n";
    echo "   - Invoice URL: " . ($json['invoice_url'] ?? 'N/A') . "\n\n";
    
    echo "   🔗 Test Payment:\n";
    echo "   " . ($json['invoice_url'] ?? 'N/A') . "\n\n";
    
    echo "   Full Response:\n";
    echo "   " . json_encode($json, JSON_PRETTY_PRINT) . "\n";
    
} else {
    echo "   ❌ API Error (HTTP $http_code):\n";
    echo "   " . $response . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>";
?>
