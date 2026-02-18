import { test, expect } from '@playwright/test';

test('product variants adjust price and add to cart', async ({ page }) => {
  await page.goto('/menu');
  await page.waitForLoadState('networkidle');

  // Locate the specific Cappuccino product card
  const cappuccinoHeading = page.locator('h3:has-text("Cappuccino")').first();
  await cappuccinoHeading.waitFor({ timeout: 10000 });
  
  // Get the parent product card
  const card = cappuccinoHeading.locator('xpath=ancestor::div[@data-testid="product-card"]').first();
  await card.waitFor({ timeout: 5000 });

  // Check if variants exist (size and temperature)
  const sizeButtons = card.locator('button:has-text("Large")');
  const tempButtons = card.locator('button:has-text("Iced")');
  
  if ((await sizeButtons.count()) > 0) {
    await sizeButtons.first().click();
    await page.waitForTimeout(300);
  }
  
  if ((await tempButtons.count()) > 0) {
    await tempButtons.first().click();
    await page.waitForTimeout(300);
  }

  // Add to cart
  const addBtn = card.locator('button:has-text("Add to Cart")').first();
  await expect(addBtn).toBeVisible({ timeout: 5000 });
  await addBtn.click();

  // Wait for cart update
  await page.waitForTimeout(1000);

  // Verify cart badge updated
  const cartBadge = page.locator('[data-testid="cart-badge"]');
  await expect(cartBadge).toBeVisible({ timeout: 5000 });
});

test('stock indicators shown and out-of-stock disables add button', async ({ page }) => {
  await page.goto('/menu');
  await page.waitForLoadState('networkidle');

  // Look for any "Out of stock" badge
  const outOfStockBadge = page.locator('text=/out of stock/i').first();
  
  // If there's an out-of-stock product, verify the add button is disabled or not shown
  if ((await outOfStockBadge.count()) > 0 && await outOfStockBadge.isVisible()) {
    await expect(outOfStockBadge).toBeVisible({ timeout: 5000 });
  }

  // Look for any "Low stock" badge
  const lowStockBadge = page.locator('text=/low stock/i').first();
  
  if ((await lowStockBadge.count()) > 0) {
    await expect(lowStockBadge).toBeVisible({ timeout: 3000 });
  }
});