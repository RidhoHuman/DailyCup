<?php
/**
 * Redeem Codes API
 * Handles CRUD operations for loyalty points redeem codes
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

// Ensure redeem_codes table exists
try {
    $db->exec("CREATE TABLE IF NOT EXISTS redeem_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        points INT NOT NULL,
        is_used TINYINT(1) DEFAULT 0,
        used_by VARCHAR(255) NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_code (code),
        INDEX idx_is_used (is_used)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Table might already exist, continue
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$codeId = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    switch ($method) {
        case 'GET':
            getAllCodes($db);
            break;

        case 'POST':
            // Admin only - generate codes
            $userData = validateToken();
            if ($userData['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }
            generateCodes($db, $input);
            break;

        case 'DELETE':
            // Admin only - delete code
            $userData = validateToken();
            if ($userData['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }
            deleteCode($db, $codeId);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}

/**
 * Get all redeem codes
 */
function getAllCodes($db) {
    $query = "SELECT * FROM redeem_codes ORDER BY created_at DESC";
    $stmt = $db->query($query);
    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'codes' => $codes
    ]);
}

/**
 * Generate new redeem codes
 */
function generateCodes($db, $data) {
    $points = intval($data['points'] ?? 0);
    $count = intval($data['count'] ?? 1);

    if ($points <= 0 || $count <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid points or count']);
        return;
    }

    if ($count > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Maximum 100 codes can be generated at once']);
        return;
    }

    try {
        $db->beginTransaction();
        $generatedCodes = [];

        for ($i = 0; $i < $count; $i++) {
            // Generate random unique code
            $attempts = 0;
            do {
                $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
                
                // Check if code already exists
                $checkStmt = $db->prepare("SELECT id FROM redeem_codes WHERE code = ?");
                $checkStmt->execute([$code]);
                $exists = $checkStmt->fetch();
                
                $attempts++;
                if ($attempts > 10) {
                    throw new Exception('Failed to generate unique code');
                }
            } while ($exists);

            $stmt = $db->prepare("INSERT INTO redeem_codes (code, points) VALUES (?, ?)");
            $stmt->execute([$code, $points]);
            
            $generatedCodes[] = $code;
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "$count code(s) generated successfully",
            'codes' => $generatedCodes
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate codes: ' . $e->getMessage()]);
    }
}

/**
 * Delete redeem code
 */
function deleteCode($db, $codeId) {
    if (!$codeId) {
        http_response_code(400);
        echo json_encode(['error' => 'Code ID required']);
        return;
    }

    $query = "DELETE FROM redeem_codes WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$codeId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Code deleted successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Code not found']);
    }
}
