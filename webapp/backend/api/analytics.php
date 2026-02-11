<?php
// CRITICAL: Start output buffering FIRST to prevent any early output
ob_start();

// CRITICAL: .htaccess handles ALL CORS headers via mod_headers
// DO NOT set CORS headers here to prevent duplicates!
// Only set Content-Type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Handle OPTIONS preflight - .htaccess handles CORS, we just return 200
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_flush();
    exit();
}

// CRITICAL: Prevent session from starting before our CORS headers
@ini_set('session.auto_start', 0);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/jwt.php'; // CRITICAL: Use same JWT class as login.php

// CRITICAL: Get Authorization token from multiple sources
$authHeader = null;

// Method 1: Try getallheaders() first (most reliable if available)
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    error_log('[Analytics Debug] getallheaders() Authorization: ' . ($authHeader ?? 'NULL'));
}

// Method 2: Fallback to $_SERVER if getallheaders() didn't work
if (empty($authHeader)) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    error_log('[Analytics Debug] $_SERVER[HTTP_AUTHORIZATION]: ' . ($authHeader ?? 'NULL'));
}

// Method 3: Fallback to REDIRECT_HTTP_AUTHORIZATION (Apache rewrite)
if (empty($authHeader)) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    error_log('[Analytics Debug] $_SERVER[REDIRECT_HTTP_AUTHORIZATION]: ' . ($authHeader ?? 'NULL'));
}

// Method 4: Fallback to apache_request_headers() if available
if (empty($authHeader) && function_exists('apache_request_headers')) {
    $apacheHeaders = apache_request_headers();
    $authHeader = $apacheHeaders['Authorization'] ?? $apacheHeaders['authorization'] ?? null;
    error_log('[Analytics Debug] apache_request_headers() Authorization: ' . ($authHeader ?? 'NULL'));
}

// DEBUG: Log ALL server variables related to Authorization
error_log('[Analytics Debug] ALL $_SERVER KEYS: ' . implode(', ', array_keys($_SERVER)));
error_log('[Analytics Debug] Final Auth Header: ' . ($authHeader ?? 'NULL'));

// Final check
if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'No token provided',
        'debug' => [
            'getallheaders_exists' => function_exists('getallheaders'),
            'apache_request_headers_exists' => function_exists('apache_request_headers'),
            'checked_sources' => ['getallheaders', '$_SERVER[HTTP_AUTHORIZATION]', '$_SERVER[REDIRECT_HTTP_AUTHORIZATION]', 'apache_request_headers'],
            'hint' => 'Check .htaccess for Authorization header pass-through'
        ]
    ]);
    ob_end_flush();
    exit;
}

// Extract token from "Bearer <token>"
if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid authorization format']);
    ob_end_flush();
    exit;
}

$token = $matches[1];
$user = JWT::verify($token); // CRITICAL: Use JWT::verify() not verifyJWT()

// DEBUG: Log token verification result
error_log('[Analytics Debug] Token: ' . substr($token, 0, 50) . '...');
error_log('[Analytics Debug] JWT::verify() result: ' . ($user ? 'SUCCESS' : 'FAILED'));
if ($user) {
    error_log('[Analytics Debug] User Data: ' . json_encode($user));
    error_log('[Analytics Debug] User Role: ' . ($user['role'] ?? 'NULL'));
    error_log('[Analytics Debug] Role Check: role=' . ($user['role'] ?? 'NULL') . ', expected=admin, match=' . (($user['role'] ?? '') === 'admin' ? 'YES' : 'NO'));
}

if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Admin access required',
        'debug' => [
            'token_verified' => $user ? true : false,
            'user_role' => $user['role'] ?? null,
            'expected_role' => 'admin',
            'user_data' => $user ?? null
        ]
    ]);
    ob_end_flush();
    exit;
}

// Get database connection
$db = Database::getConnection();

try {

$period = $_GET['period'] ?? '30days'; // 7days, 30days, 90days, 1year, all
$startDate = null;
$endDate = date('Y-m-d 23:59:59');

switch ($period) {
    case '7days':
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case '30days':
        $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        break;
    case '90days':
        $startDate = date('Y-m-d 00:00:00', strtotime('-90 days'));
        break;
    case '1year':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 year'));
        break;
    case 'all':
        $startDate = '2000-01-01 00:00:00';
        break;
    default:
        $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
}

// Revenue Analytics
$revenueQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        AVG(CASE WHEN status = 'completed' THEN final_amount ELSE NULL END) as avg_order_value,
        MAX(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as highest_order
    FROM orders
    WHERE created_at BETWEEN ? AND ?
";
$stmt = $db->prepare($revenueQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$revenueData = $stmt->get_result()->fetch_assoc();

// Daily Revenue Trend
$dailyRevenueQuery = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as revenue
    FROM orders
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$stmt = $db->prepare($dailyRevenueQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$dailyRevenue = [];
while ($row = $result->fetch_assoc()) {
    $dailyRevenue[] = $row;
}

// Top Products
$productQuery = "
    SELECT 
        p.id,
        p.name,
        p.category_id,
        p.base_price as price,
        COUNT(oi.id) as times_ordered,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.unit_price) as total_revenue
    FROM products p
    LEFT JOIN order_items oi ON p.ID = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
    WHERE o.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_revenue DESC
    LIMIT 10
";
$stmt = $db->prepare($productQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$topProducts = [];
while ($row = $result->fetch_assoc()) {
    $topProducts[] = $row;
}

// Category Performance
$categoryQuery = "
    SELECT 
        p.category_id,
        COUNT(DISTINCT oi.order_id) as orders,
        SUM(oi.quantity) as items_sold,
        SUM(oi.quantity * oi.unit_price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' AND o.created_at BETWEEN ? AND ?
    GROUP BY p.category_id
    ORDER BY revenue DESC
";
$stmt = $db->prepare($categoryQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$categoryPerformance = [];
while ($row = $result->fetch_assoc()) {
    $categoryPerformance[] = $row;
}

// Customer Analytics
$customerQuery = "
    SELECT 
        COUNT(DISTINCT id) as total_customers,
        COUNT(DISTINCT CASE WHEN created_at BETWEEN ? AND ? THEN id END) as new_customers
    FROM users
    WHERE role = 'customer'
";
$stmt = $db->prepare($customerQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$customerData = $stmt->get_result()->fetch_assoc();

// Top Customers
$topCustomersQuery = "
    SELECT 
        u.id,
        u.name,
        u.email,
        COUNT(o.id) as total_orders,
        SUM(CASE WHEN o.status = 'completed' THEN o.final_amount ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'customer' AND o.created_at BETWEEN ? AND ?
    GROUP BY u.id
    HAVING total_spent > 0
    ORDER BY total_spent DESC
    LIMIT 10
";
$stmt = $db->prepare($topCustomersQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$topCustomers = [];
while ($row = $result->fetch_assoc()) {
    $topCustomers[] = $row;
}

// Order Status Distribution
$statusQuery = "
    SELECT 
        status,
        COUNT(*) as count
    FROM orders
    WHERE created_at BETWEEN ? AND ?
    GROUP BY status
";
$stmt = $db->prepare($statusQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$orderStatus = [];
while ($row = $result->fetch_assoc()) {
    $orderStatus[] = $row;
}

// Payment Method Distribution
$paymentQuery = "
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as revenue
    FROM orders
    WHERE created_at BETWEEN ? AND ?
    GROUP BY payment_method
";
$stmt = $db->prepare($paymentQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$paymentMethods = [];
while ($row = $result->fetch_assoc()) {
    $paymentMethods[] = $row;
}

// Peak Hours
$peakHoursQuery = "
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as revenue
    FROM orders
    WHERE created_at BETWEEN ? AND ?
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
";
$stmt = $db->prepare($peakHoursQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$peakHours = [];
while ($row = $result->fetch_assoc()) {
    $peakHours[] = $row;
}

// Growth Comparison
$periodDays = (strtotime($endDate) - strtotime($startDate)) / 86400;
$prevStartDate = date('Y-m-d 00:00:00', strtotime($startDate . ' -' . ceil($periodDays) . ' days'));
$prevEndDate = $startDate;

$growthQuery = "
    SELECT 
        SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as revenue,
        COUNT(*) as orders
    FROM orders
    WHERE created_at BETWEEN ? AND ?
";
$stmt = $db->prepare($growthQuery);
$stmt->bind_param("ss", $prevStartDate, $prevEndDate);
$stmt->execute();
$prevPeriodData = $stmt->get_result()->fetch_assoc();

$revenueGrowth = 0;
$orderGrowth = 0;
if ($prevPeriodData['revenue'] > 0) {
    $revenueGrowth = (($revenueData['total_revenue'] - $prevPeriodData['revenue']) / $prevPeriodData['revenue']) * 100;
}
if ($prevPeriodData['orders'] > 0) {
    $orderGrowth = (($revenueData['total_orders'] - $prevPeriodData['orders']) / $prevPeriodData['orders']) * 100;
}

// Review Stats
$reviewQuery = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE created_at BETWEEN ? AND ?";
$stmt = $db->prepare($reviewQuery);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$reviewData = $stmt->get_result()->fetch_assoc();

// Response
echo json_encode([
    'success' => true,
    'period' => $period,
    'date_range' => [
        'start' => $startDate,
        'end' => $endDate
    ],
    'revenue' => [
        'total' => floatval($revenueData['total_revenue'] ?? 0),
        'avg_order_value' => floatval($revenueData['avg_order_value'] ?? 0),
        'highest_order' => floatval($revenueData['highest_order'] ?? 0),
        'growth_percentage' => round($revenueGrowth, 2)
    ],
    'orders' => [
        'total' => intval($revenueData['total_orders'] ?? 0),
        'completed' => intval($revenueData['completed_orders'] ?? 0),
        'cancelled' => intval($revenueData['cancelled_orders'] ?? 0),
        'growth_percentage' => round($orderGrowth, 2),
        'status_distribution' => $orderStatus
    ],
    'customers' => [
        'total' => intval($customerData['total_customers'] ?? 0),
        'new' => intval($customerData['new_customers'] ?? 0),
        'top_customers' => $topCustomers
    ],
    'products' => [
        'top_selling' => $topProducts,
        'category_performance' => $categoryPerformance
    ],
    'trends' => [
        'daily_revenue' => $dailyRevenue,
        'peak_hours' => $peakHours
    ],
    'payment_methods' => $paymentMethods,
    'reviews' => [
        'avg_rating' => round(floatval($reviewData['avg_rating'] ?? 0), 2),
        'total' => intval($reviewData['total_reviews'] ?? 0)
    ]
]);

// Flush output buffer and exit
ob_end_flush();

} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch analytics data',
        'message' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
    ]);
    ob_end_flush();
}
?>
