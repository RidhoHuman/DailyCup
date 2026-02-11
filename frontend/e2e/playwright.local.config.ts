import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './',
  timeout: 30 * 1000,
  // Do NOT start a webServer; use the already-running dev server on 127.0.0.1:3001
  use: {
    baseURL: 'http://127.0.0.1:3001',
    headless: true,
    viewport: { width: 1280, height: 720 },
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    launchOptions: {
      args: [
        '--no-sandbox',
        '--disable-dev-shm-usage',
        '--host-resolver-rules=MAP dailycup.test 127.0.0.1'
      ]
    }
  },
  projects: [{ name: 'chromium' }]
});
