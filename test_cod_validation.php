<?php
/**
 * Test COD Validation API
 */

$apiUrl = 'http://localhost/DailyCup/webapp/backend/api/validate_cod.php';

$testData = [
    'user_id' => 1,
    'order_amount' => 45000,
    'delivery_distance' => 3.5,
    'delivery_address' => 'Jl. Sudirman No. 123, Jakarta'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== COD VALIDATION TEST ===\n";
echo "HTTP Code: $httpCode\n";
echo "Raw Response:\n";
echo $response . "\n";
echo "\nParsed JSON:\n";
$parsed = json_decode($response);
if ($parsed) {
    echo json_encode($parsed, JSON_PRETTY_PRINT);
} else {
    echo "JSON Parse Error: " . json_last_error_msg();
}
echo "\n";
