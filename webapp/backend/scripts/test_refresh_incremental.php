<?php
// Simple sanity test for refresh_analytics_materialized incremental mode
$php = PHP_BINARY ?? 'php';
$script = __DIR__ . '/refresh_analytics_materialized.php';
$cmd = escapeshellcmd($php) . ' ' . escapeshellarg($script) . ' 3 2>&1';
$output = [];
$code = 0;
exec($cmd, $output, $code);
echo "Command: $cmd\n";
echo "Exit code: $code\n";
echo "Output:\n" . implode("\n", $output) . "\n";
if ($code !== 0) { echo "FAIL\n"; exit(1); }
// basic success check
if (preg_grep('/Incremental refresh complete/', $output)) { echo "OK\n"; exit(0); }
echo "Unexpected output, check logs\n";
exit(2);
