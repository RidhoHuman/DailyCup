<?php
/**
 * Debug endpoint to lookup a user by email (for local development only)
 * GET /api/debug_user_lookup.php?email=<email>
 * Returns basic user info (excludes password)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config/database.php';

$email = $_GET['email'] ?? '';
if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing email parameter']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name, email, phone, address, role, loyalty_points, is_active, created_at FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    echo json_encode(['success' => true, 'user' => $user]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
}
