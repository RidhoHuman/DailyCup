<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/audit_log.php';

try {
    // Optional CLI arg: days (integer) to run incremental refresh for last N days
    $days = null;
    if (isset($argv) && count($argv) > 1) {
        $arg = $argv[1];
        if (is_numeric($arg)) { $days = intval($arg); }
    }

    if ($days && $days > 0) {
        // Incremental: delete the affected day range from mat table and repopulate
        $from = date('Y-m-d', strtotime("-{$days} days"));
        echo "Incremental refresh for last {$days} days from {$from}\n";
        $pdo->beginTransaction();
        $delStmt = $pdo->prepare('DELETE FROM analytics_integration_messages_daily_mat WHERE day >= :from');
        $delStmt->execute([':from' => $from]);

        $sql = "INSERT INTO analytics_integration_messages_daily_mat (provider, day, channel, total_messages, delivered_count, failed_count, retry_scheduled, avg_retry_count)
                SELECT provider, DATE(created_at) as day, channel, SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as total_messages, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count, SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) as retry_scheduled, AVG(COALESCE(retry_count,0)) as avg_retry_count
                FROM integration_messages
                WHERE DATE(created_at) >= :from
                GROUP BY provider, DATE(created_at), channel";
        $ins = $pdo->prepare($sql);
        $ins->execute([':from' => $from]);
        $pdo->commit();

        AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH_INCREMENTAL', ['days'=>$days,'from'=>$from,'updated_at'=>date('Y-m-d H:i:s')]);
        echo "Incremental refresh complete\n";
    } else {
        // Full refresh: TRUNCATE is not always transaction-safe in MySQL; run without transaction
        echo "Full refresh (truncate + repopulate)\n";
        $pdo->exec('TRUNCATE TABLE analytics_integration_messages_daily_mat');
        $sql = "INSERT INTO analytics_integration_messages_daily_mat (provider, day, channel, total_messages, delivered_count, failed_count, retry_scheduled, avg_retry_count)
                SELECT provider, DATE(created_at) as day, channel, SUM(CASE WHEN direction = 'outbound' THEN 1 ELSE 0 END) as total_messages, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count, SUM(CASE WHEN status = 'retry_scheduled' THEN 1 ELSE 0 END) as retry_scheduled, AVG(COALESCE(retry_count,0)) as avg_retry_count
                FROM integration_messages
                GROUP BY provider, DATE(created_at), channel";
        $pdo->exec($sql);
        AuditLog::log('ANALYTICS_MATERIALIZED_REFRESH', ['updated_at'=>date('Y-m-d H:i:s')]);
        echo "Refreshed materialized analytics table\n";
    }
} catch (Exception $e) {
    // If we started a transaction, ensure rollback
    try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Exception $ex) {}
    AuditLog::logApiError('refresh_analytics_materialized', $e->getMessage());
    echo "Failed: " . $e->getMessage() . "\n";
}
