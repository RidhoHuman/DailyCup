<?php
require_once __DIR__ . '/../lib/sms_providers.php';
require_once __DIR__ . '/../api/audit_log.php';

echo "Running MVP smoke tests...\n";

// 1) Provider credentials test (will use env vars if present)
echo "- Testing provider credentials (twilio)...\n";
$res1 = test_provider_credentials('twilio');
if ($res1['success']) {
    echo "  OK: Twilio credentials valid\n";
} else {
    echo "  WARN: Twilio credential test failed/skipped: " . ($res1['error'] ?? json_encode($res1)) . "\n";
}

// 2) Incremental refresh (last 1 day)
echo "- Running incremental refresh (1 day)...\n";
$php = PHP_BINARY ?? 'php';
$script = __DIR__ . '/refresh_analytics_materialized.php';
$cmd = escapeshellcmd($php) . ' ' . escapeshellarg($script) . ' 1 2>&1';
$out = [];
$code = 0;
exec($cmd, $out, $code);
echo implode("\n", $out) . "\n";
if ($code === 0) echo "  OK: Incremental refresh succeeded\n"; else echo "  FAIL: Incremental refresh failed (exit $code)\n";

// 3) Send mock message
echo "- Sending mock message via provider 'mock'...\n";
$send = send_sms_via_provider('mock', ['to'=>'whatsapp:+1000000000','from'=>'whatsapp:+1000000000','body'=>'smoke test']);
if (!empty($send['success'])) echo "  OK: mock send succeeded (sid: {$send['sid']})\n"; else echo "  FAIL: mock send failed: " . json_encode($send) . "\n";

// 4) Test broadcast to segment 'new' (mock)
echo "- Testing broadcast to 'new' segment (mock)...\n";
$cmd = escapeshellcmd(PHP_BINARY ?? 'php') . ' ' . escapeshellarg(__DIR__ . '/test_broadcast.php') . ' 2>&1';
$ob = [];$c=0; exec($cmd, $ob, $c);
echo implode("\n", $ob) . "\n";
if ($c === 0) echo "  OK: broadcast script ran\n"; else echo "  WARN: broadcast script returned $c\n";

// 4) Basic AuditLog sanity
$logs = AuditLog::getLogs();
$last = $logs ? $logs[count($logs)-1] : null;
if ($last) echo "- Latest AuditLog action: {$last['action']} at {$last['timestamp']}\n";

echo "Smoke tests completed.\n";
exit(0);
