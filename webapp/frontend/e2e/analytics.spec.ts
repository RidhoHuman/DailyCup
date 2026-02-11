import { test, expect } from '@playwright/test';

test('Analytics page shows integration KPIs', async ({ page }) => {
  await page.route('**/admin/analytics.php?action=summary', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, summary: [{ provider: 'twilio', sent_last_24h: 5, failed_last_24h: 1, retry_scheduled_total: 2, avg_retry_count: 0.5 }], trend: [] }) });
  });

  // Mock provider breakdown
  await page.route('**/admin/analytics.php?action=provider&**', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, providers: ['twilio'], provider: 'twilio', from: '2026-01-01', to: '2026-01-14', series: [{ provider: 'twilio', day: '2026-01-01', channel: 'whatsapp', total_messages: 2, delivered_count: 1, failed_count: 0 }], totals: [{ provider: 'twilio', total_sent: 2, total_delivered: 1, total_failed: 0 }] }) });
  });

  await page.goto('/admin/analytics');
  await expect(page.locator('text=Twilio')).toBeVisible();
  await expect(page.locator('text=Sent (24h):')).toHaveText(/Sent \(24h\):/);
});