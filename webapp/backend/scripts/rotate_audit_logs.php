<?php
require_once __DIR__ . '/../api/audit_log.php';

$days = intval($argv[1] ?? getenv('AUDITLOG_DAYS_KEEP') ?: 90);
$deleted = AuditLog::cleanup($days);
AuditLog::log('AUDITLOG_ROTATION', ['days_kept' => $days, 'files_deleted' => $deleted]);
echo "Rotated audit logs, deleted: $deleted\n";
