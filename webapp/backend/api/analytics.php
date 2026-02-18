<?php
// -----------------------------------------------------------------------------
// 1. INCLUDE CENTRAL CORS (WAJIB PALING ATAS)
// -----------------------------------------------------------------------------
// Mundur 1 langkah (../) karena file ini ada di folder 'api', dan 'cors.php' di 'backend'
require_once __DIR__ . '/../cors.php'; 

/**
 * Analytics API endpoint - Order/Revenue analytics
 */

// -----------------------------------------------------------------------------
// 2. ERROR HANDLER (Disederhanakan karena CORS sudah dihandle cors.php)
// -----------------------------------------------------------------------------
set_exception_handler(function($e){
    // Tidak perlu panggil fungsi cors lagi, karena sudah diset di atas
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Internal server error','error'=>$e->getMessage()]);
    exit;
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Internal server error','error'=>"$errstr in $errfile:$errline"]);
    exit;
});

// -----------------------------------------------------------------------------
// 3. LOGIKA UTAMA
// -----------------------------------------------------------------------------
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$db = Database::getConnection();

header('Content-Type: application/json');

// (Hapus blok OPTIONS manual, karena sudah dihandle oleh cors.php)

// Require admin auth
$user = validateToken();
if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$period = $_GET['period'] ?? '30days';

// Calculate date range based on period
$endDate = date('Y-m-d');
$endDateTime = date('Y-m-d H:i:s');
switch ($period) {
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $prevStartDate = date('Y-m-d', strtotime('-14 days'));
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $prevStartDate = date('Y-m-d', strtotime('-60 days'));
        break;
    case '90days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $prevStartDate = date('Y-m-d', strtotime('-180 days'));
        break;
    case '1year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        $prevStartDate = date('Y-m-d', strtotime('-2 years'));
        break;
    case 'all':
        $startDate = '2000-01-01';
        $prevStartDate = '2000-01-01';
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $prevStartDate = date('Y-m-d', strtotime('-60 days'));
}

// ========== REVENUE ==========
$revenueData = ['total' => 0, 'avg_order_value' => 0, 'highest_order' => 0, 'growth_percentage' => 0];
try {
    $stmt = $db->prepare("SELECT 
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(total_amount), 0) as avg_order_value,
        COALESCE(MAX(total_amount), 0) as highest_order
        FROM orders WHERE created_at >= ? AND created_at <= ?");
    $stmt->bind_param('ss', $startDate, $endDateTime);
    $stmt->execute();
    $rev = $stmt->get_result()->fetch_assoc();
    $revenueData['total'] = floatval($rev['total_revenue']);
    $revenueData['avg_order_value'] = floatval($rev['avg_order_value']);
    $revenueData['highest_order'] = floatval($rev['highest_order']);

    // Previous period for growth
    $stmt2 = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as prev_revenue FROM orders WHERE created_at >= ? AND created_at < ?");
    $stmt2->bind_param('ss', $prevStartDate, $startDate);
    $stmt2->execute();
    $prev = $stmt2->get_result()->fetch_assoc();
    $prevRevenue = floatval($prev['prev_revenue']);
    if ($prevRevenue > 0) {
        $revenueData['growth_percentage'] = round((($revenueData['total'] - $prevRevenue) / $prevRevenue) * 100, 2);
    } elseif ($revenueData['total'] > 0) {
        $revenueData['growth_percentage'] = 100;
    }
} catch (Throwable $e) { /* graceful fallback */ }

// ========== ORDERS ==========
$ordersData = ['total' => 0, 'completed' => 0, 'cancelled' => 0, 'growth_percentage' => 0, 'status_distribution' => []];
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' OR status = 'delivered' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM orders WHERE created_at >= ? AND created_at <= ?");
    $stmt->bind_param('ss', $startDate, $endDateTime);
    $stmt->execute();
    $ord = $stmt->get_result()->fetch_assoc();
    $ordersData['total'] = intval($ord['total']);
    $ordersData['completed'] = intval($ord['completed']);
    $ordersData['cancelled'] = intval($ord['cancelled']);

    // Previous period growth
    $stmt2 = $db->prepare("SELECT COUNT(*) as prev_total FROM orders WHERE created_at >= ? AND created_at < ?");
    $stmt2->bind_param('ss', $prevStartDate, $startDate);
    $stmt2->execute();
    $prevOrd = $stmt2->get_result()->fetch_assoc();
    $prevTotal = intval($prevOrd['prev_total']);
    if ($prevTotal > 0) {
        $ordersData['growth_percentage'] = round((($ordersData['total'] - $prevTotal) / $prevTotal) * 100, 2);
    } elseif ($ordersData['total'] > 0) {
        $ordersData['growth_percentage'] = 100;
    }

    // Status distribution
    $stmt3 = $db->prepare("SELECT status, COUNT(*) as count FROM orders WHERE created_at >= ? AND created_at <= ? GROUP BY status ORDER BY count DESC");
    $stmt3->bind_param('ss', $startDate, $endDateTime);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    while ($r = $res3->fetch_assoc()) {
        $ordersData['status_distribution'][] = $r;
    }
} catch (Throwable $e) { /* graceful fallback */ }

// ========== CUSTOMERS ==========
$customersData = ['total' => 0, 'new' => 0, 'top_customers' => []];
try {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stmt->execute();
    $customersData['total'] = intval($stmt->get_result()->fetch_assoc()['total']);

    $stmt2 = $db->prepare("SELECT COUNT(*) as new_customers FROM users WHERE role = 'customer' AND created_at >= ? AND created_at <= ?");
    $stmt2->bind_param('ss', $startDate, $endDateTime);
    $stmt2->execute();
    $customersData['new'] = intval($stmt2->get_result()->fetch_assoc()['new_customers']);

    // Top customers
    $stmt3 = $db->prepare("SELECT u.id, u.name, u.email, COUNT(o.id) as total_orders, COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM users u JOIN orders o ON u.id = o.user_id
        WHERE o.created_at >= ? AND o.created_at <= ?
        GROUP BY u.id ORDER BY total_spent DESC LIMIT 5");
    $stmt3->bind_param('ss', $startDate, $endDateTime);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    while ($r = $res3->fetch_assoc()) {
        $r['total_spent'] = floatval($r['total_spent']);
        $r['total_orders'] = intval($r['total_orders']);
        $customersData['top_customers'][] = $r;
    }
} catch (Throwable $e) { /* graceful fallback */ }

// ========== PRODUCTS ==========
$productsData = ['top_selling' => [], 'category_performance' => []];
try {
    $stmt = $db->prepare("SELECT p.id, p.name, COALESCE(c.name, 'Uncategorized') as category, p.price,
        COUNT(oi.id) as times_ordered, COALESCE(SUM(oi.quantity), 0) as total_quantity,
        COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= ? AND o.created_at <= ?
        GROUP BY p.id ORDER BY total_revenue DESC LIMIT 10");
    $stmt->bind_param('ss', $startDate, $endDateTime);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $r['price'] = floatval($r['price']);
        $r['total_revenue'] = floatval($r['total_revenue']);
        $r['total_quantity'] = intval($r['total_quantity']);
        $r['times_ordered'] = intval($r['times_ordered']);
        $productsData['top_selling'][] = $r;
    }

    // Category performance
    $stmt2 = $db->prepare("SELECT COALESCE(c.name, 'Uncategorized') as category,
        COUNT(DISTINCT o.id) as orders, COALESCE(SUM(oi.quantity), 0) as items_sold,
        COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at >= ? AND o.created_at <= ?
        GROUP BY c.id ORDER BY revenue DESC");
    $stmt2->bind_param('ss', $startDate, $endDateTime);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        $r['orders'] = intval($r['orders']);
        $r['items_sold'] = intval($r['items_sold']);
        $r['revenue'] = floatval($r['revenue']);
        $productsData['category_performance'][] = $r;
    }
} catch (Throwable $e) { /* graceful fallback */ }

// ========== TRENDS ==========
$trendsData = ['daily_revenue' => [], 'peak_hours' => []];
try {
    $stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
        FROM orders WHERE created_at >= ? AND created_at <= ?
        GROUP BY DATE(created_at) ORDER BY date ASC");
    $stmt->bind_param('ss', $startDate, $endDateTime);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $r['orders'] = intval($r['orders']);
        $r['revenue'] = floatval($r['revenue']);
        $trendsData['daily_revenue'][] = $r;
    }

    // Peak hours
    $stmt2 = $db->prepare("SELECT HOUR(created_at) as hour, COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
        FROM orders WHERE created_at >= ? AND created_at <= ?
        GROUP BY HOUR(created_at) ORDER BY hour ASC");
    $stmt2->bind_param('ss', $startDate, $endDateTime);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($r = $res2->fetch_assoc()) {
        $r['hour'] = intval($r['hour']);
        $r['orders'] = intval($r['orders']);
        $r['revenue'] = floatval($r['revenue']);
        $trendsData['peak_hours'][] = $r;
    }
} catch (Throwable $e) { /* graceful fallback */ }

// ========== PAYMENT METHODS ==========
$paymentMethods = [];
try {
    $stmt = $db->prepare("SELECT COALESCE(payment_method, 'Unknown') as payment_method, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as revenue
        FROM orders WHERE created_at >= ? AND created_at <= ?
        GROUP BY payment_method ORDER BY count DESC");
    $stmt->bind_param('ss', $startDate, $endDateTime);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $r['count'] = intval($r['count']);
        $r['revenue'] = floatval($r['revenue']);
        $paymentMethods[] = $r;
    }
} catch (Throwable $e) { /* graceful fallback */ }

// ========== REVIEWS ==========
$reviewsData = ['avg_rating' => 0, 'total' => 0];
try {
    // Try product_reviews table first, then reviews table
    $tableCheck = $db->query("SHOW TABLES LIKE 'product_reviews'");
    $reviewTable = $tableCheck->num_rows > 0 ? 'product_reviews' : 'reviews';
    
    $stmt = $db->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total FROM `$reviewTable` WHERE created_at >= ? AND created_at <= ?");
    $stmt->bind_param('ss', $startDate, $endDateTime);
    $stmt->execute();
    $rev = $stmt->get_result()->fetch_assoc();
    $reviewsData['avg_rating'] = round(floatval($rev['avg_rating']), 1);
    $reviewsData['total'] = intval($rev['total']);
} catch (Throwable $e) { /* graceful fallback */ }

// ========== BUILD RESPONSE ==========
echo json_encode([
    'success' => true,
    'period' => $period,
    'date_range' => ['start' => $startDate, 'end' => $endDate],
    'revenue' => $revenueData,
    'orders' => $ordersData,
    'customers' => $customersData,
    'products' => $productsData,
    'trends' => $trendsData,
    'payment_methods' => $paymentMethods,
    'reviews' => $reviewsData,
]);
exit;