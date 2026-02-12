import { test, expect } from '@playwright/test';

test('Analytics page shows integration KPIs', async ({ page }) => {
  // Intercept both rewritten and direct backend paths
  await page.route('**/admin/analytics.php?action=summary', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, summary: [{ provider: 'twilio', sent_last_24h: 5, failed_last_24h: 1, retry_scheduled_total: 2, avg_retry_count: 0.5 }], trend: [] }) });
  });
  await page.route('**/api/admin/analytics.php?action=summary', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, summary: [{ provider: 'twilio', sent_last_24h: 5, failed_last_24h: 1, retry_scheduled_total: 2, avg_retry_count: 0.5 }], trend: [] }) });
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
      localStorage.setItem('dailycup-auth', JSON.stringify({ user: adminUser, token: 'ci-admin-token', isAuthenticated: true }));
    } catch (e) { /* ignore */ }
  });

  await page.goto('/admin/analytics');
  // wait for analytics API to return (handle rewrites and rewrites-to-/api)
  await page.waitForResponse(resp => /analytics\.php/.test(resp.url()) && resp.status() === 200, { timeout: 15000 });

  // wait for integration card to appear (case-insensitive)
  await expect(page.locator('text=/twilio/i')).toBeVisible({ timeout: 10000 });
  await expect(page.locator('text=Sent (24h):')).toHaveText(/Sent \(24h\):/, { timeout: 5000 });
});