<?php
/**
 * Get Pending COD Orders API
 * Returns list of COD orders waiting for admin confirmation
 */

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Get pending COD orders
    $query = "
        SELECT 
            o.id,
            o.order_number,
            o.user_id,
            o.total_amount,
            o.final_amount,
            o.delivery_address,
            o.customer_notes,
            o.delivery_distance,
            o.cod_amount_limit,
            o.status,
            o.payment_method,
            o.payment_status,
            o.expires_at,
            o.created_at,
            u.name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            u.trust_score,
            u.total_successful_orders,
            u.is_verified_user,
            TIMESTAMPDIFF(MINUTE, NOW(), o.expires_at) as minutes_remaining,
            (SELECT COUNT(*) FROM orders o2 
             WHERE o2.user_id = u.id 
             AND o2.status = 'cancelled' 
             AND o2.payment_method = 'cod'
             AND o2.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_cancellations
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.payment_method = 'cod'
          AND o.status = 'pending'
          AND o.payment_status = 'pending'
          AND o.admin_confirmed_at IS NULL
        ORDER BY o.created_at ASC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate risk level
        $riskLevel = 'low';
        if ($row['trust_score'] < 20) $riskLevel = 'high';
        elseif ($row['recent_cancellations'] >= 2) $riskLevel = 'high';
        elseif ($row['delivery_distance'] > 4) $riskLevel = 'medium';
        elseif (!$row['is_verified_user']) $riskLevel = 'medium';
        
        // Add risk indicators
        $row['risk_level'] = $riskLevel;
        $row['is_expired'] = $row['minutes_remaining'] <= 0;
        $row['is_expiring_soon'] = $row['minutes_remaining'] > 0 && $row['minutes_remaining'] <= 10;
        
        $orders[] = $row;
    }
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM orders o
        WHERE o.payment_method = 'cod'
          AND o.status = 'pending'
          AND o.payment_status = 'pending'
          AND o.admin_confirmed_at IS NULL
    ";
    $countResult = $conn->query($countQuery);
    $totalCount = $countResult->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => intval($totalCount),
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
