<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

echo "<h3>Checking Database Tables</h3>";

// Check if tables exist
$tables = ['users', 'orders', 'reviews', 'support_tickets'];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "✓ Table <strong>$table</strong> exists<br>";
            
            // Show columns
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "&nbsp;&nbsp;&nbsp;Columns: " . implode(', ', $columns) . "<br><br>";
        } else {
            echo "✗ Table <strong>$table</strong> NOT FOUND<br><br>";
        }
    } catch (Exception $e) {
        echo "✗ Error checking $table: " . $e->getMessage() . "<br><br>";
    }
}

// Test the exact queries from detail.php
echo "<hr><h3>Testing Queries from detail.php</h3>";

$userId = 3;

// Query 1: Orders count
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    echo "✓ Query 1 (orders count): $count<br>";
} catch (Exception $e) {
    echo "✗ Query 1 failed: " . $e->getMessage() . "<br>";
}

// Query 2: Completed orders
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    echo "✓ Query 2 (completed orders): $count<br>";
} catch (Exception $e) {
    echo "✗ Query 2 failed: " . $e->getMessage() . "<br>";
}

// Query 3: Total spent
try {
    $stmt = $db->prepare("SELECT SUM(total_price) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $total = $stmt->fetchColumn() ?? 0;
    echo "✓ Query 3 (total spent): " . formatCurrency($total) . "<br>";
} catch (Exception $e) {
    echo "✗ Query 3 failed: " . $e->getMessage() . "<br>";
}

// Query 4: Reviews count
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    echo "✓ Query 4 (reviews): $count<br>";
} catch (Exception $e) {
    echo "✗ Query 4 failed: " . $e->getMessage() . "<br>";
}

// Query 5: Support tickets count
try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM support_tickets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    echo "✓ Query 5 (tickets): $count<br>";
} catch (Exception $e) {
    echo "✗ Query 5 failed: " . $e->getMessage() . "<br>";
}

// Query 6: Recent orders
try {
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
    echo "✓ Query 6 (recent orders): " . count($orders) . " records<br>";
} catch (Exception $e) {
    echo "✗ Query 6 failed: " . $e->getMessage() . "<br>";
}

echo "<br><h4>If all queries passed, the problem might be in header/footer includes</h4>";
?>
