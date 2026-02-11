<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();

echo "=== Orders Table Structure ===" . PHP_EOL;
$result = $db->query("DESCRIBE orders");

if ($result) {
    while ($col = $result->fetch_assoc()) {
        echo "{$col['Field']} ({$col['Type']}) - Null:{$col['Null']} - Key:{$col['Key']}" . PHP_EOL;
    }
    
    // Reset and check again
    $result = $db->query("DESCRIBE orders");
    $hasTotal = false;
    $hasTotalAmount = false;
    $hasFinalAmount = false;
    
    while ($col = $result->fetch_assoc()) {
        if ($col['Field'] === 'total') $hasTotal = true;
        if ($col['Field'] === 'total_amount') $hasTotalAmount = true;
        if ($col['Field'] === 'final_amount') $hasFinalAmount = true;
    }
    
    echo "\n=== Check for 'total' column ===" . PHP_EOL;
    echo "Has 'total': " . ($hasTotal ? 'YES ✅' : 'NO ❌') . PHP_EOL;
    echo "Has 'total_amount': " . ($hasTotalAmount ? 'YES ✅' : 'NO ❌') . PHP_EOL;
    echo "Has 'final_amount': " . ($hasFinalAmount ? 'YES ✅' : 'NO ❌') . PHP_EOL;
}
