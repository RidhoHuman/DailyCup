<?php
require_once __DIR__ . '/../api/audit_log.php';

AuditLog::logSecurityAlert('ANALYTICS_REFRESH_TEST', ['note' => 'Manual test: analytics alert via CLI']);
echo "Test alert sent (check AuditLog and configured channels)\n";
