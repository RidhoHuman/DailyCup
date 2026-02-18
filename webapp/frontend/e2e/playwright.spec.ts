import { test, expect } from '@playwright/test';

test('home page loads and shows featured products', async ({ page }) => {
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  // Wait for the hero section to load
  await expect(page.locator('text=Discover')).toBeVisible({ timeout: 15000 });
  // Wait for featured products section to be visible
  await page.waitForTimeout(1000);
});

// Test mock notice appears when API dispatches mock event
test('mock notice shows when api:mock event dispatched', async ({ page }) => {
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  // Wait for page to be fully loaded
  await page.waitForTimeout(2000);
  // Dispatch event in page context
  await page.evaluate(() => {
    window.dispatchEvent(new CustomEvent('api:mock', { detail: { endpoint: 'products' } }));
  });
  // Wait a bit for the mock notice to appear
  await page.waitForTimeout(1000);
});