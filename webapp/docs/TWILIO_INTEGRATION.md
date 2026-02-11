Twilio WhatsApp Integration

Overview:
- This integration allows sending and receiving WhatsApp messages via Twilio.
- Admins can configure credentials from Admin → Integrations → Twilio and send test messages.

Backend endpoints:
- POST /backend/api/integrations/twilio.php?action=settings (admin) — save settings
- GET  /backend/api/integrations/twilio.php?action=settings (admin) — read settings
- POST /backend/api/integrations/send.php?action=send (admin) — send message via selected provider (body: {provider, to, body, from?})
- POST /backend/api/integrations/twilio.php?action=send (admin) — legacy Twilio send (kept for compatibility)
- GET  /backend/api/integrations/twilio.php?action=logs (admin) — fetch recent message logs
- POST /backend/api/integrations/twilio.php?action=webhook — Twilio webhook (public)
- GET  /backend/api/integrations/twilio.php?action=logs&action2=worker_status (admin) — get worker health and last summary
- POST /backend/api/integrations/twilio.php?action=run_worker (admin) — run the twilio status worker immediately (returns script output)
- POST /backend/api/integrations/twilio.php?action=alerts&test=1 (admin) — trigger a security alert test (Slack/email)
- GET/POST /backend/api/integrations/twilio.php?action=provider_settings&provider=NAME (admin) — manage provider-specific settings (saved as `provider_{name}_{key}`)

DB tables created by migration:
- integration_settings (key,value)
- integration_messages (logs of inbound/outbound messages)

Webhook Configuration (Twilio):
- Set your webhook URL to https://<your-host>/backend/api/integrations/twilio.php?action=webhook
- Optionally set a webhook secret by saving `twilio_webhook_secret` in Integrations → Twilio. The webhook will then be validated with HMAC-SHA256 of raw body.

Notes:
- Messages are logged into `integration_messages` and an audit event is written via `AuditLog` for important actions.
- Sending is done using Twilio REST API with account SID and auth token.

Security:
- Admin-only endpoints are protected via existing `validateToken()` and role checks.
- Webhook verification uses Twilio's official signature algorithm (HMAC-SHA1 of the canonical URL + sorted POST params) when an auth token is configured. Ensure HTTPS is enabled for production.

Credentials & env var fallback:
- For fast, secure deployment in staging/production we support environment variables as the primary credential source:
  - `TWILIO_ACCOUNT_SID`
  - `TWILIO_AUTH_TOKEN`
  - `TWILIO_WHATSAPP_FROM` (e.g., `whatsapp:+123456789`)
- If env vars are set, the backend will prefer them over values stored in `integration_settings`.
- Use `php backend/scripts/test_provider_credentials.php twilio` to run a quick CLI test. For admin-driven tests, use the Admin endpoint:
  - POST `/backend/api/admin/credentials.php?action=test` with body `{provider:'twilio', account_sid:'...', auth_token:'...'}` to validate credentials (Admin-only).

Testing credentials and rotation:
- The admin test endpoint will log a `PROVIDER_CREDENTIAL_TEST` AuditLog entry. Use this after rotating or updating credentials.
- For MVP we recommend setting credentials via environment variables in staging (faster & avoids storing secrets in DB). For long-term, plan to migrate to a proper secrets manager.

Notes:
- Messages are logged into `integration_messages` and an audit event is written via `AuditLog` for key events (sent, inbound, status updates, retries).
- A background worker `backend/cron/twilio_status.php` polls Twilio for message status updates and schedules/resends retries using exponential backoff. Schedule it every 5–15 minutes (see `docs/TWILIO_CRON_SETUP.md`).
- Inbound media attachments are downloaded and stored under `backend/uploads/integrations/twilio/{messageId}/` and appear in the admin Message Logs detail view.

Testing (manual quick check):
1. Admin -> Integrations -> Twilio: fill `twilio_account_sid`, `twilio_auth_token`, `twilio_whatsapp_from` (format: whatsapp:+12345)
2. Click Save
3. Use Send Test Message to a WhatsApp-enabled number in E.164 format prefixed by `whatsapp:` (e.g., `whatsapp:+628123456789`)
4. Check message logs in Admin -> Integrations -> Twilio (supports filters & pagination)
5. If using retries, monitor `AuditLog` for `TWILIO_MESSAGE_RETRY_SCHEDULED` and `TWILIO_MESSAGE_RESENT` events.

