Analytics materialized table

A materialized table `analytics_integration_messages_daily_mat` is used to speed up analytics queries.

Refresh script:
- Full refresh: `php backend/scripts/refresh_analytics_materialized.php` will TRUNCATE and repopulate the materialized table from `integration_messages`.
- Incremental refresh: pass a number of days to refresh only recent days (faster):
  `php backend/scripts/refresh_analytics_materialized.php 7` (refresh last 7 days)

Scheduling examples

- Crontab (nightly full refresh at 02:00):
  0 2 * * * cd /path/to/DailyCup/webapp && php backend/scripts/refresh_analytics_materialized.php >> /var/log/dailycup/analytics_refresh.log 2>&1

- Crontab (nightly incremental refresh for last 7 days at 03:00):
  0 3 * * * cd /path/to/DailyCup/webapp && php backend/scripts/refresh_analytics_materialized.php 7 >> /var/log/dailycup/analytics_refresh.log 2>&1

- systemd (recommended on servers using systemd)

  We include sample `systemd` unit and timer files in the repository at `webapp/deploy/systemd/` which use the wrapper script (adds logging, metrics & alerts).

  Copy the unit and timer to `/etc/systemd/system/` and adapt `WorkingDirectory`/`ExecStart` paths as necessary. Example contents (provided in `webapp/deploy/systemd/`):

  Create `/etc/systemd/system/dailycup-analytics-refresh.service`:

  [Unit]
  Description=DailyCup Analytics Materialized Refresh (one-shot)
  After=network.target

  [Service]
  Type=oneshot
  WorkingDirectory=/path/to/DailyCup/webapp
  ExecStart=/usr/bin/php backend/scripts/analytics_refresh_wrapper.php 7
  User=www-data
  Group=www-data
  StandardOutput=append:/var/log/dailycup/analytics_refresh.log
  StandardError=append:/var/log/dailycup/analytics_refresh.log

  Create `/etc/systemd/system/dailycup-analytics-refresh.timer`:

  [Unit]
  Description=Run DailyCup analytics refresh daily

  [Timer]
  OnCalendar=*-*-* 03:00:00
  Persistent=true

  [Install]
  WantedBy=timers.target

  Healthcheck (hourly):
  We also include a sample healthcheck unit/timer to verify recent successful runs; copy `webapp/deploy/systemd/dailycup-analytics-healthcheck.service` and `.timer` to `/etc/systemd/system/` if using systemd.

  Then enable and start timers:
  sudo systemctl daemon-reload
  sudo systemctl enable --now dailycup-analytics-refresh.timer dailycup-analytics-healthcheck.timer

  Environment variables for alerting
  - `SECURITY_ALERT_EMAIL` (required for email alerts)
  - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE` (PHPMailer SMTP config)
  - `MAIL_FROM`, `MAIL_FROM_NAME` (optional from address)
  - `SECURITY_ALERT_SLACK_WEBHOOK` (Slack webhook url)

  Metrics (Prometheus):
  - The wrapper writes a small Prometheus textfile to `webapp/metrics/analytics_refresh.prom` which contains:
    - `analytics_refresh_last_success_timestamp` (gauge)
    - `analytics_refresh_failed_total` (counter)
  - Use node_exporter's textfile collector or copy the file into your collector directory.

  Testing alerts
  - CLI test: `php backend/scripts/test_alert_analytics.php`
  - Admin UI: Admin → Analytics → click **Test Alerts** to send a test alert (Slack/email) and verify AuditLog entries.

  Notes:
  - Healthcheck (`backend/scripts/check_analytics_refresh_health.php`) will send a security alert if `ANALYTICS_MATERIALIZED_REFRESH` last run is older than threshold. Schedule hourly.
  - Ensure the process user has permission to write logs/metrics files and that env vars are set for SMTP/Slack to receive notifications.

- Windows Task Scheduler (sample)

  Use Task Scheduler to run `php.exe` with the argument `C:\path\to\DailyCup\webapp\backend\scripts\refresh_analytics_materialized.php 7` on a daily trigger. Configure 'Run whether user is logged on or not' and redirect output to a log file.

API and admin controls

- The admin endpoint can trigger refresh and supports an optional `days` parameter:
  POST `admin/analytics.php?action=refresh_materialized` with body `{ "days": 7 }` will run an incremental refresh for last 7 days.

- You can run a quick sanity check locally using `php backend/scripts/test_refresh_incremental.php` which runs the script for last 3 days and checks for completion.

Operational notes:
- Incremental refresh is faster and recommended for daily schedules. Full refresh is useful after backfills or schema changes.
- Monitor `analytics_refresh.log` (system path `/var/log/dailycup/analytics_refresh.log` or project-local `webapp/logs/analytics_refresh.log`) for errors and ensure the process has permission to write to logs.
- A wrapper script `backend/scripts/analytics_refresh_wrapper.php` is provided; it writes logs and will emit an AuditLog `ANALYTICS_MATERIALIZED_REFRESH_FAILED` and a security alert (Slack/email) when a run fails.
- Health check: use `backend/scripts/check_analytics_refresh_health.php [hours]` (default 24) to verify the last successful refresh is not stale; schedule this hourly in cron or as a systemd timer to notify on issues.
- Example cron entries:
  - Nightly incremental (7 days) at 03:00:
    0 3 * * * cd /path/to/DailyCup/webapp && php backend/scripts/analytics_refresh_wrapper.php 7 >> /var/log/dailycup/analytics_refresh.log 2>&1
  - Hourly health check (alerts if last run older than 24h):
    0 * * * * cd /path/to/DailyCup/webapp && php backend/scripts/check_analytics_refresh_health.php 24 >> /var/log/dailycup/analytics_health_check.log 2>&1

- If you have very large volumes, consider partitioning `integration_messages` by date or maintaining a per-day incremental job that computes day-level aggregates as messages arrive.
