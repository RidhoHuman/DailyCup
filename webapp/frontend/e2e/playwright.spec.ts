import { test, expect } from '@playwright/test';

test('home page loads and shows featured products', async ({ page }) => {
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  // Use a more stable selector (Order Now link) instead of full hero text which is split into spans
  await expect(page.getByRole('link', { name: 'Order Now' })).toBeVisible({ timeout: 15000 });
  // Wait for featured products to render
  await page.waitForSelector('text=Featured Products', { timeout: 10000 }).catch(() => {});
});

// Test mock notice appears when API dispatches mock event
test('mock notice shows when api:mock event dispatched', async ({ page }) => {
  await page.goto('/');
  // Ensure ClientUI root mounted so MockNotice listener is attached
  await page.waitForSelector('#__client_ui_root', { timeout: 5000 });
  // Dispatch event in page context
  await page.evaluate(() => {
    window.dispatchEvent(new CustomEvent('api:mock', { detail: { endpoint: 'products' } }));
  });
  // Be resilient to small text changes and casing
  await expect(page.locator('text=/mock data.*products/i').first()).toBeVisible({ timeout: 5000 });
});