# Email Testing & Configuration

## 📖 Overview

Complete email notification system for DailyCup with:
- ✅ Order confirmation emails
- ✅ Payment confirmation emails
- ✅ Order status update emails
- ✅ Welcome emails for new users
- ✅ Professional HTML templates
- ✅ Gmail SMTP integration

## 🚀 Quick Start (10 minutes)

### 1. **Gmail Setup** (~5 min)
```
Go to: https://myaccount.google.com/security
→ Enable 2-Step Verification
→ Generate App Password (Mail + Computer)
→ Copy the 16-character password
```

### 2. **Configure .env** (~2 min)
```bash
# File: backend/api/.env

SMTP_ENABLED=true
SMTP_USERNAME=your.email@gmail.com
SMTP_PASSWORD=xxxx xxxx xxxx xxxx    # Your app password here
```

### 3. **Run Test** (~1 min)
```bash
cd backend/tests
php test_email.php
```

### 4. **Check Email** (~2 min)
- Open Gmail
- Look for 4 test emails from "DailyCup Coffee Shop"
- If successful → ✅ Email system ready!

## 📚 Documentation

| Document | Purpose | Link |
|----------|---------|------|
| **QUICK_REFERENCE.txt** | Visual quick start (READ THIS FIRST) | [View](QUICK_REFERENCE.txt) |
| **GMAIL_SETUP_GUIDE.md** | Detailed step-by-step instructions | [View](GMAIL_SETUP_GUIDE.md) |
| **QUICK_EMAIL_TEST.md** | Terminal/CLI testing guide | [View](QUICK_EMAIL_TEST.md) |
| **EMAIL_TEST_PLAN.md** | Testing checklist & next steps | [View](EMAIL_TEST_PLAN.md) |
| **EMAIL_SYSTEM.md** | Complete system documentation | [View](EMAIL_SYSTEM.md) |

## 🧪 Testing Scripts

### Simple CLI Test
```bash
cd backend/tests
php test_email.php
```
Sends 4 test emails and displays results.

### Interactive Web Test
```bash
# Access in browser:
http://localhost/DailyCup/backend/tests/email_checklist.php
```
Interactive checklist with progress tracking.

### Configuration Check
```bash
# Via browser:
http://localhost/DailyCup/backend/tests/check_email_env.php
```
Verifies your SMTP configuration.

## 📂 File Structure

```
backend/
├── api/
│   ├── .env                          # SMTP configuration
│   ├── email/
│   │   └── EmailService.php          # Main email service
│   ├── create_order.php              # Sends order confirmation
│   ├── pay_order.php                 # Sends payment confirmation
│   ├── notify_midtrans.php           # Webhook: sends payment email
│   └── admin/
│       └── update_order_status.php   # Sends status update
│
├── templates/
│   └── email/
│       ├── order_confirmation.html
│       ├── payment_confirmation.html
│       ├── status_update.html
│       └── welcome.html
│
├── tests/
│   ├── test_email.php                # Simple CLI test
│   ├── email_checklist.php           # Interactive web test
│   ├── check_email_env.php           # Configuration check
│   └── run_email_tests.php           # Test executor
│
└── docs/
    ├── QUICK_REFERENCE.txt           # Visual quick start
    ├── GMAIL_SETUP_GUIDE.md          # Detailed guide
    ├── QUICK_EMAIL_TEST.md           # CLI guide
    ├── EMAIL_TEST_PLAN.md            # Checklist
    ├── EMAIL_SYSTEM.md               # Full documentation
    └── README.md                     # This file
```

## ⚙️ Configuration

### Required: SMTP Settings
```env
SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your.email@gmail.com
SMTP_PASSWORD=your-app-password-16-chars
SMTP_FROM_EMAIL=noreply@dailycup.com
SMTP_FROM_NAME="DailyCup Coffee Shop"
SMTP_ENCRYPTION=tls
APP_URL=http://localhost:3000
```

### Gmail App Password Setup
1. Enable 2-Step Verification: https://myaccount.google.com/security
2. Go to App passwords: https://myaccount.google.com/apppasswords
3. Select "Mail" and "Windows Computer"
4. Copy the 16-character password
5. Paste into `SMTP_PASSWORD` in `.env`

## 📧 Email Methods

### Order Confirmation
```php
EmailService::sendOrderConfirmation($order, $customer);
```
Sent when order is created in `create_order.php`

### Payment Confirmation
```php
EmailService::sendPaymentConfirmation($order, $customer);
```
Sent when payment is marked as paid in `pay_order.php` or `notify_midtrans.php`

### Status Update
```php
EmailService::sendStatusUpdate($order, $customer, $newStatus);
```
Sent when admin updates order status in `admin/update_order_status.php`

### Welcome Email
```php
EmailService::sendWelcomeEmail($user);
```
Can be sent when user registers (optional)

## ✅ Success Checklist

- [ ] 2-Step Verification enabled on Gmail
- [ ] App Password generated (16 chars)
- [ ] `.env` updated with credentials
- [ ] `SMTP_ENABLED=true` in `.env`
- [ ] `test_email.php` shows ✅ for all 4 tests
- [ ] All 4 test emails in Gmail inbox
- [ ] Email templates render correctly
- [ ] Ready for production!

## ❌ Common Issues

### "Failed to send email"
- Check `SMTP_ENABLED=true` in `.env`
- Verify app password (no typos)
- Check 2-Step Verification is enabled

### "Connection timeout"
- Try `SMTP_PORT=465` with `SMTP_ENCRYPTION=ssl`
- Check firewall not blocking port 587

### Emails in spam folder
- Mark as "not spam" in Gmail
- In production, use business email

### "Authentication failed"
- Regenerate app password
- Check for extra spaces in password
- Verify Gmail username is correct

## 🔄 Integration Points

### Create Order (create_order.php)
```php
// After successful order creation:
EmailService::sendOrderConfirmation($orderData, $customer);
```

### Pay Order (pay_order.php)
```php
// When payment is confirmed:
EmailService::sendPaymentConfirmation($orderData, $customerData);
```

### Payment Webhook (notify_midtrans.php)
```php
// On successful payment webhook:
if ($paymentStatus === 'paid') {
    EmailService::sendPaymentConfirmation($orderData, $customerData);
}
```

### Admin Status Update (admin/update_order_status.php)
```php
// When order status changes:
if ($newStatus && $newStatus !== $order['status']) {
    EmailService::sendStatusUpdate($orderData, $customerData, $newStatus);
}
```

## 🎯 Next Steps

After verifying email system works:

1. **Test with real orders**
   - Create order via frontend
   - Verify order confirmation email arrives
   - Complete payment
   - Verify payment confirmation arrives

2. **Test admin features**
   - Update order status in admin
   - Verify status email is sent

3. **Monitor delivery**
   - Check Gmail for bounce/failure notices
   - Monitor spam complaint rates

4. **Production readiness**
   - Switch to SendGrid/SES if needed
   - Set up email queue system
   - Monitor deliverability metrics

## 📊 Testing Summary

| Test | Time | Files |
|------|------|-------|
| CLI Test | 1 min | `test_email.php` |
| Web Test | 2 min | `email_checklist.php` |
| Real Order | 3 min | API + frontend |
| All Tests | ~10 min | Complete verification |

## 🛠️ Maintenance

### Check Email Delivery
- Monitor Gmail inbox/spam folder
- Look for bounce notifications
- Check error logs for failures

### Monitor Performance
- Log email send times
- Track delivery rates
- Monitor bounce rates

### Update Configuration
- If changing SMTP provider
- Update `.env` with new credentials
- Run test script to verify

## 📞 Support

**Having issues?**

1. Check error message
2. Review relevant documentation
3. Check error logs
4. Review troubleshooting section

**Key files:**
- `.env` - Configuration
- `error.log` - Error messages
- `EmailService.php` - Main class
- Documentation files - Guides

---

## 🎉 Email System Ready!

Once all tests pass:
- ✅ Order confirmations working
- ✅ Payment confirmations working
- ✅ Status updates working
- ✅ Welcome emails working

**You're ready to move on to Phase 11 remaining tasks!**
