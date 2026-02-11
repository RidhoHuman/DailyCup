<?php
/**
 * Debug Create Order - Check Xendit Integration
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<pre>";
echo "=== CREATE ORDER DEBUG ===\n\n";

// 1. Check environment
echo "1. Environment Variables:\n";
$xendit_key = getenv('XENDIT_SECRET_KEY');
echo "   XENDIT_SECRET_KEY: " . ($xendit_key ? substr($xendit_key, 0, 20) . "... (length: " . strlen($xendit_key) . ")" : "NOT SET") . "\n";
echo "   First 10 chars: " . substr($xendit_key, 0, 10) . "\n";
echo "   Starts with 'xnd_': " . (strpos($xendit_key, 'xnd_') === 0 ? "YES" : "NO") . "\n\n";

// 2. Test Xendit API call
if (!$xendit_key) {
    echo "❌ XENDIT_SECRET_KEY not found!\n";
    echo "   This is why mock payment is used.\n";
    exit;
}

echo "2. Testing Xendit Invoice Creation:\n";

$test_order_id = 'DEBUG_' . time();
$payload = [
    'external_id' => $test_order_id,
    'amount' => 53000,
    'payer_email' => 'test@example.com',
    'description' => 'Debug Test Order',
    'success_redirect_url' => 'http://localhost:3000/checkout/success',
    'failure_redirect_url' => 'http://localhost:3000/checkout/payment?failed=1'
];

echo "   Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

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

echo "3. Xendit API Response:\n";
echo "   HTTP Code: $http_code\n";

if ($curl_error) {
    echo "   ❌ cURL Error: $curl_error\n";
} else if ($http_code >= 200 && $http_code < 300) {
    $json = json_decode($response, true);
    echo "   ✅ SUCCESS!\n\n";
    echo "   Invoice URL: " . ($json['invoice_url'] ?? 'N/A') . "\n\n";
    echo "   Full Response:\n";
    echo "   " . json_encode($json, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "   ❌ API ERROR:\n";
    echo "   Response: $response\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "</pre>";
?>
