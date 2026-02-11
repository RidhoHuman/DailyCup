<?php
/**
 * Analytics API
 * 
 * Provides analytics data for admin dashboard
 * - Revenue statistics
 * - Sales trends
 * - Order insights
 * - Best selling products
 */

// CORS handled by .htaccess
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Require authentication (admin only in production)
$user = JWT::getUser();
// For development, allow without auth. In production, uncomment below:
// if (!$user || ($user['role'] ?? '') !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

// Use global $pdo from database.php
$db = $pdo;

// Get date range from query params (default: last 30 days)
$period = $_GET['period'] ?? '30days';

switch ($period) {
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
}

$analytics = [];

try {
    // 1. REVENUE STATISTICS
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN payment_status = 'failed' THEN 1 ELSE 0 END) as failed_orders,
            AVG(CASE WHEN payment_status = 'paid' THEN final_amount END) as average_order_value
        FROM orders
        WHERE created_at >= ?
    ");
    $stmt->execute([$startDate]);
    $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $analytics['revenue'] = [
        'total_revenue' => (float)($revenueStats['total_revenue'] ?? 0),
        'total_orders' => (int)($revenueStats['total_orders'] ?? 0),
        'paid_orders' => (int)($revenueStats['paid_orders'] ?? 0),
        'pending_orders' => (int)($revenueStats['pending_orders'] ?? 0),
        'failed_orders' => (int)($revenueStats['failed_orders'] ?? 0),
        'average_order_value' => (float)($revenueStats['average_order_value'] ?? 0),
        'conversion_rate' => $revenueStats['total_orders'] > 0 
            ? round(($revenueStats['paid_orders'] / $revenueStats['total_orders']) * 100, 2)
            : 0
    ];

    // 2. DAILY REVENUE TREND (for charts)
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as revenue
        FROM orders
        WHERE created_at >= ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$startDate]);
    $analytics['daily_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. BEST SELLING PRODUCTS
    $stmt = $db->prepare("
        SELECT 
            oi.product_id,
            oi.product_name,
            SUM(oi.quantity) as total_sold,
            SUM(oi.subtotal) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= ? AND o.payment_status = 'paid'
        GROUP BY oi.product_id, oi.product_name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute([$startDate]);
    $analytics['best_sellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. PAYMENT METHOD DISTRIBUTION
    $stmt = $db->prepare("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as total_amount
        FROM orders
        WHERE created_at >= ?
        GROUP BY payment_method
        ORDER BY count DESC
    ");
    $stmt->execute([$startDate]);
    $analytics['payment_methods'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. ORDER STATUS DISTRIBUTION
    $stmt = $db->prepare("
        SELECT 
            payment_status,
            COUNT(*) as count
        FROM orders
        WHERE created_at >= ?
        GROUP BY payment_status
    ");
    $stmt->execute([$startDate]);
    $analytics['order_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. HOURLY DISTRIBUTION (peak hours)
    $stmt = $db->prepare("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as orders_count
        FROM orders
        WHERE created_at >= ?
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");
    $stmt->execute([$startDate]);
    $analytics['hourly_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. COMPARISON WITH PREVIOUS PERIOD
    $previousStartDate = date('Y-m-d', strtotime($startDate . ' -' . $period));
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as revenue,
            COUNT(*) as orders
        FROM orders
        WHERE created_at >= ? AND created_at < ?
    ");
    $stmt->execute([$previousStartDate, $startDate]);
    $previousStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $currentRevenue = $analytics['revenue']['total_revenue'];
    $previousRevenue = (float)($previousStats['revenue'] ?? 0);
    
    $analytics['comparison'] = [
        'revenue_change' => $previousRevenue > 0 
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0,
        'orders_change' => $previousStats['orders'] > 0
            ? round((($analytics['revenue']['total_orders'] - $previousStats['orders']) / $previousStats['orders']) * 100, 2)
            : 0,
        'previous_revenue' => $previousRevenue,
        'previous_orders' => (int)($previousStats['orders'] ?? 0)
    ];

    echo json_encode([
        'success' => true,
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => date('Y-m-d'),
        'analytics' => $analytics
    ]);

} catch (PDOException $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'error' => $e->getMessage()
    ]);
}
?>
