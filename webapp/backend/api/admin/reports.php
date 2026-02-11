<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../audit_log.php';

header('Content-Type: application/json');

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? 'top';

if ($action === 'top') {
    // Top 10 customers by total spend (last 30 days by default)
    $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $to = $_GET['to'] ?? date('Y-m-d');

    $stmt = $db->prepare("SELECT u.id, u.name, u.email, COALESCE(SUM(o.total),0) as total_spend, COUNT(o.id) as orders_count FROM users u JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY u.id ORDER BY total_spend DESC LIMIT 10");
    $stmt->bind_param('ss', $from, $to);
    $stmt->execute();
    $res = $stmt->get_result(); $topCustomers = [];
    while ($r = $res->fetch_assoc()) $topCustomers[] = $r;

    // Top products by quantity
    $pstmt = $db->prepare("SELECT p.id, p.name, SUM(oi.quantity) as qty_sold, COUNT(DISTINCT o.user_id) as unique_buyers FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON p.ID = oi.product_id WHERE DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.id ORDER BY qty_sold DESC LIMIT 10");
    $pstmt->bind_param('ss', $from, $to);
    $pstmt->execute();
    $pres = $pstmt->get_result(); $topProducts = [];
    while ($r = $pres->fetch_assoc()) $topProducts[] = $r;

    echo json_encode(['success'=>true,'from'=>$from,'to'=>$to,'top_customers'=>$topCustomers,'top_products'=>$topProducts]);
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action']);
exit;
