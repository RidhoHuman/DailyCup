<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Basic DB check
    $stmt = $pdo->query('SELECT 1');
    $ok = $stmt->fetchColumn();

    // Check GET_LOCK availability
    $r = $pdo->query("SELECT GET_LOCK('test_worker_sanity_lock', 1) as got")->fetch(PDO::FETCH_ASSOC);
    $got = !empty($r['got']);
    if ($got) { $pdo->query("SELECT RELEASE_LOCK('test_worker_sanity_lock')"); }

    echo json_encode(['success'=>true,'db_ok'=>boolval($ok),'get_lock'=>boolval($got)]);
    exit(0);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    exit(1);
}
