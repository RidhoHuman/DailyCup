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
    
    // 2. Navigate to menu
    await page.click('text=Menu');
    await expect(page).toHaveURL(/\/menu/);
    
    // 3. Search for a product
    await page.fill('input[placeholder*="Search"]', 'cappuccino');
    await page.waitForTimeout(500); // Wait for search debounce
    
    // 4. Click on a product
    const productCard = page.locator('text=Cappuccino').first();
    await expect(productCard).toBeVisible();
    
    // 5. Add to cart
    const addToCartBtn = page.locator('button:has-text("Add to Cart")').first();
    await addToCartBtn.click();
    
    // 6. Verify cart badge updates
    const cartBadge = page.locator('[data-testid="cart-badge"]');
    await expect(cartBadge).toHaveText('1');
    
    // 7. Open cart
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
    await expect(products).toHaveCount(expect.any(Number));
    
    // All visible products should be coffee
    const firstProduct = products.first();
    await expect(firstProduct).toContainText(/coffee/i);
  });

  test('should handle product sorting', async ({ page }) => {
    await page.goto('/menu');
    
    // Sort by price: low to high
    await page.selectOption('select', 'price_low_high');
    await page.waitForTimeout(300);
    
    // Verify products are sorted
    const prices = await page.locator('[data-testid="product-price"]').allTextContents();
    const numericPrices = prices.map(p => parseInt(p.replace(/[^0-9]/g, '')));
    
    // Check if array is sorted
    for (let i = 1; i < numericPrices.length; i++) {
      expect(numericPrices[i]).toBeGreaterThanOrEqual(numericPrices[i - 1]);
    }
  });

  test('should update cart quantity', async ({ page }) => {
    await page.goto('/menu');
    
    // Add product to cart
    await page.click('button:has-text("Add to Cart")').first();
    
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
    await page.click('button:has-text("Add to Cart")').first();
    
    // Open cart
    await page.click('[data-testid="cart-button"]');
    
    // Remove item
    await page.click('button[aria-label="Remove item"]').first();
    
    // Verify cart is empty
    await expect(page.locator('text=Your cart is empty')).toBeVisible();
  });

  test('should persist cart after page refresh', async ({ page }) => {
    await page.goto('/menu');
    
    // Add product
    await page.click('button:has-text("Add to Cart")').first();
    
    // Refresh page
    await page.reload();
    
    // Cart should still have item
    const cartBadge = page.locator('[data-testid="cart-badge"]');
    await expect(cartBadge).toHaveText('1');
  });

  test('should handle out of stock products', async ({ page }) => {
    await page.goto('/menu');
    
    // Find out of stock product (if any)
    const outOfStockProduct = page.locator('text=Out of Stock').first();
    
    if (await outOfStockProduct.isVisible()) {
      // Add to cart button should be disabled
      const addBtn = outOfStockProduct.locator('..').locator('button');
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
    
    // Should show error messages
    await expect(page.locator('text=required')).toBeVisible();
  });

  test('should logout successfully', async ({ page }) => {
    // Assume user is logged in
    await page.goto('/dashboard');
    
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
    
    // Mobile menu should be visible
    const mobileMenuBtn = page.locator('[aria-label="Open menu"]');
    await expect(mobileMenuBtn).toBeVisible();
    
    // Click to open
    await mobileMenuBtn.click();
    
    // Menu should expand
    await expect(page.locator('nav')).toBeVisible();
  });

  test('should work on tablet viewport', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    
    await page.goto('/menu');
    
    // Product grid should adapt
    const productGrid = page.locator('[data-testid="product-grid"]');
    await expect(productGrid).toBeVisible();
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
    
    // Should have h1
    const h1 = page.locator('h1');
    await expect(h1).toBeVisible();
    
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
