# End-to-end tests (Playwright)

This project includes simple Playwright tests to smoke test critical flows.

Setup:
1. Install playwright: `npm i -D @playwright/test`
2. Install browser binaries: `npx playwright install`

Run tests:
- `npm run test:e2e`

Notes:
- Ensure the dev server is running on `http://localhost:3000` before running tests.
- Tests are small smoke checks and can be extended as needed.
