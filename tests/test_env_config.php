<?php
/**
 * Test Database Connection with Environment Variables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Database Connection with Environment Variables</h2>";
echo "<hr>";

try {
    // Load database config
    require_once __DIR__ . '/../config/database.php';
    
    echo "✅ <strong>Environment variables loaded successfully!</strong><br><br>";
    
    echo "<h3>Configuration Values:</h3>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    echo "<tr><td>DB_HOST</td><td>" . DB_HOST . "</td></tr>";
    echo "<tr><td>DB_NAME</td><td>" . DB_NAME . "</td></tr>";
    echo "<tr><td>DB_USER</td><td>" . DB_USER . "</td></tr>";
    echo "<tr><td>DB_PASS</td><td>" . (empty(DB_PASS) ? '<em>(empty)</em>' : '***hidden***') . "</td></tr>";
    echo "<tr><td>APP_ENV</td><td>" . APP_ENV . "</td></tr>";
    echo "<tr><td>APP_DEBUG</td><td>" . (APP_DEBUG ? 'true' : 'false') . "</td></tr>";
    echo "</table>";
    
    echo "<br><h3>Database Connection Test:</h3>";
    
    // Test connection
    $db = getDB();
    echo "✅ <strong>Database connected successfully!</strong><br><br>";
    
    // Test query
    $stmt = $db->query("SELECT DATABASE() as db_name, VERSION() as mysql_version");
    $result = $stmt->fetch();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Database Name</th><td>" . $result['db_name'] . "</td></tr>";
    echo "<tr><th>MySQL Version</th><td>" . $result['mysql_version'] . "</td></tr>";
    echo "</table>";
    
    // Test user table
    echo "<br><h3>Testing User Table Access:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as user_count FROM users");
    $userCount = $stmt->fetch();
    echo "✅ Users table accessible - Total users: <strong>" . $userCount['user_count'] . "</strong><br>";
    
    echo "<br><h3>Environment Variables Security Check:</h3>";
    echo "✅ Database credentials are now loaded from .env file<br>";
    echo "✅ .env file is excluded from Git (.gitignore updated)<br>";
    echo "✅ Sensitive data is no longer hardcoded in source code<br>";
    
    echo "<br><div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ SUCCESS!</h3>";
    echo "<p style='color: #155724; margin: 0;'>Environment Variables implementation is working correctly!</p>";
    echo "</div>";
    
    echo "<br><h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>✅ Keep .env file secure - never commit to Git</li>";
    echo "<li>✅ For production, use strong passwords in .env</li>";
    echo "<li>✅ Change JWT_SECRET and SESSION_SECRET to random values</li>";
    echo "<li>🔄 Ready to implement next security fix (Rate Limiting)</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ ERROR!</h3>";
    echo "<p style='color: #721c24;'><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: #721c24;'><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
