import { test, expect } from '@playwright/test';

test('product variants adjust price and add to cart', async ({ page }) => {
  await page.goto('/menu');

  // Locate the specific Cappuccino product card - find the h3 then navigate to its card container
  // The card is a direct parent div with rounded-lg styling
  const cappuccinoHeading = page.locator('h3').filter({ hasText: 'Cappuccino' }).first();
  const card = cappuccinoHeading.locator('xpath=ancestor::div[contains(@class, "rounded")]').first();

  // Select Large size and Iced temperature
  await card.getByRole('button', { name: 'Large' }).first().click();
  await card.getByRole('button', { name: 'Iced' }).first().click();

  // Ensure the card is visible
  await expect(card).toBeVisible();

  // Debug: log innerHTML if price can't be found
  const cardHtml = await card.innerHTML();
  console.log('CARD HTML SNIPPET:', cardHtml.substring(0, 400));

  // Read displayed price from the card
  const priceText = await card.locator('text=/Rp\\s*[0-9,.]+/').first().textContent();
  const displayed = Number((priceText || '').replace(/[^0-9]/g, ''));

  // Expected: base 35000 + 5000 + 2000 = 42000
  const expected = 35000 + 5000 + 2000;
  expect(displayed).toBe(expected);

  // Add to cart
  const addBtn = card.getByRole('button', { name: 'Add to Cart' }).first();
  await expect(addBtn).toBeVisible({ timeout: 5000 });
  await addBtn.click();

  // Wait for cart update before navigating
  await page.waitForTimeout(500);

  // Open cart page and verify item total in Order Summary
  await page.goto('/cart', { waitUntil: 'networkidle' });
  const subtotalLabel = page.getByText(/Subtotal/).locator('..');
  const subtotalValueText = await subtotalLabel.locator('span').nth(1).textContent();
  const subtotal = Number((subtotalValueText || '').replace(/[^0-9]/g, ''));
  expect(subtotal).toBe(expected);
});

test('stock indicators shown and out-of-stock disables add button', async ({ page }) => {
  await page.goto('/menu');

  // Iced Special should show Out of stock and add button disabled
  const icedLocator = page.getByText('Iced Special').first();
  await expect(icedLocator).toBeVisible({ timeout: 5000 });
  const outCard = icedLocator.locator('xpath=ancestor::div[contains(@class, "rounded")]').first();
  await expect(outCard.locator('text=/out of stock/i')).toBeVisible({ timeout: 5000 });
  // The add button may be present but disabled — or not rendered at all for out-of-stock items
  const addBtnCount = await outCard.getByRole('button', { name: 'Add to Cart' }).count();
  if (addBtnCount > 0) {
    await expect(outCard.getByRole('button', { name: 'Add to Cart' }).first()).toBeDisabled();
  } else {
    // if button absent, assert that the card displays out-of-stock state (acceptable)
    await expect(outCard.locator('text=/out of stock/i')).toBeVisible({ timeout: 3000 });
  }

  // Filter Brew should show Low stock (guarded)
  const lowLocator = page.getByText('Filter Brew').first();
  if (await lowLocator.count() > 0) {
    const lowCard = lowLocator.locator('xpath=ancestor::div[contains(@class, "rounded")]').first();
    await expect(lowCard.locator('text=/low stock/i')).toBeVisible({ timeout: 3000 });
  }
});