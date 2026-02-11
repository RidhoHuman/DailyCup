<?php
require_once __DIR__ . '/../api/audit_log.php';

$hoursThreshold = $argv[1] ?? 24;
$hoursThreshold = intval($hoursThreshold);

$logs = AuditLog::getLogsByAction('ANALYTICS_MATERIALIZED_REFRESH', 1);
if (empty($logs)) {
    AuditLog::logSecurityAlert('ANALYTICS_REFRESH_STALE', ['note' => 'No successful refresh entries found', 'threshold_hours' => $hoursThreshold]);
    echo "STALE: no runs found\n";
    exit(2);
}

$last = $logs[0];
$ts = $last['timestamp'];
$lastTime = strtotime($ts);
$now = time();
$diffHours = ($now - $lastTime) / 3600.0;

if ($diffHours > $hoursThreshold) {
    AuditLog::logSecurityAlert('ANALYTICS_REFRESH_STALE', ['last_run' => $ts, 'hours_ago' => round($diffHours,2), 'threshold_hours' => $hoursThreshold]);
    echo "STALE: last run {$ts}, {$diffHours} hours ago\n";
    exit(2);
}

echo "OK: last run {$ts}, {$diffHours} hours ago\n";
exit(0);
