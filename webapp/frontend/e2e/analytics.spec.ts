import { test, expect } from '@playwright/test';

test.setTimeout(60000);

test('Analytics page shows integration KPIs', async ({ page }) => {

  // ✅ FIX 1: Struktur mock data disesuaikan dengan Interface di page.tsx
  // page.tsx expect: data.analytics.revenue.total_revenue (bukan data.revenue.total)
  const mockApiResponse = {
    success: true,
    analytics: {
      revenue: {
        total_revenue: 500000,       // Ditampilkan via formatCurrency → "Rp 500.000"
        total_orders: 10,
        paid_orders: 8,
        pending_orders: 2,
        average_order_value: 50000,
        conversion_rate: 80.0,
      },
      best_sellers: [
        {
          product_id: 1,
          product_name: 'Kopi Susu',
          total_sold: 42,
          total_revenue: '1750000',
        }
      ],
      payment_methods: [
        {
          payment_method: 'transfer',
          count: 8,
          total_amount: '400000',
        }
      ],
      order_status: [
        {
          payment_status: 'paid',
          count: 8,
          total_amount: '400000',
        },
        {
          payment_status: 'pending',
          count: 2,
          total_amount: '100000',
        }
      ],
      comparison: {
        revenue_change: 12.5,
        orders_change: 5.0,
      }
    }
  };

  // ✅ FIX 2: Intercept di level network SEBELUM addInitScript
  // page.route lebih reliable daripada mock fetch/XHR manual
  await page.route('**/analytics.php**', (route) => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(mockApiResponse),
    });
  });

  // ✅ FIX 3: Hanya setup auth di addInitScript, tidak perlu mock fetch/XHR manual
  // karena page.route() sudah menangani intercept di level network
  await page.addInitScript(() => {
    window.localStorage.clear();
    const auth = {
      state: {
        user: { id: '1', role: 'admin' },
        token: 'ci-admin-token',
        isAuthenticated: true
      }
    };
    window.localStorage.setItem('dailycup-auth', JSON.stringify(auth));
    window.localStorage.setItem('token', 'ci-admin-token');
  });

  // 4. Navigasi
  await page.goto('http://127.0.0.1:3000/admin/analytics', {
    waitUntil: 'domcontentloaded',
    timeout: 45000,
  });

  // 5. Tunggu loading spinner hilang (page.tsx render spinner saat loading=true)
  await page.waitForSelector('.animate-spin', { state: 'hidden', timeout: 20000 })
    .catch(() => { /* spinner mungkin sudah tidak ada */ });

  // 6. Tunggu heading Analytics muncul
  await page.waitForSelector('text=/Analytics/i', { timeout: 20000 });

  // ✅ FIX 4: Locator disesuaikan dengan output formatCurrency(500000)
  // formatCurrency pakai Intl id-ID → "Rp 500.000" (titik sebagai pemisah ribuan)
  // Jadi kita cari "500.000" bukan "500"
  const revenueLocator = page.locator('text=Rp 500.000').first();

  // Untuk total_orders = 10, page.tsx render: analytics?.revenue?.total_orders
  const ordersLocator = page.locator('text=10').first();

  // conversion_rate = 80.0 → page.tsx render: "80.0%"
  const conversionLocator = page.locator('text=80.0%').first();

  // best_seller product name
  const productLocator = page.locator('text=Kopi Susu').first();

  // ✅ FIX 5: Hapus reload paksa — tidak diperlukan karena mock sudah benar dari awal
  // Reload paksa justru berbahaya karena addInitScript tidak re-inject mock XHR
  // (page.route() masih aktif, tapi timing bisa bermasalah)

  // Final Assertions
  await expect(revenueLocator).toBeVisible({ timeout: 15000 });
  await expect(ordersLocator).toBeVisible({ timeout: 15000 });
  await expect(conversionLocator).toBeVisible({ timeout: 15000 });
  await expect(productLocator).toBeVisible({ timeout: 15000 });
  await expect(page.getByText(/Kopi Susu/i).first()).toBeVisible();
});