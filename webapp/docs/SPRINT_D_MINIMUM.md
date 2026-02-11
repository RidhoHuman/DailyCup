SPRINT D — Minimum Shippable Plan

Goal: Deliver a shippable MVP within 3–7 days focused on Integrations (WhatsApp/SMS), Advanced analytics (materialized + incremental), and Audit polish — make sure functionality is secure, monitored, and testable.

Priority tasks (must-have):

1. Secure provider credentials (env-vars fallback + test) — 0.5–1.5d
   - Use env vars for Twilio/SMS creds (TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_WHATSAPP_FROM)
   - Add credential test endpoint CLI and admin endpoint
   - Minimal UI: ability to enter and test temporary creds (dev/staging)

2. Monitoring & Alerts — 0.5–1d
   - Health-check script (exists) scheduled hourly
   - Wrapper writes Prometheus textfile and logs
   - Test alerts via CLI and Admin UI (Slack/SMTP env vars)

3. Smoke tests & CI gating — 0.5–1.5d
   - CLI smoke tests: send (mock)/webhook simulation, incremental refresh test (script `backend/scripts/smoke_test_mvp.php`)
   - Add CI job to run lint + smoke tests (CI job `webapp/.github/workflows/ci.yml` now runs PHP lint, composer install, applies migrations, and executes `php webapp/backend/scripts/smoke_test_mvp.php`, `php webapp/backend/scripts/test_refresh_incremental.php`, and an HTTP broadcast smoke test via `php webapp/backend/scripts/test_broadcast.php`). The job starts a PHP built-in server and Next.js for integration testing (uses a MySQL service). For security, CI generates a short-lived admin token using `php webapp/backend/scripts/generate_ci_admin_token.php` and passes it as `BACKEND_AUTH_TOKEN` to the broadcast test; no auth bypass is used in production. The job will fail the build on non-zero exit codes from lint/smoke tests.

4. Basic Audit UI & export — 0.5–1d
   - Admin viewer with filters by action and CSV export

5. Frontend polish (legend/colors/deltas already added) — 0.25–0.5d

Acceptance criteria checklist (quick):
- [ ] Send+Webhook end-to-end works (mock provider OK)
- [ ] Incremental refresh runs and is scheduled (wrapper+cron/systemd)
- [ ] Health-check script alerts when refresh stale
- [ ] Provider credentials can be validated (CLI/admin)
- [ ] AuditLog entries exist for key events and export works
- [ ] CI runs lint and smoke tests before deploy

Notes:
- Advanced items (full secrets manager, Grafana panels, anomaly detection) are deferred to follow-up sprints.
- I will start with (1) Secure provider credentials and (2) Monitoring & Alerts and report progress daily.
