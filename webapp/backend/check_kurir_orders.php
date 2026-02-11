<?php
// Simple script to check kurir orders
$mysqli = new mysqli('localhost', 'root', '', 'dailycup_db');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "=== Orders available for assignment ===\n";
$result = $mysqli->query("SELECT id, order_number, status FROM orders WHERE kurir_id IS NULL AND status IN ('confirmed', 'processing', 'ready') LIMIT 5");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - Order: {$row['order_number']} - Status: {$row['status']}\n";
    }
} else {
    echo "No orders available for assignment\n";
}

echo "\n=== Active orders for Kurir ID 1 ===\n";
$result2 = $mysqli->query("SELECT id, order_number, status, kurir_id FROM orders WHERE kurir_id = 1 AND status IN ('confirmed', 'processing', 'ready', 'delivering') LIMIT 5");
if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        echo "ID: {$row['id']} - Order: {$row['order_number']} - Status: {$row['status']}\n";
    }
} else {
    echo "No active orders assigned to this kurir\n";
}

// Assign one order to kurir if available
echo "\n=== Assigning order to Kurir ID 1 ===\n";
$assign = $mysqli->query("UPDATE orders SET kurir_id = 1, assigned_at = NOW() WHERE kurir_id IS NULL AND status IN ('confirmed', 'ready') LIMIT 1");
if ($assign && $mysqli->affected_rows > 0) {
    echo "Successfully assigned 1 order to Kurir ID 1\n";
    
    // Show the assigned order
    $result3 = $mysqli->query("SELECT id, order_number, status FROM orders WHERE kurir_id = 1 ORDER BY assigned_at DESC LIMIT 1");
    if ($row = $result3->fetch_assoc()) {
        echo "Assigned Order: {$row['order_number']} (ID: {$row['id']}, Status: {$row['status']})\n";
    }
} else {
    echo "No orders available to assign\n";
}

$mysqli->close();
