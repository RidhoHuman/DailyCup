<?php
require_once __DIR__ . '/cors.php';
/**
 * Loyalty History API
 * Get user's loyalty points transaction history
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

$userId = $userData['id'];

try {
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
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Get loyalty history for user
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $query = "SELECT 
                id,
                transaction_type as type,
                points,
                description,
                created_at
              FROM loyalty_transactions 
              WHERE user_id = ? 
              ORDER BY created_at DESC 
              LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$userId, $limit, $offset]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM loyalty_transactions WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $totalCount = $countStmt->fetch()['total'];

    // Get current user points
    $userStmt = $db->prepare("SELECT loyalty_points FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $currentPoints = $userStmt->fetch()['loyalty_points'] ?? 0;

    echo json_encode([
        'success' => true,
        'history' => $history,
        'current_points' => $currentPoints,
        'total_count' => $totalCount
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch loyalty history',
        'message' => $e->getMessage()
    ]);
}
