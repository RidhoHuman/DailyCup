<?php
/**
 * CLEAR RATE LIMIT SCRIPT - Development Tool
 * Use this to reset rate limiting when blocked during testing
 */

require_once __DIR__ . '/../includes/functions.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Clear Rate Limit</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
        button { background: #6F4E37; color: white; padding: 10px 20px; border: none; cursor: pointer; font-size: 16px; }
        button:hover { background: #5a3e2d; }
    </style>
</head>
<body>
    <h1>🔓 Rate Limit Cleaner</h1>";

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    // Clear all rate limits from database
    if (clearRateLimit()) {
        echo "<div class='success'>";
        echo "<h2>✓ Success!</h2>";
        echo "<p>All rate limits have been cleared from the database.</p>";
        echo "<p>You can now login again.</p>";
        echo "</div>";
    } else {
        echo "<div style='color: red;'>";
        echo "<h2>✗ Error</h2>";
        echo "<p>Failed to clear rate limits. Check error logs.</p>";
        echo "</div>";
    }
    echo "<hr>";
    echo "<p><a href='clear_rate_limit.php'>← Back</a> | <a href='../auth/login.php'>Go to Login →</a></p>";
    
} else {
    // Show options
    echo "<div class='info'>";
    echo "<h3>About Rate Limiting</h3>";
    echo "<p>The system blocks login attempts after <strong>5 failed attempts</strong> within 15 minutes to prevent brute force attacks.</p>";
    echo "<p>If you're blocked during testing, use this tool to clear the rate limit.</p>";
    echo "</div>";
    
    // Check current mode
    if (defined('TESTING_MODE') && TESTING_MODE === true) {
        echo "<div class='success'>";
        echo "<h3>✓ Testing Mode Active</h3>";
        echo "<p>Rate limiting is currently <strong>disabled</strong> because TESTING_MODE is enabled in constants.php</p>";
        echo "<p>You should be able to login without being blocked.</p>";
        echo "</div>";
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠ Production Mode</h3>";
        echo "<p>Rate limiting is <strong>active</strong>. Failed login attempts will be blocked.</p>";
        echo "</div>";
    }
    
    // Show clear button
    echo "<hr>";
    echo "<h3>Clear All Rate Limits</h3>";
    echo "<p>This will remove all rate limit entries from the database.</p>";
    echo "<form method='get' action='' onsubmit='return confirm(\"Clear all rate limits?\");'>";
    echo "<input type='hidden' name='action' value='clear'>";
    echo "<button type='submit'>🗑️ Clear All Rate Limits</button>";
    echo "</form>";
    
    echo "<hr>";
    echo "<p><a href='../auth/login.php'>→ Go to Login Page</a></p>";
    echo "<p><em>Note: Remove this file in production environment!</em></p>";
}

echo "</body></html>";
?>
