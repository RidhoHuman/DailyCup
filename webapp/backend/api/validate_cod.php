<?php
/**
 * COD Validation API
 * Check if user eligible for COD payment
 * 
 * Security Features:
 * - Trust score validation
 * - Amount limit based on user status
 * - Distance restriction
 * - Blacklist check
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
require_once __DIR__ . '/../cors.php';
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection(); // Returns mysqli

try {
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $input['user_id'] ?? null;
    $orderAmount = floatval($input['order_amount'] ?? 0);
    $deliveryDistance = floatval($input['delivery_distance'] ?? 0);
    $deliveryAddress = $input['delivery_address'] ?? '';
    
    if (!$userId || $orderAmount <= 0) {
        throw new Exception('Invalid request data');
    }
    
    // Get user data
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.trust_score,
            u.total_successful_orders,
            u.cod_enabled,
            u.cod_blacklisted,
            u.blacklist_reason,
            u.is_verified_user,
            COUNT(CASE WHEN o.status = 'cancelled' 
                  AND o.payment_method = 'cod' 
                  AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                  THEN 1 END) as recent_cod_cancellations
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Initialize response
    $response = [
        'eligible' => false,
        'reasons' => [],
        'user_status' => [
            'trust_score' => intval($user['trust_score']),
            'is_verified' => boolval($user['is_verified_user']),
            'total_orders' => intval($user['total_successful_orders']),
            'cod_enabled' => boolval($user['cod_enabled']),
            'is_blacklisted' => boolval($user['cod_blacklisted'])
        ],
        'limits' => [],
        'recommendations' => []
    ];
    
    // ============================================
    // 1. CHECK BLACKLIST
    // ============================================
    if ($user['cod_blacklisted']) {
        $response['reasons'][] = '❌ Akun Anda diblokir dari menggunakan COD';
        $response['reasons'][] = 'Alasan: ' . ($user['blacklist_reason'] ?? 'Pelanggaran kebijakan COD');
        $response['recommendations'][] = 'Gunakan pembayaran online (Xendit) untuk melanjutkan';
        
        echo json_encode($response);
        exit;
    }
    
    // ============================================
    // 2. GET COD VALIDATION RULES
    // ============================================
    $result = $conn->query("
        SELECT rule_name, rule_type, rule_value, rule_operator 
        FROM cod_validation_rules 
        WHERE is_active = 1
    ");
    $rulesMap = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rulesMap[$row['rule_name']] = $row;
        }
        $result->close();
    }
    
    // ============================================
    // 3. CHECK AMOUNT LIMIT
    // ============================================
    $amountLimit = 0;
    if ($user['is_verified_user'] && $user['total_successful_orders'] >= 1) {
        // Verified user limit
        $amountLimit = $rulesMap['max_cod_amount_verified']['rule_value'] ?? 100000;
        $response['user_status']['user_type'] = 'verified';
    } else {
        // New user limit  
        $amountLimit = $rulesMap['max_cod_amount_new_user']['rule_value'] ?? 50000;
        $response['user_status']['user_type'] = 'new';
    }
    
    $response['limits']['max_amount'] = $amountLimit;
    
    if ($orderAmount > $amountLimit) {
        $response['reasons'][] = sprintf(
            '❌ Jumlah pesanan (Rp %s) melebihi batas COD Anda (Rp %s)',
            number_format($orderAmount, 0, ',', '.'),
            number_format($amountLimit, 0, ',', '.')
        );
        
        if (!$user['is_verified_user']) {
            $response['recommendations'][] = 'Selesaikan 1 pesanan sukses untuk meningkatkan limit COD Anda';
            $response['recommendations'][] = 'Atau gunakan pembayaran online untuk pesanan ini';
        } else {
            $response['recommendations'][] = 'Gunakan pembayaran online untuk pesanan dengan nilai lebih tinggi';
        }
    }
    
    // ============================================
    // 4. CHECK DELIVERY DISTANCE
    // ============================================
    $maxDistance = $rulesMap['max_delivery_distance']['rule_value'] ?? 5;
    $response['limits']['max_distance'] = $maxDistance;
    
    if ($deliveryDistance > $maxDistance) {
        $response['reasons'][] = sprintf(
            '❌ Jarak pengiriman (%.1f KM) melebihi batas COD (%.1f KM)',
            $deliveryDistance,
            $maxDistance
        );
        $response['recommendations'][] = 'COD hanya tersedia untuk jarak pengiriman di bawah ' . $maxDistance . ' KM';
        $response['recommendations'][] = 'Gunakan pembayaran online untuk alamat pengiriman ini';
    }
    
    // ============================================
    // 5. CHECK TRUST SCORE
    // ============================================
    $minTrustScore = $rulesMap['min_trust_score_cod']['rule_value'] ?? 20;
    $response['limits']['min_trust_score'] = $minTrustScore;
    
    if ($user['trust_score'] < $minTrustScore) {
        $response['reasons'][] = sprintf(
            '⚠️ Trust score Anda (%d) di bawah minimum (%d)',
            $user['trust_score'],
            $minTrustScore
        );
        $response['recommendations'][] = 'Selesaikan pesanan untuk meningkatkan trust score';
        $response['recommendations'][] = 'Gunakan pembayaran online untuk memulai';
    }
    
    // ============================================
    // 6. CHECK RECENT COD CANCELLATIONS
    // ============================================
    $recentCancellations = intval($user['recent_cod_cancellations']);
    if ($recentCancellations >= 2) {
        $response['reasons'][] = sprintf(
            '⚠️ Anda memiliki %d pembatalan pesanan COD dalam 30 hari terakhir',
            $recentCancellations
        );
        $response['recommendations'][] = 'Hindari pembatalan pesanan untuk menjaga akses COD Anda';
        $response['recommendations'][] = 'Pertimbangkan pembayaran online untuk saat ini';
    }
    
    // ============================================
    // 7. CHECK NEW USER WITHOUT PHONE
    // ============================================
    if (!$user['phone'] && !$user['is_verified_user']) {
        $response['reasons'][] = '⚠️ Nomor telepon diperlukan untuk menggunakan COD';
        $response['recommendations'][] = 'Lengkapi nomor telepon di profil Anda';
    }
    
    // ============================================
    // 8. DETERMINE FINAL ELIGIBILITY
    // ============================================
    $isEligible = true;
    
    // Critical checks (must pass)
    if ($user['cod_blacklisted']) $isEligible = false;
    if ($orderAmount > $amountLimit) $isEligible = false;
    if ($deliveryDistance > $maxDistance) $isEligible = false;
    if (!$user['phone']) $isEligible = false;
    
    // Warning checks (can still proceed with caution)
    $hasWarnings = false;
    if ($user['trust_score'] < $minTrustScore) $hasWarnings = true;
    if ($recentCancellations >= 2) $hasWarnings = true;
    
    $response['eligible'] = $isEligible;
    $response['has_warnings'] = $hasWarnings;
    
    // ============================================
    // 9. ADD SUCCESS MESSAGE
    // ============================================
    if ($isEligible && count($response['reasons']) === 0) {
        $response['reasons'][] = '✅ Anda memenuhi syarat untuk menggunakan COD';
        
        if ($user['is_verified_user']) {
            $response['reasons'][] = sprintf(
                '✅ Verified User - Limit COD: Rp %s',
                number_format($amountLimit, 0, ',', '.')
            );
        } else {
            $response['reasons'][] = sprintf(
                'ℹ️ New User - Limit COD: Rp %s (bereskan 1 pesanan untuk upgrade)',
                number_format($amountLimit, 0, ',', '.')
            );
        }
    }
    
    // Optional: Log validation check (commented out since order_id is required)
    // Can be enabled by creating a separate validation_logs table
    /*
    $stmt = $conn->prepare("
        INSERT INTO order_status_logs 
        (order_id, to_status, changed_by_type, notes, created_at)
        VALUES (NULL, 'cod_validation', 'system', ?, NOW())
    ");
    $logData = json_encode([
        'user_id' => $userId,
        'amount' => $orderAmount,
        'distance' => $deliveryDistance,
        'eligible' => $isEligible,
        'trust_score' => $user['trust_score']
    ]);
    $stmt->bind_param("s", $logData);
    $stmt->execute();
    $stmt->close();
    */
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
