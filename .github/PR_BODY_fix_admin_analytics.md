## Summary
- Fixes missing `Authorization` on admin analytics requests (prevents 403 in browser).
- Uses central `api` client and adds an explicit Authorization fallback for reliability.
- Adds Playwright e2e assertion/logging to ensure the browser sends the admin token.

## Changes
- `webapp/frontend/app/admin/(panel)/analytics/page.tsx` — use `api.get()` + explicit auth fallback.
- `webapp/frontend/e2e/analytics.spec.ts` — assert/log Authorization header.
- `webapp/backend/api/jwt.php` — debug logging (gated by `APP_DEBUG`/`JWT_DEBUG`).

## How to test / smoke tests
1. Start backend + frontend locally.
2. In browser localStorage set `dailycup-auth` with an admin token (ci-admin-token) or log in via Admin UI.
3. Open `/admin/analytics` and confirm Network request to `admin/analytics.php?action=summary` includes `Authorization: Bearer <token>` and returns 200.
4. Run e2e: `cd webapp/frontend && npm run test:e2e`

## Notes
- Low-risk change; only affects admin analytics client call and tests.
- If CI shows failures, they will likely be related to environment variable wiring for `NEXT_PUBLIC_API_URL`.

Please review and merge when green.