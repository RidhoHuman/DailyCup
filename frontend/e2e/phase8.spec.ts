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
  
  const proceedBtn = page.locator('text=Proceed to Payment');
  await expect(proceedBtn).toBeVisible({ timeout: 5000 });
  await proceedBtn.click();

  // On payment page, simulate success
  const simulateSuccess = page.locator('text=Simulate Success');
  await expect(simulateSuccess).toBeVisible({ timeout: 5000 });
  await simulateSuccess.click();

  // Confirm order status updated
  await page.waitForSelector('text=Payment successful', { timeout: 5000 });
  await expect(page.locator('text=Payment successful')).toBeVisible();
});