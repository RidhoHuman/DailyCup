<?php
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../audit_log.php';

header('Content-Type: application/json');

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'list') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $segment = $_GET['segment'] ?? null; // new, vip, passive

    // Build base query with total spend and last order
    $sql = "SELECT u.id, u.name, u.email, u.phone, u.avatar, COALESCE(SUM(o.total),0) AS total_spend, COUNT(o.id) AS orders_count, MIN(u.created_at) as joined_at, MAX(o.created_at) AS last_order_at
            FROM users u
            LEFT JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled'";
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $s = '%' . $search . '%';
        $params[] = $s; $params[] = $s; $params[] = $s;
    }

    $groupHaving = '';
    if ($segment) {
        if ($segment === 'new') {
            $where[] = "u.created_at >= (NOW() - INTERVAL 30 DAY)";
        } elseif ($segment === 'vip') {
            // VIP: total_spend > 500000
            $groupHaving = "HAVING total_spend > 500000";
        } elseif ($segment === 'passive') {
            $where[] = "(COALESCE(MAX(o.created_at), u.created_at) < (NOW() - INTERVAL 60 DAY))";
        }
    }

    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }

    $sql .= " GROUP BY u.id " . $groupHaving . " ORDER BY total_spend DESC LIMIT ? OFFSET ?";

    $stmt = $db->prepare($sql);
    // bind params dynamically
    $types = '';
    $bindParams = [];
    foreach ($params as $p) { $types .= 's'; $bindParams[] = $p; }
    $types .= 'ii';
    $bindParams[] = $limit; $bindParams[] = $offset;

    if ($types) {
        $a_params = array_merge([$types], $bindParams);
        $tmp = [];
        foreach ($a_params as $k => $v) { $tmp[$k] = &$a_params[$k]; }
        call_user_func_array([$stmt, 'bind_param'], $tmp);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }

    // total count (approx)
    $countSql = "SELECT COUNT(*) as cnt FROM users u";
    if ($search !== '') { $countSql .= " WHERE (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)"; }
    $cstmt = $db->prepare($countSql);
    if ($search !== '') { $s = '%' . $search . '%'; $cstmt->bind_param('sss', $s, $s, $s); }
    $cstmt->execute();
    $total = intval($cstmt->get_result()->fetch_assoc()['cnt'] ?? 0);

    echo json_encode(['success'=>true,'page'=>$page,'limit'=>$limit,'total'=>$total,'customers'=>$rows]);
    exit;
}

if ($action === 'detail') {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'id required']); exit; }

    $stmt = $db->prepare("SELECT id, name, email, phone, avatar, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
    if (!$userRow) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'not found']); exit; }

    $ordersStmt = $db->prepare("SELECT id, order_number, total, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
    $ordersStmt->bind_param('i', $id);
    $ordersStmt->execute();
    $orders = [];
    $ores = $ordersStmt->get_result();
    while ($o = $ores->fetch_assoc()) { $orders[] = $o; }

    // total spend
    $ts = $db->prepare("SELECT COALESCE(SUM(total),0) as total_spend FROM orders WHERE user_id = ? AND status <> 'cancelled'");
    $ts->bind_param('i', $id);
    $ts->execute();
    $total_spend = floatval($ts->get_result()->fetch_assoc()['total_spend'] ?? 0);

    echo json_encode(['success'=>true,'customer'=>$userRow,'orders'=>$orders,'total_spend'=>$total_spend]);
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action']);
exit;
