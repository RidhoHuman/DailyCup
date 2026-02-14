import { test, expect } from '@playwright/test';

test('complete checkout with mock payment', async ({ page }) => {
  // Add a product to cart
  await page.goto('/menu');
  await page.getByRole('button', { name: /Add to Cart/ }).first().click();
  
  // Wait for cart update (header badge should appear)
  await page.waitForSelector('[data-testid="cart-badge"]', { timeout: 5000 });

  // Go to checkout
  await page.goto('/checkout', { waitUntil: 'networkidle' });
  
  // Ensure checkout form is visible before interacting
  await page.waitForSelector('input[placeholder="Full Name"]', { timeout: 10000 });
  // Fill form with controlled inputs
  await page.locator('input[placeholder="Full Name"]').fill('Test User');
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