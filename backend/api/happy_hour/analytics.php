<?php
/**
 * Happy Hour Analytics API
 * 
 * Purpose: Provide CRM analytics data for Happy Hour performance
 * Used by: Admin dashboard to show Happy Hour impact
 * 
 * GET Parameters:
 * - period: 'last_30_days' (default) | 'last_7_days' | 'last_90_days' | 'all_time'
 * - schedule_id: (optional) Filter by specific Happy Hour schedule
 * 
 * Returns: Comprehensive analytics data comparing Happy Hour vs normal sales
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

// Verify admin authentication
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'No token provided']);
    exit;
}

$decoded = JWT::verify($token);
if (!$decoded || !isset($decoded['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Check if user is admin
$userQuery = "SELECT role FROM users WHERE id = :user_id";
$userStmt = $pdo->prepare($userQuery);
$userStmt->bindParam(':user_id', $decoded['user_id']);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Get parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'last_30_days';
$scheduleId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : null;

// Calculate date range based on period
$dateFilter = '';
switch ($period) {
    case 'last_7_days':
        $dateFilter = "AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $days = 7;
        break;
    case 'last_30_days':
        $dateFilter = "AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $days = 30;
        break;
    case 'last_90_days':
        $dateFilter = "AND order_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $days = 90;
        break;
    case 'all_time':
        $dateFilter = "";
        $days = null;
        break;
    default:
        $dateFilter = "AND order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $days = 30;
}

$scheduleFilter = $scheduleId ? "AND happy_hour_id = :schedule_id" : "";

try {
    // 1. Overall Happy Hour Statistics
    $overallQuery = "SELECT 
                        COUNT(DISTINCT order_id) as total_orders,
                        COUNT(DISTINCT user_id) as unique_customers,
                        SUM(quantity) as total_items_sold,
                        SUM(original_price * quantity) as total_original_revenue,
                        SUM(discount_amount * quantity) as total_discount_given,
                        SUM(final_price * quantity) as total_actual_revenue,
                        AVG(discount_percentage) as avg_discount_percentage
                    FROM happy_hour_analytics
                    WHERE 1=1 $dateFilter $scheduleFilter";
    
    $stmt = $pdo->prepare($overallQuery);
    if ($scheduleId) {
        $stmt->bindParam(':schedule_id', $scheduleId);
    }
    $stmt->execute();
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Compare with normal (non-Happy Hour) sales in same period
    $normalSalesQuery = "SELECT 
                            COUNT(DISTINCT o.id) as normal_orders,
                            SUM(oi.quantity) as normal_items,
                            SUM(oi.price * oi.quantity) as normal_revenue
                        FROM orders o
                        INNER JOIN order_items oi ON o.id = oi.order_id
                        WHERE o.status NOT IN ('cancelled', 'failed')
                        AND o.id NOT IN (SELECT DISTINCT order_id FROM happy_hour_analytics WHERE 1=1 $dateFilter $scheduleFilter)
                        " . str_replace('order_date', 'o.created_at', $dateFilter);
    
    $normalStmt = $pdo->prepare($normalSalesQuery);
    if ($scheduleId && strpos($normalSalesQuery, ':schedule_id') !== false) {
        $normalStmt->bindParam(':schedule_id', $scheduleId);
    }
    $normalStmt->execute();
    $normalSales = $normalStmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate performance metrics
    $totalOrders = (int)$overall['total_orders'] + (int)$normalSales['normal_orders'];
    $happyHourPercentage = $totalOrders > 0 ? round(((int)$overall['total_orders'] / $totalOrders) * 100, 2) : 0;
    
    // Calculate ROI: Is discount worth it?
    $revenueIncrease = ((int)$overall['total_actual_revenue'] - (int)$normalSales['normal_revenue']);
    $roi = (int)$overall['total_discount_given'] > 0 
        ? round(($revenueIncrease / (int)$overall['total_discount_given']) * 100, 2) 
        : 0;
    
    // 3. Daily breakdown for chart
    $dailyQuery = "SELECT 
                        DATE(order_date) as date,
                        COUNT(DISTINCT order_id) as orders,
                        SUM(quantity) as items,
                        SUM(final_price * quantity) as revenue,
                        SUM(discount_amount * quantity) as discount
                    FROM happy_hour_analytics
                    WHERE 1=1 $dateFilter $scheduleFilter
                    GROUP BY DATE(order_date)
                    ORDER BY date DESC
                    LIMIT 90";
    
    $dailyStmt = $pdo->prepare($dailyQuery);
    if ($scheduleId) {
        $dailyStmt->bindParam(':schedule_id', $scheduleId);
    }
    $dailyStmt->execute();
    $dailyData = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Top performing products
    $topProductsQuery = "SELECT 
                            p.id,
                            p.name,
                            p.image,
                            COUNT(DISTINCT hha.order_id) as order_count,
                            SUM(hha.quantity) as total_sold,
                            SUM(hha.discount_amount * hha.quantity) as total_discount,
                            SUM(hha.final_price * hha.quantity) as total_revenue
                        FROM happy_hour_analytics hha
                        INNER JOIN products p ON hha.product_id = p.id
                        WHERE 1=1 $dateFilter $scheduleFilter
                        GROUP BY p.id
                        ORDER BY total_sold DESC
                        LIMIT 10";
    
    $topStmt = $pdo->prepare($topProductsQuery);
    if ($scheduleId) {
        $topStmt->bindParam(':schedule_id', $scheduleId);
    }
    $topStmt->execute();
    $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Schedule performance comparison (if not filtered by schedule)
    $schedulePerformance = [];
    if (!$scheduleId) {
        $scheduleQuery = "SELECT 
                            hhs.id,
                            hhs.name,
                            COUNT(DISTINCT hha.order_id) as orders,
                            SUM(hha.quantity) as items_sold,
                            SUM(hha.discount_amount * hha.quantity) as discount_given,
                            SUM(hha.final_price * hha.quantity) as revenue
                        FROM happy_hour_schedules hhs
                        LEFT JOIN happy_hour_analytics hha ON hhs.id = hha.happy_hour_id
                        WHERE 1=1 " . str_replace('order_date', 'hha.order_date', $dateFilter) . "
                        GROUP BY hhs.id
                        ORDER BY revenue DESC";
        
        $scheduleStmt = $pdo->query($scheduleQuery);
        $schedulePerformance = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format response
    echo json_encode([
        'success' => true,
        'period' => $period,
        'days' => $days,
        'summary' => [
            'happy_hour_orders' => (int)$overall['total_orders'],
            'normal_orders' => (int)$normalSales['normal_orders'],
            'total_orders' => $totalOrders,
            'happy_hour_percentage' => $happyHourPercentage,
            'unique_customers' => (int)$overall['unique_customers'],
            'items_sold' => (int)$overall['total_items_sold'],
            'original_revenue' => (float)$overall['total_original_revenue'],
            'discount_given' => (float)$overall['total_discount_given'],
            'actual_revenue' => (float)$overall['total_actual_revenue'],
            'revenue_increase' => $revenueIncrease,
            'roi_percentage' => $roi,
            'avg_discount' => round((float)$overall['avg_discount_percentage'], 2)
        ],
        'daily_breakdown' => array_map(function($day) {
            return [
                'date' => $day['date'],
                'orders' => (int)$day['orders'],
                'items' => (int)$day['items'],
                'revenue' => (float)$day['revenue'],
                'discount' => (float)$day['discount']
            ];
        }, $dailyData),
        'top_products' => array_map(function($product) {
            return [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'image' => $product['image'],
                'orders' => (int)$product['order_count'],
                'sold' => (int)$product['total_sold'],
                'discount' => (float)$product['total_discount'],
                'revenue' => (float)$product['total_revenue']
            ];
        }, $topProducts),
        'schedule_performance' => array_map(function($schedule) {
            return [
                'id' => (int)$schedule['id'],
                'name' => $schedule['name'],
                'orders' => (int)$schedule['orders'],
                'items' => (int)$schedule['items_sold'],
                'discount' => (float)$schedule['discount_given'],
                'revenue' => (float)$schedule['revenue']
            ];
        }, $schedulePerformance)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
