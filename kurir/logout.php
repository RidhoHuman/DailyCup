<?php
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['kurir_id'])) {
    $db = getDB();
    
    // Set kurir status to offline
    $stmt = $db->prepare("UPDATE kurir SET status = 'offline' WHERE id = ?");
    $stmt->execute([$_SESSION['kurir_id']]);
    
    session_destroy();
}

header('Location: login.php');
exit;
