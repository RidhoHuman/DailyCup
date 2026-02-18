import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './',
  timeout: 60 * 1000, // 60 detik untuk operasi yang lambat
  expect: {
    timeout: 10000, // 10 detik untuk assertion
  },
  // Menggunakan dev server yang sedang berjalan
  webServer: {
    command: 'npm run dev',
    url: 'http://127.0.0.1:3000',
    timeout: 120000,
    reuseExistingServer: true,
  },
  use: {
    baseURL: 'http://127.0.0.1:3000',
    headless: true, // Ubah ke false jika ingin melihat browser saat test berjalan
    viewport: { width: 1280, height: 720 },
    
    // Capture log/video jika gagal
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'retain-on-failure',
    
    navigationTimeout: 30000,
    actionTimeout: 15000,

    // BAGIAN KRUSIAL: Launch Options untuk Bypass CORS & Sandbox issues
    launchOptions: {
      args: [
        '--no-sandbox',
        '--disable-web-security', // Bypass CORS policy saat testing
        '--disable-features=IsolateOrigins,site-per-process', // Bypass isolasi origin
        '--disable-dev-shm-usage',
        '--disable-hardware-acceleration',
        '--host-resolver-rules=MAP dailycup.test 127.0.0.1'
      ]
    }
  },
  projects: [
    {
      name: 'chromium',
      use: {
        // Memastikan browser menggunakan fitur yang diizinkan
        permissions: ['clipboard-read', 'clipboard-write', 'notifications'],
      },
    }
  ]
});