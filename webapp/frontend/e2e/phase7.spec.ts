import { test, expect } from '@playwright/test';

test('profile edit validation and save', async ({ page }) => {
  // Set up dialog handler before navigation
  page.on('dialog', async (dialog) => {
    await dialog.accept();
  });

  // Ensure user is logged in (persisted auth) so Profile page shows Edit button
  await page.addInitScript(() => {
    try {
      const user = { id: '2', name: 'Test User', email: 'test@example.com', role: 'customer', loyaltyPoints: 0, joinDate: new Date().toISOString() };
      localStorage.setItem('dailycup-auth', JSON.stringify({ state: { user, token: 'ci-user-token', isAuthenticated: true } }));
    } catch (e) { }
  });

  await page.goto('/');
  await page.waitForLoadState('networkidle');

  // Force reload so persisted auth in localStorage hydrates into Zustand store
  await page.reload();

  await page.getByRole('link', { name: 'Profile' }).click();
  await expect(page).toHaveURL(/\/profile|\/account|\/dashboard/);

  // Start editing (give extra time for client-side hydration)
  await expect(page.getByRole('button', { name: 'Edit Profile' })).toBeVisible({ timeout: 15000 });
  await page.getByRole('button', { name: 'Edit Profile' }).click();

  // Ensure name input visible before validation
  const nameInput = page.locator('input[type="text"]').first();
  await expect(nameInput).toBeVisible({ timeout: 3000 });

  // Clear name to trigger validation - use input directly since label is not linked via for/id
  await nameInput.fill('');
  await page.getByRole('button', { name: 'Save Changes' }).click();
  await expect(page.locator('text=Name is required')).toBeVisible();

  // Fill valid name and save
  await nameInput.fill('Test User');
  
  // Click save and wait for the simulated API call (1 second delay in profile page)
  await page.getByRole('button', { name: 'Save Changes' }).click();
  
  // Wait for button text to change back from "Saving..." indicating completion
  await expect(page.getByRole('button', { name: 'Edit Profile' })).toBeVisible({ timeout: 5000 });
});

test('add item to cart, apply coupon and checkout alert', async ({ page }) => {
  await page.goto('/menu');

  // Add first available product to cart
  const addButtons = page.getByRole('button', { name: /Add to Cart|Customize & Add/ });
  await addButtons.first().click();

  // Navigate to cart page
  await page.getByRole('link', { name: 'Cart' }).click();
  await expect(page.locator('text=My Cart')).toBeVisible();

  // Apply coupon
  await page.fill('input[placeholder="Enter coupon code"]', 'WELCOME10');
  await page.getByRole('button', { name: 'Apply' }).click();

  // Expect applied coupon description visible
  await expect(page.locator('text=10% off welcome discount')).toBeVisible({ timeout: 5000 });

  // Checkout shows placeholder alert (Phase 8)
  page.on('dialog', async (dialog) => {
    expect(dialog.message()).toContain('Checkout functionality will be implemented in Phase 8');
    await dialog.accept();
  });

  await page.getByRole('button', { name: 'Proceed to Checkout' }).click();
});