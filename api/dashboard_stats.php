<?php
/**
 * Dashboard Statistics API
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check admin access (Phase 3: Robust Auth)
if (!isAdmin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = getDB();
$type = $_GET['type'] ?? '';

try {
    switch ($type) {
        case 'sales':
            // Revenue data for last 7 days
            $stmt = $db->query("
                SELECT DATE(created_at) as date, 
                       SUM(final_amount) as revenue,
                       COUNT(*) as orders
                FROM orders 
                WHERE status = 'completed' 
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fill missing dates with 0
            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $found = false;
                foreach ($data as $row) {
                    if ($row['date'] == $date) {
                        $result[] = [
                            'date' => date('d M', strtotime($date)),
                            'revenue' => floatval($row['revenue']),
                            'orders' => intval($row['orders'])
                        ];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $result[] = [
                        'date' => date('d M', strtotime($date)),
                        'revenue' => 0,
                        'orders' => 0
                    ];
                }
            }
            echo json_encode($result);
            break;
            
        case 'orders':
            // Order status distribution
            $stmt = $db->query("
                SELECT status, COUNT(*) as count
                FROM orders 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY status
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data);
            break;
            
        case 'products':
            // Top 5 selling products
            $stmt = $db->query("
                SELECT p.name, SUM(oi.quantity) as total_sold
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status = 'completed'
                  AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.id, p.name
                ORDER BY total_sold DESC
                LIMIT 5
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data);
            break;
            
        case 'kurir':
            // Kurir performance
            $stmt = $db->query("
                SELECT k.name, COUNT(o.id) as deliveries,
                       AVG(TIMESTAMPDIFF(MINUTE, o.pickup_time, o.delivery_time)) as avg_time
                FROM kurir k
                LEFT JOIN orders o ON k.id = o.kurir_id AND o.status = 'completed'
                WHERE k.is_active = 1
                GROUP BY k.id, k.name
                ORDER BY deliveries DESC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data);
            break;
            
        case 'customers':
            // New customers per month (last 6 months)
            $stmt = $db->query("
                SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                       COUNT(*) as new_customers
                FROM users
                WHERE role = 'customer'
                  AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format month names
            $result = array_map(function($row) {
                return [
                    'month' => date('M Y', strtotime($row['month'] . '-01')),
                    'count' => intval($row['new_customers'])
                ];
            }, $data);
            
            echo json_encode($result);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type parameter']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
