<?php
require_once __DIR__ . '/includes/functions.php';

// Set timezone explicitly to check match
date_default_timezone_set('Asia/Jakarta');

$token = $_GET['token'] ?? '';

echo "<h1>Debug Reset Token</h1>";
echo "Run Time (PHP): " . date('Y-m-d H:i:s') . "<br>";
echo "Current Timezone: " . date_default_timezone_get() . "<br>";
echo "Token provided: " . htmlspecialchars($token) . "<br><br>";

if (empty($token)) {
    echo "No token provided in URL (?token=...)";
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM password_reset_tokens WHERE token = ?");
$stmt->execute([$token]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data) {
    echo "<h3>Token Found in Database (NEW System):</h3>";
    echo "<pre>";
    print_r($data);
    echo "</pre>";

    echo "<h3>Validation Logic Check:</h3>";
    $expiryTime = strtotime($data['expires_at']);
    $now = time();
    $diff = $expiryTime - $now;
    
    echo "Expires At (DB String): " . $data['expires_at'] . "<br>";
    echo "Expires At (Timestamp): " . $expiryTime . "<br>";
    echo "Current Time (Timestamp): " . $now . "<br>";
    echo "Difference (Seconds): " . $diff . " seconds<br>";
    
    if ($data['used'] == 1) {
        echo "<span style='color:red'>FAILED: Token is marked as USED.</span>";
    } elseif ($diff < 0) {
        echo "<span style='color:red'>FAILED: Token is EXPIRED.</span>";
    } else {
        echo "<span style='color:green'>SUCCESS: Token is VALID.</span>";
    }

} else {
    // Check Legacy Table
    $stmt = $db->prepare("SELECT id, name, email, reset_expires FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $legacyUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($legacyUser) {
        echo "<h3>Token Found in Database (LEGACY System - Users Table):</h3>";
        echo "<pre>";
        print_r($legacyUser);
        echo "</pre>";
        
        $expiryTime = strtotime($legacyUser['reset_expires']);
        $now = time();
         if ($expiryTime < $now) {
            echo "<span style='color:red'>FAILED: Legacy Token is EXPIRED.</span>";
        } else {
            echo "<span style='color:green'>SUCCESS: Legacy Token is VALID.</span>";
        }
    } else {
        echo "<h3 style='color:red'>Token NOT FOUND in Database.</h3>";
        echo "Please check if the token was copied correctly or if it was truncated.";
    }
}
?>
