# Deployment Guide — DailyCup Backend (shared hosting / cPanel)

This document describes a minimal, secure deployment flow for the DailyCup backend on a shared hosting/cPanel account using the API subdomain `api.dailycup.com`.

## Quick checklist ✅
- Create subdomain `api.dailycup.com` in cPanel
- Upload backend files and ensure `/api/*` routes are reachable
- Place `.env` outside the webroot (e.g. `/home/<cpanel_user>/.env`)
- Import database SQL and create DB user
- Enable AutoSSL / Let's Encrypt
- Set Vercel env `NEXT_PUBLIC_API_URL=https://api.dailycup.com/api` and redeploy frontend

---

## 1) Create subdomain
1. Log in to cPanel → Domains → Subdomains.
2. Create `api` for `dailycup.com` (subdomain = `api.dailycup.com`).
3. Set the document root. Recommended: `public_html` (if you plan to upload `backend/api/*` so endpoints are available under `/api/`).

Notes:
- If you prefer the API at root of the subdomain (e.g. `https://api.dailycup.com/products.php`), configure the document root to the folder that contains the API files.

## 2) Upload files
1. Upload the repository `backend/` files into the document root so the `api/` folder is directly accessible (e.g. `public_html/api/products.php`).
2. Ensure `db_test.php` is accessible at `https://api.dailycup.com/api/db_test.php` for quick verification.

## 3) Environment file (.env)
1. Copy `backend/.env.example` to a file named `.env` and fill values (DB credentials, JWT_SECRET, VAPID keys, XENDIT keys, SMTP, etc.).
2. **Place `.env` outside the public_html/webroot** (recommended location: `/home/<cpanel_user>/.env` or `/home/<cpanel_user>/backend.env`).
3. Ensure the file is not world-readable: `chmod 600 .env`.
4. The backend will try to read environment from common locations (`api/.env`, `../.env`, `../../.env`, and `/home/<user>/.env`). Use `/home/<user>/.env` for best isolation.

## 4) Database
1. Create a MySQL database and a user in cPanel → MySQL Databases.
2. Grant the user to the database.
3. Import SQL via phpMyAdmin (use `backend/sql/*.sql`). If you have SSH access you can use `mysql -u user -p dbname < backend/sql/yourfile.sql`.

## 5) SSL
1. In cPanel → SSL/TLS → Manage AutoSSL or Let's Encrypt, enable SSL for both `dailycup.com` and `api.dailycup.com`.
2. Wait for certificate issuance and verify `https://api.dailycup.com/api/products.php` returns JSON over HTTPS.

## 6) Payment callbacks and other webhooks
- Update payment gateway callback URLs to `https://api.dailycup.com/notify_xendit.php` (or configure in `.env`).

## 7) VAPID keys (push notifications)
1. Locally: `node frontend/scripts/generate-vapid-keys.js` → copy keys.
2. In backend `.env`: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`.
3. In frontend `.env.local`: `NEXT_PUBLIC_VAPID_PUBLIC_KEY` (public key only).

## 8) Vercel / Frontend
1. Set Vercel environment variables (Production):
   - `NEXT_PUBLIC_API_URL = https://api.dailycup.com/api`
   - `NEXT_PUBLIC_APP_NAME = DailyCup`
   - `NEXT_PUBLIC_VAPID_PUBLIC_KEY = <public-key>`
2. Redeploy frontend.

## 9) Post-deploy checks
- Test: `curl -i -H "Origin: https://dailycup.com" https://api.dailycup.com/api/products.php` → look for `Access-Control-Allow-Origin: https://dailycup.com`.
- Run `https://api.dailycup.com/api/db_test.php` to confirm DB connectivity.
- Once stable, harden CORS by removing permissive fallbacks and only allow `https://dailycup.com` (and `https://dailycup.vercel.app` for preview deployments if needed).

## 10) Cleanup
- Remove or restrict `db_test.php` once done.
- Do not commit actual `.env` to Git.

---

If you want, saya bisa: upload prepared `.env` template content somewhere safe for you, or generate a checklist for you to run in cPanel. Let me know apakah mau saya juga push these repo changes now so you can pull and deploy.