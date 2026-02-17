import { test, expect } from '@playwright/test';

test('Analytics page shows integration KPIs', async ({ page }) => {
  // 1. Suntikkan Mock ke dalam Browser sebelum apapun dimuat
  await page.addInitScript(() => {
    const mockResponse = {
      success: true,
      summary: [{ provider: 'twilio', sent_last_24h: 999, failed_last_24h: 0 }],
      revenue: { total: 500000, avg_order_value: 50000 },
      orders: { total: 10, paid: 10 },
      trends: { daily_revenue: [] },
      products: { top_selling: [] },
      customers: { total: 5 },
      payment_methods: [],
      order_status_distribution: []
    };

    // --- MOCK FETCH ---
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      const url = args[0].toString();
      if (url.includes('analytics.php')) {
        return new Response(JSON.stringify(mockResponse), {
          status: 200,
          headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
      }
      return originalFetch(...args);
    };

    // --- MOCK XHR (Untuk Axios) ---
    const XHR = window.XMLHttpRequest;
    // @ts-ignore
    window.XMLHttpRequest = function() {
      const xhr = new XHR();
      const originalOpen = xhr.open;
      xhr.open = function(method, url) {
        if (typeof url === 'string' && url.includes('analytics.php')) {
          Object.defineProperty(xhr, 'status', { writable: true, value: 200 });
          Object.defineProperty(xhr, 'responseText', { writable: true, value: JSON.stringify(mockResponse) });
          // Mencegah request asli terkirim
          xhr.send = function() {
            setTimeout(() => {
              xhr.dispatchEvent(new Event('load'));
              xhr.dispatchEvent(new Event('readystatechange'));
            }, 10);
          };
        }
        return originalOpen.apply(xhr, arguments as any);
      };
      return xhr;
    };

    // Clear Auth & Cache
    window.localStorage.clear();
    const auth = { state: { user: { id: '1', role: 'admin' }, token: 'ci-admin-token', isAuthenticated: true } };
    window.localStorage.setItem('dailycup-auth', JSON.stringify(auth));
    window.localStorage.setItem('token', 'ci-admin-token');
  });

  // 2. Gunakan page.route sebagai jaring pengaman tambahan (Level Network)
  await page.route('**/analytics.php*', (route) => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ success: true, revenue: { total: 500000 }, summary: [{ provider: 'twilio', sent_last_24h: 999 }] })
  }));

  // 3. Navigasi dan Tunggu
  await page.goto('http://127.0.0.1:3000/admin/analytics', { waitUntil: 'networkidle' });

  // 4. Verifikasi dengan Reload jika perlu
  const revenueLocator = page.locator('text=500');
  if (!(await revenueLocator.isVisible())) {
    await page.reload({ waitUntil: 'networkidle' });
  }

  // Cari angka 999 (Twilio) dan 500 (Revenue)
  await expect(page.locator('text=500').first()).toBeVisible({ timeout: 15000 });
  await expect(page.locator('text=999').first()).toBeVisible({ timeout: 15000 });
});