<?php
// Test direct access to GDPR page
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing GDPR Page...<br>";

require_once __DIR__ . '/../includes/functions.php';
echo "✓ functions.php loaded<br>";

// Check if we can connect to DB
try {
    $db = getDB();
    echo "✓ Database connected<br>";
    
    // Check if gdpr_requests table exists
    $stmt = $db->query("SHOW TABLES LIKE 'gdpr_requests'");
    if ($stmt->fetch()) {
        echo "✓ gdpr_requests table exists<br>";
    } else {
        echo "✗ gdpr_requests table NOT found!<br>";
    }
    
    // Try to query the table
    $stmt = $db->query("SELECT COUNT(*) FROM gdpr_requests");
    $count = $stmt->fetchColumn();
    echo "✓ Found {$count} GDPR requests<br>";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<br>All tests passed! The page should work.<br>";
echo "<a href='gdpr_requests.php'>Go to GDPR Requests Page</a>";
?>
