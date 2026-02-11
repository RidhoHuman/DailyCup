import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './',
  timeout: 60 * 1000, // Increased to 60 seconds for slower operations
  expect: {
    timeout: 10000, // 10 seconds for expect assertions
  },
  // Use existing dev server on port 3000
  webServer: {
    command: 'npm run dev',
    url: 'http://127.0.0.1:3000',
    timeout: 120000,
    reuseExistingServer: true,
  },
  use: {
    baseURL: 'http://127.0.0.1:3000',
    headless: true,
    viewport: { width: 1280, height: 720 },
    // Capture artifacts on failure for debugging
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    // Navigation timeout
    navigationTimeout: 30000,
    actionTimeout: 15000,
    // Conditional launch options for Windows to avoid sandbox/spawn issues
    launchOptions: {
      args: [
        '--no-sandbox',
        '--disable-dev-shm-usage',
        '--disable-hardware-acceleration',
        '--host-resolver-rules=MAP dailycup.test 127.0.0.1'
      ]
    }
  },
  projects: [
    { name: 'chromium' }
  ]
});