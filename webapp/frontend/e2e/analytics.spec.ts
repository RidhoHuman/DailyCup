import { test, expect } from '@playwright/test';

test('Analytics page shows integration KPIs', async ({ page }) => {
  // Capture console and page errors to surface runtime exceptions (helps debug overlay)
  const consoleErrors: string[] = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
      // also print to test runner log
      console.error('[PAGE CONSOLE ERROR]', msg.text());
    }
  });
  page.on('pageerror', err => {
    consoleErrors.push(err.message || String(err));
    console.error('[PAGE ERROR]', err);
  });

  // Use a stable absolute base URL so tests do not depend on Playwright config being loaded
  const BASE = process.env.PLAYWRIGHT_BASE_URL || process.env.PW_BASE_URL || 'http://127.0.0.1:3000';
  // Intercept both rewritten and direct backend paths
  // Summary endpoint (only handle action=summary) — leave provider requests to the dedicated mock
  await page.route('**/admin/analytics.php**', async route => {
    const url = route.request().url();
    const auth = (route.request().headers()['authorization'] || '').toLowerCase();
    // log the Authorization header seen by the route handler (debugging assertion failure)
    console.log('[e2e] route admin/analytics Authorization header:', auth);
    if (url.includes('action=summary')) {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, summary: [{ provider: 'twilio', sent_last_24h: 5, failed_last_24h: 1, retry_scheduled_total: 2, avg_retry_count: 0.5 }], trend: [] }) });
      return;
    }
    await route.continue();
  });
  await page.route('**/api/admin/analytics.php**', async route => {
    const url = route.request().url();
    const auth = (route.request().headers()['authorization'] || '').toLowerCase();
    expect(auth).toContain('ci-admin-token');
    if (url.includes('action=summary')) {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, summary: [{ provider: 'twilio', sent_last_24h: 5, failed_last_24h: 1, retry_scheduled_total: 2, avg_retry_count: 0.5 }], trend: [] }) });
      return;
    }
    await route.continue();
  });

  // Mock provider breakdown
  await page.route('**/admin/analytics.php?action=provider&**', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, providers: ['twilio'], provider: 'twilio', from: '2026-01-01', to: '2026-01-14', series: [{ provider: 'twilio', day: '2026-01-01', channel: 'whatsapp', total_messages: 2, delivered_count: 1, failed_count: 0 }], totals: [{ provider: 'twilio', total_sent: 2, total_delivered: 1, total_failed: 0 }] }) });
  });

  // Mock main analytics payload (page fetches /api/analytics.php via Next.js rewrites)
  await page.route('**/api/analytics.php**', async route => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        success: true,
        period: '30days',
        date_range: { start: '2026-01-01', end: '2026-01-30' },
        trends: { daily_revenue: [{ date: '2026-01-01', orders: 1, revenue: 10000 }], peak_hours: [{ hour: 9, orders: 1, revenue: 10000 }] },
        products: { top_selling: [], category_performance: [] },
        customers: { total: 10, new: 2, top_customers: [] },
        payment_methods: [],
        reviews: { avg_rating: 4.5, total: 5 },
        revenue: { total: 100000, avg_order_value: 20000, highest_order: 50000, growth_percentage: 5 }
      })
    });
  });



  // Ensure page has a hydrated admin auth state (Zustand persist format)
  await page.addInitScript(() => {
    try {
      const adminUser = {
        id: '1',
        name: 'Admin',
        email: 'admin@example.com',
        role: 'admin',
        loyaltyPoints: 0,
        joinDate: new Date().toISOString(),
      };
      // Zustand persist stores the snapshot under a `state` key — mirror that shape
      localStorage.setItem('dailycup-auth', JSON.stringify({ state: { user: adminUser, token: 'ci-admin-token', isAuthenticated: true } }));
    } catch (e) { /* ignore */ }
  });

  // navigate using absolute URL (use BASE to avoid invalid relative navigation in some envs)
  await page.goto(`${BASE}/admin/analytics`, { waitUntil: 'load' });
  // wait for analytics API to return (handle rewrites and rewrites-to-/api)
  await page.waitForResponse(resp => /analytics\.php/.test(resp.url()) && resp.status() === 200, { timeout: 15000 });

  // wait specifically for the admin summary API used by the integration cards
  await page.waitForResponse(resp => /admin\/analytics\.php/.test(resp.url()) && resp.status() === 200, { timeout: 10000 }).catch(()=>{});

  // wait for analytics API to return (handle rewrites and rewrites-to-/api)
  await page.waitForResponse(resp => /analytics\.php/.test(resp.url()) && resp.status() === 200, { timeout: 15000 });

  // wait specifically for the admin summary API used by the integration cards
  await page.waitForResponse(resp => /admin\/analytics\.php/.test(resp.url()) && resp.status() === 200, { timeout: 10000 }).catch(()=>{});

  // wait for integration card to appear (case-insensitive)
  await expect(page.locator('text=/twilio/i')).toBeVisible({ timeout: 20000 });
  await expect(page.locator('text=Sent (24h):')).toHaveText(/Sent \(24h\):/, { timeout: 5000 });
});