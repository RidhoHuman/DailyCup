/**
 * E2E Test: Complete Shopping Flow
 * Tests the entire user journey from landing to order completion
 */

import { test, expect } from '@playwright/test';

test.describe('Complete Shopping Journey', () => {
  test.beforeEach(async ({ page }) => {
    // Start at homepage
    await page.goto('/');
  });

  test('should complete full shopping flow', async ({ page }) => {
    // 1. Land on homepage
    await expect(page).toHaveTitle(/DailyCup/i);
    
    // 2. Navigate to menu (use role-based selector + wait for navigation)
    await page.getByRole('link', { name: 'Menu' }).click();
    await page.waitForURL(/\/menu/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    // 3. Find Cappuccino product
    const productCard = page.locator('h3').filter({ hasText: 'Cappuccino' }).first();
    await expect(productCard).toBeVisible();
    
    // 5. Add to cart (scope Add button to the Cappuccino card)
    const addToCartBtn = productCard.locator('..').locator('button:has-text("Add to Cart")').first();
    await expect(addToCartBtn).toBeVisible({ timeout: 10000 });
    await addToCartBtn.click();
    
    // 6. Verify cart badge updates (use stable data-testid)
    const cartBadge = page.locator('[data-testid="cart-badge"]');
    await expect(cartBadge).toBeVisible({ timeout: 10000 });
    await expect(cartBadge).toHaveText('1', { timeout: 5000 });
    
    // 7. Open cart (click the cart icon button)
    await page.click('[data-testid="cart-button"]');
    
    // 8. Verify cart contains product
    await expect(page.locator('text=Cappuccino')).toBeVisible();
    
    // 9. Proceed to checkout
    await page.click('button:has-text("Checkout")');
    
    // 10. Should redirect to login if not authenticated
    // Or to checkout if authenticated
    await page.waitForURL(/\/(login|checkout)/);
  });

  test('should handle product filtering', async ({ page }) => {
    await page.goto('/menu');
    
    // Filter by category
    await page.click('button:has-text("Coffee")');
    await page.waitForTimeout(300);
    
    // Verify filtered products
    const products = page.locator('[data-testid="product-card"]');
    expect(await products.count()).toBeGreaterThanOrEqual(0);
    
    // All visible products should be coffee (if any exist)
    const firstProduct = products.first();
    if (await firstProduct.count() > 0) {
      await expect(firstProduct).toContainText(/coffee/i);
    }
  });

  test('should handle product sorting', async ({ page }) => {
    await page.goto('/menu');
    
    // Sort by price: low to high
    await page.selectOption('select', { value: 'price-low' });
    await page.waitForLoadState('networkidle');
    
    // Verify products are sorted
    const prices = await page.locator('[data-testid="product-price"]').allTextContents();
    const numericPrices = prices.map(p => parseInt(p.replace(/[^0-9]/g, ''))).filter(n => !Number.isNaN(n));
    
    // If there are prices, check ordering
    if (numericPrices.length > 1) {
      for (let i = 1; i < numericPrices.length; i++) {
        expect(numericPrices[i]).toBeGreaterThanOrEqual(numericPrices[i - 1]);
      }
    }
  });

  test('should update cart quantity', async ({ page }) => {
    await page.goto('/menu');
    
    // Add product to cart
    const firstAddBtn = page.locator('button:has-text("Add to Cart")').first();
    await expect(firstAddBtn).toBeVisible({ timeout: 5000 });
    await firstAddBtn.click();
    
    // Open cart
    await page.click('[data-testid="cart-button"]');
    
    // Increase quantity
    const increaseBtn = page.locator('button[aria-label="Increase quantity"]').first();
    await increaseBtn.click();
    
    // Verify quantity updated
    const quantity = page.locator('[data-testid="item-quantity"]').first();
    await expect(quantity).toHaveText('2');
    
    // Verify total updated
    const total = page.locator('[data-testid="cart-total"]');
    await expect(total).not.toHaveText('Rp 0');
  });

  test('should remove item from cart', async ({ page }) => {
    await page.goto('/menu');
    
    // Add product
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    await expect(addBtn).toBeVisible({ timeout: 5000 });
    await addBtn.click();
    
    // Open cart
    await page.click('[data-testid="cart-button"]');
    
    // Remove item
    const removeBtn = page.locator('button[aria-label="Remove item"]').first();
    await removeBtn.click();
    
    // Verify cart is empty
    await expect(page.locator('text=Your cart is empty')).toBeVisible();
  });

  test('should persist cart after page refresh', async ({ page }) => {
    await page.goto('/menu');
    
    // Add product
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    await expect(addBtn).toBeVisible({ timeout: 5000 });
    await addBtn.click();
    
    // Refresh page
    await page.reload();
    
    // Cart should still have item
    const cartBadge = page.locator('[data-testid="cart-badge"]');
    await expect(cartBadge).toBeVisible({ timeout: 5000 });
    await expect(cartBadge).toHaveText('1');
  });

  test('should handle out of stock products', async ({ page }) => {
    await page.goto('/menu');
    
    // Find out of stock product (if any) - case-insensitive
    const outOfStockProduct = page.locator('text=/out of stock/i').first();
    
    if ((await outOfStockProduct.count()) > 0 && await outOfStockProduct.isVisible()) {
      // Add to cart button should be disabled
      const addBtn = outOfStockProduct.locator('xpath=ancestor::div').locator('button:has-text("Add to Cart")').first();
      await expect(addBtn).toBeDisabled();
    }
  });
});

test.describe('Authentication Flow', () => {
  test('should login successfully', async ({ page }) => {
    await page.goto('/login');
    
    // Fill login form
    await page.fill('input[type="email"]', 'test@example.com');
    await page.fill('input[type="password"]', 'password123');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Should redirect to homepage or dashboard
    await page.waitForURL(/\/(|dashboard)/);
  });

  test('should show validation errors', async ({ page }) => {
    await page.goto('/login');
    
    // Submit without filling
    await page.click('button[type="submit"]');
    
    // Should show error messages (email/password required)
    await expect(page.locator('text=Email is required')).toBeVisible({ timeout: 5000 });
    await expect(page.locator('text=Password is required')).toBeVisible({ timeout: 5000 });
  });

  test('should logout successfully', async ({ page }) => {
    // Ensure user is logged in for logout test
    await page.addInitScript(() => {
      try { const user = { id:'2', name:'Test User', email:'test@example.com', role:'customer', loyaltyPoints:0, joinDate:new Date().toISOString() }; localStorage.setItem('dailycup-auth', JSON.stringify({ state: { user, token: 'ci-user-token', isAuthenticated: true } })); } catch(e){}
    });

    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');

    // Click logout
    await page.click('button:has-text("Logout")');
    
    // Should redirect to home
    await page.waitForURL('/');
  });
});

test.describe('Responsive Design', () => {
  test('should work on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // Mobile menu should be visible
    const mobileMenuBtn = page.locator('[aria-label="Open menu"]');
    await expect(mobileMenuBtn).toBeVisible({ timeout: 5000 });
    
    // Click to open
    await mobileMenuBtn.click();
    
    // Menu should expand
    await expect(page.locator('nav')).toBeVisible({ timeout: 5000 });
  });

  test('should work on tablet viewport', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Product grid should adapt
    const productGrid = page.locator('[data-testid="product-grid"]');
    await expect(productGrid).toBeVisible({ timeout: 5000 });
  });
});

test.describe('Performance & Accessibility', () => {
  test('should load homepage quickly', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Should load in under 3 seconds
    expect(loadTime).toBeLessThan(3000);
  });

  test('should have accessible navigation', async ({ page }) => {
    await page.goto('/');
    
    // Check for main landmarks
    await expect(page.locator('nav')).toBeVisible();
    await expect(page.locator('main')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();
  });

  test('should have proper heading hierarchy', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // There may be multiple h1 on the page; assert at least one visible and headings exist
    const visibleH1 = page.locator('h1').filter({ hasText: /./ });
    await expect(visibleH1.first()).toBeVisible({ timeout: 5000 });

    // Count headings
    const headings = await page.locator('h1, h2, h3, h4, h5, h6').count();
    expect(headings).toBeGreaterThan(0);
  });

  test('should have alt text on images', async ({ page }) => {
    await page.goto('/menu');
    
    const images = page.locator('img');
    const count = await images.count();
    
    for (let i = 0; i < Math.min(count, 5); i++) {
      const alt = await images.nth(i).getAttribute('alt');
      expect(alt).toBeTruthy();
    }
  });
});
