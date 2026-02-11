<?php
session_start();
// Bypass auth untuk testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "<h1>Test API: get_all_kurir_locations.php</h1>";
echo "<hr>";

$url = 'http://localhost/DailyCup/api/get_all_kurir_locations.php';

echo "<h3>Testing URL: <a href='$url' target='_blank'>$url</a></h3>";

// Use file_get_contents with context for session cookies
$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . http_build_query($_COOKIE, '', '; ')
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "<div style='background:#ffebee;padding:20px;border-radius:5px;color:#c62828;'>";
    echo "<h3>❌ ERROR: Tidak dapat mengakses API</h3>";
    $error = error_get_last();
    echo "<pre>" . print_r($error, true) . "</pre>";
    echo "</div>";
} else {
    echo "<div style='background:#e8f5e9;padding:20px;border-radius:5px;'>";
    echo "<h3>✅ Response Berhasil</h3>";
    echo "<strong>Raw Response:</strong><br>";
    echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;max-height:400px;overflow:auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    echo "<br><strong>Decoded JSON:</strong><br>";
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;max-height:400px;overflow:auto;'>";
        print_r($data);
        echo "</pre>";
        
        echo "<h4>Summary:</h4>";
        echo "Total Kurir: " . count($data) . "<br>";
        if (count($data) > 0) {
            echo "Sample Kurir: " . htmlspecialchars($data[0]['name'] ?? 'N/A') . "<br>";
        }
    } else {
        echo "<div style='background:#fff3cd;padding:10px;color:#856404;'>";
        echo "⚠️ Response bukan valid JSON: " . json_last_error_msg();
        echo "</div>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<h3>Direct Test (Include File):</h3>";
echo "<div style='background:#e3f2fd;padding:20px;border-radius:5px;'>";
echo "<pre style='background:#f5f5f5;padding:10px;border-radius:3px;'>";

ob_start();
require __DIR__ . '/api/get_all_kurir_locations.php';
$direct_output = ob_get_clean();

echo htmlspecialchars($direct_output);
echo "</pre>";

$direct_data = json_decode($direct_output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<h4>✅ Direct include berhasil!</h4>";
    echo "Kurir count: " . count($direct_data);
} else {
    echo "<h4>❌ JSON Error: " . json_last_error_msg() . "</h4>";
}
echo "</div>";
?>
