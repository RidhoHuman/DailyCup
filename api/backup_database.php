<?php
/**
 * Database Backup Script
 * Creates automated database backups
 * Should be run as a cron job daily
 */

require_once '../includes/functions.php';

// Only allow CLI or specific access
if (!defined('ALLOW_CRON') && php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Access denied');
}

define('ALLOW_CRON', true);

try {
    // Determine backup type from command line argument or default to full
    $backupType = $argv[1] ?? 'full';

    if (!in_array($backupType, ['full', 'incremental'])) {
        echo "Invalid backup type. Use 'full' or 'incremental'\n";
        exit(1);
    }

    // Create backup
    $result = createDatabaseBackup($backupType);

    if ($result['success']) {
        echo "Backup completed successfully\n";
        echo "File: " . $result['filename'] . "\n";
        echo "Size: " . $result['size'] . " bytes\n";
        echo "Duration: " . $result['duration'] . " seconds\n";
    } else {
        echo "Backup failed: " . $result['error'] . "\n";
        exit(1);
    }

} catch (Exception $e) {
    error_log("Database backup error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>