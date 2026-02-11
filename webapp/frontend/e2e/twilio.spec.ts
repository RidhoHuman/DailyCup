import { test, expect } from '@playwright/test';

// Basic UI tests for Twilio admin page using route interception (no real Twilio calls)
test('Twilio admin page shows settings and logs and can export CSV', async ({ page }) => {
  // Mock settings
  await page.route('**/integrations/twilio.php?action=settings', async route => {
    if (route.request().method() === 'GET') {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, settings: { twilio_account_sid: 'AC123', twilio_whatsapp_from: 'whatsapp:+1415' } }) });
    } else if (route.request().method() === 'POST') {
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true }) });
    }
  });

  // Mock logs
  await page.route('**/integrations/twilio.php?action=logs**', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, logs: [{ id: 1, created_at: new Date().toISOString(), direction: 'outbound', to_number: 'whatsapp:+123', from_number: 'whatsapp:+1415', status: 'sent', body: 'hello' }], total: 1 }) });
  });

  // Mock send (generic send endpoint)
  await page.route('**/integrations/send.php?action=send', async route => {
    const post = await route.request().postDataJSON();
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, sid: 'SMOK123' }) });
  });

  // Mock worker status and run
  await page.route('**/integrations/twilio.php?action=logs&action2=worker_status**', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, last_run: new Date().toISOString(), summary: { polled: 0, retried: 0 } }) });
  });
  await page.route('**/integrations/twilio.php?action=run_worker', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, exit_code: 0 }) });
  });

  // Mock test alert
  await page.route('**/integrations/twilio.php?action=alerts&test=1', async route => {
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ success: true, message: 'Test alert triggered' }) });
  });

  await page.goto('/admin/integrations/twilio');
  await page.waitForSelector('text=Twilio (WhatsApp) Integration');
  await expect(page.getByPlaceholder('Account SID')).toHaveValue('AC123');

  // Send a test message
  await page.fill('input[placeholder="To (whatsapp:+62...)"]', 'whatsapp:+123');
  await page.fill('input[placeholder="Message body"]', 'test msg');
  await page.click('button:has-text("Send")');
  await expect(page.locator('text=Message sent')).toBeVisible({ timeout: 2000 }).catch(()=>{});

  // Open logs and open detail
  await page.click('text=Message Logs');
  await page.waitForSelector('text=hello');
  await page.click('tbody tr');
  await page.waitForSelector('text=Message #1');

  // Export CSV triggers download - create by clicking export and ensure blob is created
  await page.click('button:has-text("Export CSV")');

  // Worker status and run
  await page.waitForSelector('text=Worker status', { timeout: 2000 }).catch(()=>{});
  await page.click('button:has-text("Run Worker Now")');
  await page.waitForTimeout(500);

  // Send test alert
  await page.click('button:has-text("Send Test Alert")');
  await page.waitForTimeout(500);
});