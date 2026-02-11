<?php
/**
 * Redeem Code API for webapp
 * Handles loyalty code redemption
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/input_sanitizer.php';

header('Content-Type: application/json');

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'dailycup_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Validate user authentication
$userData = validateToken();

if (!$userData || !isset($userData['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($input['code'] ?? ''));
$userId = $userData['id'];
$userName = $userData['name'] ?? 'User';

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

try {
    $db->beginTransaction();

    // Check if code exists and not used
    $stmt = $db->prepare("SELECT * FROM redeem_codes WHERE code = ? AND is_used = 0");
    $stmt->execute([$code]);
    $redeemCode = $stmt->fetch();

    if (!$redeemCode) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or already used code']);
        $db->rollBack();
        exit;
    }

    $points = $redeemCode['points'];

    // Update redeem_code status
    $stmt = $db->prepare("UPDATE redeem_codes SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ?");
    $stmt->execute([$userName, $redeemCode['id']]);

    // Add points to user
    $stmt = $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
    $stmt->execute([$points, $userId]);

    // Ensure loyalty_transactions table exists
    $db->exec("CREATE TABLE IF NOT EXISTS loyalty_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        transaction_type ENUM('earned', 'spent', 'expired', 'refunded') NOT NULL,
        points INT NOT NULL,
        description VARCHAR(255),
        order_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Record transaction
    $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, points, transaction_type, description, created_at) VALUES (?, ?, 'earned', ?, NOW())");
    $stmt->execute([$userId, $points, "Redeem Code: $code"]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'points' => $points,
        'message' => "Successfully redeemed $points points!"
    ]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to redeem code: ' . $e->getMessage()
    ]);
}
