<?php
/**
 * Get Dashboard Statistics API
 *
 * Returns admin dashboard statistics from real database
 * GET /api/admin/get_dashboard_stats.php
 * Requires: Admin authentication
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

// Only accept GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require admin authentication
$authUser = JWT::requireAuth();
if ($authUser['role'] !== 'admin' && $authUser['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Get total revenue (sum of all paid orders) - Use final_amount instead of total
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(final_amount), 0) as total_revenue
        FROM orders
        WHERE payment_status = 'paid'
    ");
    $revenueData = $stmt->fetch();
    $totalRevenue = (float)$revenueData['total_revenue'];

    // Get total orders count
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $ordersData = $stmt->fetch();
    $totalOrders = (int)$ordersData['total_orders'];

    // Get pending orders count
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
    $pendingData = $stmt->fetch();
    $pendingOrders = (int)$pendingData['pending_orders'];

    // Get new customers count (registered in last 30 days)
    $stmt = $pdo->query("
        SELECT COUNT(*) as new_customers
        FROM users
        WHERE role = 'customer'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $customersData = $stmt->fetch();
    $newCustomers = (int)$customersData['new_customers'];

    // Get revenue trend (compare last 30 days vs previous 30 days)
    $stmt = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN final_amount ELSE 0 END), 0) as current_month,
            COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN final_amount ELSE 0 END), 0) as previous_month
        FROM orders
        WHERE payment_status = 'paid'
    ");
    $trendData = $stmt->fetch();
    $currentMonth = (float)$trendData['current_month'];
    $previousMonth = (float)$trendData['previous_month'];
    
    $revenueTrend = 0;
    if ($previousMonth > 0) {
        $revenueTrend = (($currentMonth - $previousMonth) / $previousMonth) * 100;
    }

    // Get orders trend
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as current_month,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as previous_month
        FROM orders
    ");
    $orderTrendData = $stmt->fetch();
    $currentMonthOrders = (int)$orderTrendData['current_month'];
    $previousMonthOrders = (int)$orderTrendData['previous_month'];
    
    $ordersTrend = 0;
    if ($previousMonthOrders > 0) {
        $ordersTrend = (($currentMonthOrders - $previousMonthOrders) / $previousMonthOrders) * 100;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'newCustomers' => $newCustomers,
            'revenueTrend' => round($revenueTrend, 1),
            'ordersTrend' => round($ordersTrend, 1)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
