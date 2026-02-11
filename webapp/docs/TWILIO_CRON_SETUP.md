Twilio Status & Retry Worker — Scheduling Guide

This worker polls Twilio for message status updates and schedules/resends retries.

Files:
- backend/cron/twilio_status.php — worker script (uses DB GET_LOCK to avoid concurrent runs)

Linux (crontab)
---------------
Run every 5 minutes:

*/5 * * * * cd /path/to/DailyCup/webapp && php backend/cron/twilio_status.php >> /var/log/dailycup/twilio_status.log 2>&1

Windows (Task Scheduler)
------------------------
1. Open Task Scheduler -> Create Task
2. General: Run whether user is logged on or not; use account with permission to run php and access DB
3. Triggers: New -> Begin the task: On a schedule -> Daily -> Repeat task every: 5 minutes -> for a duration of: Indefinitely
4. Actions: New -> Action: Start a program
   Program/script: C:\path\to\php.exe
   Add arguments: "C:\laragon\www\DailyCup\webapp\backend\cron\twilio_status.php"
   Start in: "C:\laragon\www\DailyCup\webapp"
5. Conditions/Settings: Uncheck "Stop the task if it runs longer than" (or set a reasonable timeout)
6. Save. Monitor the log file specified in the task or use AuditLog entries.

Notes
-----
- The script uses MySQL GET_LOCK to ensure only one instance runs at a time.
- Recommended run interval: 5-15 minutes depending on message volume and Twilio rate limits.
- The worker writes a health summary to `integration_settings` keys `twilio_status_last_run` and `twilio_status_last_summary`.
- If thresholds are exceeded (env vars `TWILIO_ALERT_FAILED_THRESHOLD`, `TWILIO_ALERT_RETRY_THRESHOLD`), a security alert is recorded (and email/Slack can be wired via `SECURITY_ALERT_EMAIL`).
- Use `backend/scripts/rotate_audit_logs.php` (or schedule via cron) to clean up AuditLog files older than N days (default 90). Monitor `AuditLog` for `TWILIO_MESSAGE_STATUS_UPDATED`, `TWILIO_MESSAGE_RETRY_SCHEDULED`, `TWILIO_MESSAGE_RESENT`, and `TWILIO_STATUS_ERROR` events.
