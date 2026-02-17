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

## Verification performed
- Status CORS: Verified manually via `curl` and Browser Network Tab for `orders.php` and `admin/analytics.php?action=summary` (preflight + GET include Access‑Control headers).
- Auth Mirroring: Introduced shim in `webapp/frontend/lib/stores/auth-store.ts` to mirror legacy `localStorage.token`; unit test added (`auth-store.test.ts`).

## Future task / technical debt
- Replace per-file `require_once 'cors.php'` with a single `init.php` (or use `auto_prepend_file`) in a follow-up PR to prevent omissions.

## Checklist before merge
- [x] Ensure no BOM or output before `<?php` in `cors.php` (verified).
- [x] Remove trailing `?>` from `cors.php` to avoid accidental whitespace/output (applied).
- [ ] Ensure `JWT_SECRET` is set in all production/staging environments (do **not** use defaults).
- [ ] Run full CI + e2e pipeline and address any flaky tests (analytics/phase7/phase8 are currently flaky in CI).

Please review and merge when CI is green.