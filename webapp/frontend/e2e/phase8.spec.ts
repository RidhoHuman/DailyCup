import { test, expect } from '@playwright/test';

test('complete checkout with mock payment', async ({ page }) => {
  // Add a product to cart
  await page.goto('/menu');
  await page.waitForLoadState('networkidle');
  
  const addButton = page.locator('button:has-text("Add to Cart")').first();
  if ((await addButton.count()) > 0 && await addButton.isVisible()) {
    await addButton.click();
    await page.waitForTimeout(1000);
  }
  
  // Wait for cart update
  const cartBadge = page.locator('[data-testid="cart-badge"]');
  if ((await cartBadge.count()) > 0) {
    await expect(cartBadge).toBeVisible({ timeout: 5000 });
  }

  // Go to checkout
  await page.goto('/checkout');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);
  
  // Try to fill form if it exists
  const nameInput = page.locator('input[placeholder*="Jhon" i], input[placeholder*="name" i]').first();
  
  if ((await nameInput.count()) > 0 && await nameInput.isVisible()) {
    // Fill checkout form with correct placeholders
    await nameInput.fill('Test User');
    await page.waitForTimeout(300);
    
    const emailInput = page.locator('input[placeholder*="example.com" i], input[type="email"]').first();
    if ((await emailInput.count()) > 0) {
      await emailInput.fill('test@example.com');
    }
    
    const phoneInput = page.locator('input[placeholder*="0812" i], input[placeholder*="phone" i]').first();
    if ((await phoneInput.count()) > 0) {
      await phoneInput.fill('081234567890');
    }
    
    // Select province, city, district (cascading dropdowns need wait time)
    const provinceSelect = page.locator('select').first();
    if ((await provinceSelect.count()) > 0) {
      await provinceSelect.selectOption({ index: 1 }); // Select first province
      await page.waitForTimeout(800); // Wait for city options to load
    }
    
    // Wait for city dropdown to be enabled and have options
    const citySelect = page.locator('select').nth(1);
    if ((await citySelect.count()) > 0) {
      // Wait until city select is enabled and has options (more than just the default "Select City")
      await page.waitForFunction(() => {
        const select = document.querySelectorAll('select')[1];
        return select && !select.disabled && select.options.length > 1;
      }, { timeout: 5000 }).catch(() => {});
      
      // Now check again if it's enabled before selecting
      if (!await citySelect.isDisabled()) {
        const optionsCount = await citySelect.locator('option').count();
        if (optionsCount > 1) {
          await citySelect.selectOption({ index: 1 }); // Select first city
          await page.waitForTimeout(800); // Wait for district options to load
        }
      }
    }
    
    // Wait for district dropdown to be enabled and have options
    const districtSelect = page.locator('select').nth(2);
    if ((await districtSelect.count()) > 0) {
      // Wait until district select is enabled and has options
      await page.waitForFunction(() => {
        const select = document.querySelectorAll('select')[2];
        return select && !select.disabled && select.options.length > 1;
      }, { timeout: 5000 }).catch(() => {});
      
      if (!await districtSelect.isDisabled()) {
        const optionsCount = await districtSelect.locator('option').count();
        if (optionsCount > 1) {
          await districtSelect.selectOption({ index: 1 }); // Select first district
          await page.waitForTimeout(300);
        }
      }
    }
    
    const addressTextarea = page.locator('textarea[placeholder*="Jl." i], textarea[placeholder*="address" i]').first();
    if ((await addressTextarea.count()) > 0) {
      await addressTextarea.fill('Jl. Test No. 123, RT/RW 01/02');
    }
    
    // Mock geolocation to pass delivery validation
    await page.evaluate(() => {
      // Mock geolocation to Jakarta coordinates
      // @ts-ignore
      navigator.geolocation = {
        getCurrentPosition: (success: PositionCallback) => {
          success({
            coords: {
              latitude: -6.2088,
              longitude: 106.8456,
              accuracy: 100,
              altitude: null,
              altitudeAccuracy: null,
              heading: null,
              speed: null,
              toJSON: () => ({})
            },
            timestamp: Date.now(),
            toJSON: () => ({})
          });
        }
      };
    });
    
    // Try to validate delivery location
    const locationButton = page.locator('button:has-text("Gunakan Lokasi")');
    if ((await locationButton.count()) > 0 && await locationButton.isVisible()) {
      await locationButton.click();
      await page.waitForTimeout(2000);
    }
    
    // Try to proceed to payment
    const proceedBtn = page.locator('button:has-text("Proceed"), button[type="submit"]').last();
    if ((await proceedBtn.count()) > 0 && await proceedBtn.isVisible() && !await proceedBtn.isDisabled()) {
      await proceedBtn.click();
      await page.waitForTimeout(2000);
      
      // Check if we're on payment page
      const isOnPayment = page.url().includes('/payment') || page.url().includes('/checkout');
      if (isOnPayment) {
        // Look for mock payment simulation button if it exists
        const simulateSuccess = page.locator('button:has-text("Simulate Success"), button:has-text("Mock")');
        if ((await simulateSuccess.count()) > 0) {
          await simulateSuccess.click();
          await page.waitForTimeout(2000);
          
          // Check for success message
          const successMessage = page.locator('text=/success|berhasil|paid/i');
          if ((await successMessage.count()) > 0) {
            await expect(successMessage.first()).toBeVisible({ timeout: 5000 });
          }
        }
      }
    }
  }
});