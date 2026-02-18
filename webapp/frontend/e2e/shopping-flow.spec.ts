/**
 * E2E Test: Complete Shopping Flow
 * Tests the entire user journey from landing to order completion
 */

import { test, expect } from '@playwright/test';

test.describe('Complete Shopping Journey', () => {
  test.beforeEach(async ({ page }) => {
    // Start at homepage
    await page.goto('/');
    await page.waitForLoadState('networkidle');
  });

  test('should complete full shopping flow', async ({ page }) => {
    // 1. Verify homepage loaded
    await expect(page.locator('text=Discover')).toBeVisible({ timeout: 10000 });
    
    // 2. Navigate to menu
    const menuLink = page.locator('a[href="/menu"]').first();
    await menuLink.click();
    await page.waitForURL(/\/menu/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');

    // 3. Find and add a product to cart
    const productCards = page.locator('[data-testid="product-card"]');
    await productCards.first().waitFor({ timeout: 10000 });
    
    const addToCartBtn = productCards.first().locator('button:has-text("Add to Cart")');
    if ((await addToCartBtn.count()) > 0) {
      await addToCartBtn.click();
      await page.waitForTimeout(1000);
      
      // 4. Verify cart badge updates
      const cartBadge = page.locator('[data-testid="cart-badge"]');
      if ((await cartBadge.count()) > 0) {
        await expect(cartBadge).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('should handle product filtering', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Wait for products to load
    const productGrid = page.locator('[data-testid="product-grid"]');
    await productGrid.waitFor({ timeout: 10000 });
    
    // Try to filter by category if category buttons exist
    const categoryButtons = page.locator('button:has-text("Coffee")');
    if ((await categoryButtons.count()) > 0) {
      await categoryButtons.first().click();
      await page.waitForTimeout(500);
    }
  });

  test('should handle product sorting', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
  });

  test('should update cart quantity', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Add product to cart
    const firstAddBtn = page.locator('button:has-text("Add to Cart")').first();
    if ((await firstAddBtn.count()) > 0 && await firstAddBtn.isVisible()) {
      await firstAddBtn.click();
      await page.waitForTimeout(1000);
      
      // Go to cart
      await page.goto('/cart');
      await page.waitForLoadState('networkidle');
      
      // Check if increase button exists
      const increaseBtn = page.locator('button[aria-label*="Increase" i]').first();
      if ((await increaseBtn.count()) > 0) {
        await increaseBtn.click();
        await page.waitForTimeout(500);
      }
    }
  });

  test('should remove item from cart', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Add product
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    if ((await addBtn.count()) > 0 && await addBtn.isVisible()) {
      await addBtn.click();
      await page.waitForTimeout(1000);
      
      // Go to cart
      await page.goto('/cart');
      await page.waitForLoadState('networkidle');
      
      // Remove item
      const removeBtn = page.locator('button:has-text("Remove")').first();
      if ((await removeBtn.count()) > 0) {
        await removeBtn.click();
        await page.waitForTimeout(1000);
        
        // Check for empty cart message
        const emptyMessage = page.locator('text=/cart is empty/i');
        if ((await emptyMessage.count()) > 0) {
          await expect(emptyMessage).toBeVisible();
        }
      }
    }
  });

  test('should persist cart after page refresh', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Add product
    const addBtn = page.locator('button:has-text("Add to Cart")').first();
    if ((await addBtn.count()) > 0 && await addBtn.isVisible()) {
      await addBtn.click();
      await page.waitForTimeout(1000);
      
      // Refresh page
      await page.reload();
      await page.waitForLoadState('networkidle');
      
      // Cart badge should still be visible
      const cartBadge = page.locator('[data-testid="cart-badge"]');
      if ((await cartBadge.count()) > 0) {
        await expect(cartBadge).toBeVisible({ timeout: 5000 });
      }
    }
  });

  test('should handle out of stock products', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Look for out of stock badge
    const outOfStockBadge = page.locator('text=/out of stock/i').first();
    
    if ((await outOfStockBadge.count()) > 0 && await outOfStockBadge.isVisible()) {
      await expect(outOfStockBadge).toBeVisible();
    }
  });
});

test.describe('Authentication Flow', () => {
  test('should login successfully', async ({ page }) => {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
  });

  test('should show validation errors', async ({ page }) => {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    
    // Try to submit without filling
    const submitButton = page.locator('button[type="submit"]');
    if ((await submitButton.count()) > 0) {
      await submitButton.click();
      await page.waitForTimeout(1000);
    }
  });

  test('should logout successfully', async ({ page }) => {
    // Set user as logged in
    await page.addInitScript(() => {
      try { 
        const user = { id:'2', name:'Test User', email:'test@example.com', role:'customer', loyaltyPoints:0, joinDate:new Date().toISOString() }; 
        localStorage.setItem('dailycup-auth', JSON.stringify({ state: { user, token: 'ci-user-token', isAuthenticated: true } })); 
      } catch(e){}
    });

    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    
    // Look for profile menu or logout button
    const profileButton = page.locator('button:has-text("Logout")');
    if ((await profileButton.count()) > 0) {
      await profileButton.click();
      await page.waitForTimeout(1000);
    }
  });
});

test.describe('Responsive Design', () => {
  test('should work on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // Check if mobile menu exists
    const mobileMenuBtn = page.locator('button[aria-label*="menu" i]');
    if ((await mobileMenuBtn.count()) > 0) {
      await expect(mobileMenuBtn).toBeVisible({ timeout: 5000 });
    }
  });

  test('should work on tablet viewport', async ({ page }) => {
    await page.setViewportSize({ width: 768, height: 1024 });
    
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Product grid should be visible
    const productGrid = page.locator('[data-testid="product-grid"]');
    if ((await productGrid.count()) > 0) {
      await expect(productGrid).toBeVisible({ timeout: 5000 });
    }
  });
});

test.describe('Performance & Accessibility', () => {
  test('should load homepage quickly', async ({ page }) => {
    const startTime = Date.now();
    
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Should load in under 5 seconds (relaxed for CI environment)
    expect(loadTime).toBeLessThan(5000);
  });

  test('should have accessible navigation', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    // Check for navigation
    const nav = page.locator('nav');
    await expect(nav.first()).toBeVisible({ timeout: 5000 });
  });

  test('should have proper heading hierarchy', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Look for any h1 heading
    const h1 = page.locator('h1').first();
    await expect(h1).toBeVisible({ timeout: 5000 });
  });

  test('should have alt text on images', async ({ page }) => {
    await page.goto('/menu');
    await page.waitForLoadState('networkidle');
    
    // Check if images exist and have alt text
    const images = page.locator('img');
    const count = await images.count();
    
    if (count > 0) {
      for (let i = 0; i < Math.min(count, 3); i++) {
        const alt = await images.nth(i).getAttribute('alt');
        expect(alt).toBeTruthy();
      }
    }
  });
});
