<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check if kurir is logged in
if (!isset($_SESSION['kurir_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$kurirId = $_SESSION['kurir_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$latitude = floatval($input['latitude'] ?? 0);
$longitude = floatval($input['longitude'] ?? 0);

if (!$latitude || !$longitude) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

// Validate coordinates (basic check)
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinate range']);
    exit;
}

$db = getDB();

try {
    // Check if location exists for this kurir
    $stmt = $db->prepare("SELECT id FROM kurir_location WHERE kurir_id = ?");
    $stmt->execute([$kurirId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing location
        $stmt = $db->prepare("UPDATE kurir_location 
                             SET latitude = ?, longitude = ?, updated_at = NOW() 
                             WHERE kurir_id = ?");
        $stmt->execute([$latitude, $longitude, $kurirId]);
    } else {
        // Insert new location
        $stmt = $db->prepare("INSERT INTO kurir_location (kurir_id, latitude, longitude) 
                             VALUES (?, ?, ?)");
        $stmt->execute([$kurirId, $latitude, $longitude]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Location updated',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
