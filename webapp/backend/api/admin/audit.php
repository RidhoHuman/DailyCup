<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../audit_log.php';

header('Content-Type: application/json');

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? 'list';
if ($action === 'list') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $logs = AuditLog::getLogs($date);
    echo json_encode(['success'=>true,'date'=>$date,'logs'=>$logs]);
    exit;
}

if ($action === 'export') {
    $date = $_GET['date'] ?? date('Y-m-d');
    $logs = AuditLog::getLogs($date);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_' . $date . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id','timestamp','action','level','user_id','ip','data']);
    foreach ($logs as $l) {
        fputcsv($out, [$l['id'],$l['timestamp'],$l['action'],$l['level'],$l['user_id'],$l['ip'],json_encode($l['data'])]);
    }
    exit;
}

http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid action']); exit;'@
Write-File $path $content

# backend/api/admin/credentials.php
$path = 'webapp/backend/api/admin/credentials.php'
$content = @'
<?php
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../lib/sms_providers.php';
require_once __DIR__ . '/../audit_log.php';

header('Content-Type: application/json');

$user = validateToken();
if (!$user || $user['role'] !== 'admin') { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Admin access required']); exit; }

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'test') {
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'POST required']); exit; }
    $provider = $_POST['provider'] ?? null;
    if (!$provider) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'provider required']); exit; }

    // Optionally allow passing account_sid/auth_token for immediate test (useful for UI test)
    $params = [];
    if (!empty($_POST['account_sid'])) $params['account_sid'] = $_POST['account_sid'];
    if (!empty($_POST['auth_token'])) $params['auth_token'] = $_POST['auth_token'];

    $res = test_provider_credentials($provider, $params);
    AuditLog::log('PROVIDER_CREDENTIAL_TEST', ['provider'=>$provider,'result'=>$res], $user['id']);
    echo json_encode(['success'=>true,'result'=>$res]);
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Invalid action']);
exit;
