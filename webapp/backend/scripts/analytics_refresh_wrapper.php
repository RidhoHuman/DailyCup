<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/audit_log.php';

// Wrapper for analytics refresh with logging and alerting
$days = null;
if (isset($argv) && count($argv) > 1) {
    $arg = $argv[1];
    if (is_numeric($arg)) { $days = intval($arg); }
}

$php = PHP_BINARY ?? 'php';
$script = __DIR__ . '/refresh_analytics_materialized.php';
$cmd = escapeshellcmd($php) . ' ' . escapeshellarg($script);
if ($days && $days > 0) { $cmd .= ' ' . escapeshellarg((string)$days); }
$cmd .= ' 2>&1';

$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/analytics_refresh.log';

$out = [];
$code = 0;
exec($cmd, $out, $code);

$entry = "[" . date('Y-m-d H:i:s') . "] CMD: {$cmd} EXIT: {$code}\n" . implode("\n", $out) . "\n\n";
file_put_contents($logFile, $entry, FILE_APPEND);

// Textfile metrics (Prometheus): write a small metrics file that can be collected by node_exporter textfile collector
$metricsDir = __DIR__ . '/../../metrics';
if (!is_dir($metricsDir)) mkdir($metricsDir, 0755, true);
$metricsFile = $metricsDir . '/analytics_refresh.prom';

// Load previous failed count if exists
$failedCount = 0;
if (file_exists($metricsFile)) {
    $m = file($metricsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($m as $line) {
        if (strpos($line, 'analytics_refresh_failed_total') === 0) {
            $parts = preg_split('/\s+/', $line);
            $failedCount = intval($parts[1] ?? 0);
        }
    }
}

if ($code !== 0) {
    // increment failed count
    $failedCount++;
    $metrics = [];
    $metrics[] = "# HELP analytics_refresh_last_success_timestamp Last successful analytics refresh (unix timestamp)";
    $metrics[] = "# TYPE analytics_refresh_last_success_timestamp gauge";
    $metrics[] = "analytics_refresh_last_success_timestamp 0";
    $metrics[] = "# HELP analytics_refresh_failed_total Total failed refresh runs";
    $metrics[] = "# TYPE analytics_refresh_failed_total counter";
    $metrics[] = "analytics_refresh_failed_total {$failedCount}";
    file_put_contents($metricsFile, implode("\n", $metrics) . "\n");

    AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH_FAILED', ['exit_code'=>$code,'output'=>array_slice($out,0,50)], null, 'error');
    AuditLog::logSecurityAlert('ANALYTICS_REFRESH_FAILED', ['exit_code'=>$code,'output'=>substr(implode("\n",$out),0,2000)]);
    echo "FAILED\n";
    exit($code);
}

// Success: update last_success timestamp (do not reset counter)
$lastTs = time();
$metrics = [];
$metrics[] = "# HELP analytics_refresh_last_success_timestamp Last successful analytics refresh (unix timestamp)";
$metrics[] = "# TYPE analytics_refresh_last_success_timestamp gauge";
$metrics[] = "analytics_refresh_last_success_timestamp {$lastTs}";
$metrics[] = "# HELP analytics_refresh_failed_total Total failed refresh runs";
$metrics[] = "# TYPE analytics_refresh_failed_total counter";
$metrics[] = "analytics_refresh_failed_total {$failedCount}";
file_put_contents($metricsFile, implode("\n", $metrics) . "\n");

AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH', ['days'=>$days,'updated_at'=>date('Y-m-d H:i:s')]);
echo "OK\n";
exit(0);
