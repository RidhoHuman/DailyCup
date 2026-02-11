import { test, expect } from '@playwright/test';

test('complete checkout with mock payment', async ({ page }) => {
  // Add a product to cart
  await page.goto('/menu');
  await page.getByRole('button', { name: /Add to Cart/ }).first().click();
  
  // Wait for cart update
  await page.waitForTimeout(500);

  // Go to checkout
  await page.goto('/checkout', { waitUntil: 'networkidle' });
  
  // Fill form with controlled inputs
  await page.locator('input[placeholder="Full name"]').fill('Test User');
  await page.locator('input[placeholder="Phone number"]').fill('081234567890');
  await page.locator('input[placeholder="Email address"]').fill('test@example.com');
  await page.locator('textarea[placeholder="Delivery address"]').fill('Jl. Test 123');
  
  await page.click('text=Proceed to Payment');

  // On payment page, simulate success
  await page.waitForSelector('text=Simulate Success');
  await page.click('text=Simulate Success');

  // Confirm order status updated
  await page.waitForSelector('text=Payment successful', { timeout: 5000 });
  await expect(page.locator('text=Payment successful')).toBeVisible();
});