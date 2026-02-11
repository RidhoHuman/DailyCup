<?php
/**
 * Test Phase 2 Security Tables Creation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $db = Database::getInstance()->getConnection();

    // Check if Phase 2 tables exist
    $tables = [
        'password_reset_tokens',
        'user_2fa',
        'email_queue',
        'login_history',
        'backup_logs'
    ];

    echo "<h2>Phase 2 Security Tables Status</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";

    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch();

            if ($exists) {
                // Get record count
                $countStmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countStmt->fetch()['count'];

                echo "<tr><td>$table</td><td style='color: green;'>✓ Created</td><td>$count</td></tr>";
            } else {
                echo "<tr><td>$table</td><td style='color: red;'>✗ Missing</td><td>-</td></tr>";
            }
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</td><td>-</td></tr>";
        }
    }

    echo "</table>";

    // Test email verification columns in users table
    echo "<h3>Email Verification Columns in Users Table</h3>";
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $emailColumns = ['email_verified', 'verification_token', 'verification_expires'];
    echo "<ul>";
    foreach ($emailColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "<li style='color: green;'>✓ $col column exists</li>";
        } else {
            echo "<li style='color: orange;'>⚠ $col column missing (add manually if needed)</li>";
        }
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
?>
