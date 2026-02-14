<?php
require __DIR__ . '/../config/database.php';

function ok($v){ return $v ? 'YES' : 'NO'; }

echo "Connected DB: " . DB_NAME . "\n\n";

$checks = [
    'migrations_table' => "SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='migrations'",
    'migration_20260213_analytics_kpis' => "SELECT COUNT(*) as c FROM migrations WHERE filename='20260213_analytics_kpis.sql'",
    'migration_20260213_analytics_materialized' => "SELECT COUNT(*) as c FROM migrations WHERE filename='20260213_analytics_materialized.sql'",
    'migration_20260213_orders_analytics' => "SELECT COUNT(*) as c FROM migrations WHERE filename='20260213_orders_analytics.sql'",
    'migration_20260212_twilio_fields' => "SELECT COUNT(*) as c FROM migrations WHERE filename='20260212_twilio_message_fields.sql'",
    'view_integration_messages_daily' => "SELECT COUNT(*) as c FROM information_schema.views WHERE table_schema=DATABASE() AND table_name='analytics_integration_messages_daily'",
    'view_integration_messages_recent' => "SELECT COUNT(*) as c FROM information_schema.views WHERE table_schema=DATABASE() AND table_name='analytics_integration_messages_recent'",
    'table_analytics_mat' => "SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='analytics_integration_messages_daily_mat'",
    'view_orders_daily' => "SELECT COUNT(*) as c FROM information_schema.views WHERE table_schema=DATABASE() AND table_name='analytics_orders_daily'",
    'col_retry_count' => "SELECT COUNT(*) as c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='integration_messages' AND column_name='retry_count'",
    'table_categories' => "SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='categories'",
];

foreach ($checks as $k => $sql) {
    try {
        $v = (int)$pdo->query($sql)->fetchColumn();
        echo str_pad($k, 45) . " : " . $v . "\n";
    } catch (Exception $e) {
        echo str_pad($k, 45) . " : ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\nTip: run 'php apply_migrations.php' to apply missing migrations.\n";