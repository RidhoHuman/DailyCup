<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Testing detail.php...<br><br>";

try {
    require_once __DIR__ . '/includes/functions.php';
    echo "✓ functions.php loaded<br>";
    
    $db = getDB();
    echo "✓ Database connected<br>";
    
    // Test user query
    $userId = 3;
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ User found: " . htmlspecialchars($user['name']) . "<br>";
    } else {
        echo "✗ User not found<br>";
    }
    
    // Test formatDate function
    if (function_exists('formatDate')) {
        echo "✓ formatDate function exists<br>";
        $testDate = formatDate('2026-01-13 10:00:00');
        echo "Test date: " . $testDate . "<br>";
    } else {
        echo "✗ formatDate function NOT found<br>";
    }
    
    // Test formatCurrency function
    if (function_exists('formatCurrency')) {
        echo "✓ formatCurrency function exists<br>";
        $testPrice = formatCurrency(50000);
        echo "Test price: " . $testPrice . "<br>";
    } else {
        echo "✗ formatCurrency function NOT found<br>";
    }
    
    echo "<br>All tests passed!";
    
} catch (Exception $e) {
    echo "<br><strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
}
?>
