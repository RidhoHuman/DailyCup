# Gmail SMTP Setup & Email Testing Guide

## 📋 Table of Contents
1. [Step 1: Enable 2-Step Verification](#step-1-enable-2-step-verification)
2. [Step 2: Generate App Password](#step-2-generate-app-password)
3. [Step 3: Configure .env](#step-3-configure-env)
4. [Step 4: Run Test Script](#step-4-run-test-script)
5. [Step 5: Check Email Results](#step-5-check-email-results)
6. [Troubleshooting](#troubleshooting)

---

## Step 1: Enable 2-Step Verification

**Duration:** ~5 minutes

### For Gmail Users:

1. **Go to Google Account:**
   - Open https://myaccount.google.com/
   - Click **"Security"** in left menu

2. **Find 2-Step Verification:**
   - Scroll down to find **"2-Step Verification"**
   - Click on it
   - Click **"Get Started"**

3. **Follow the prompts:**
   - Select your phone number
   - Choose SMS or call
   - Enter the verification code
   - Click **"Activate"**

✅ **Status:** 2-Step Verification enabled

---

## Step 2: Generate App Password

**Duration:** ~3 minutes

### Steps:

1. **Go back to Security:**
   - https://myaccount.google.com/security
   - Scroll to **"App passwords"** (appears only after 2-Step Verification is enabled)

2. **Generate Password:**
   - Click **"App passwords"**
   - Select **"Mail"** from dropdown
   - Select **"Windows Computer"** (or your device)
   - Click **"Generate"**

3. **Copy the Password:**
   - Google will show a **16-character password**
   - **Copy this password** (you'll use it in step 3)
   - Click **"Done"**

✅ **Password Format:** `xxxx xxxx xxxx xxxx` (with spaces)

---

## Step 3: Configure .env

**Duration:** ~2 minutes

### 3a. Open your .env file:
```bash
c:\laragon\www\DailyCup\webapp\backend\api\.env
```

### 3b. Find the SMTP section and update:

**BEFORE:**
```env
SMTP_ENABLED=false
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@dailycup.com
SMTP_FROM_NAME="DailyCup Coffee Shop"
SMTP_ENCRYPTION=tls
APP_URL=http://localhost:3000
```

**AFTER (Example with actual values):**
```env
SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your.email@gmail.com
SMTP_PASSWORD=abcd efgh ijkl mnop
SMTP_FROM_EMAIL=your.email@gmail.com
SMTP_FROM_NAME="DailyCup Coffee Shop"
SMTP_ENCRYPTION=tls
APP_URL=http://localhost:3000
```

⚠️ **Important:**
- `SMTP_ENABLED` = `true` (not false!)
- `SMTP_USERNAME` = Your full Gmail address (e.g., `john.doe@gmail.com`)
- `SMTP_PASSWORD` = The 16-character app password (with or without spaces)
- `SMTP_FROM_EMAIL` = Your Gmail address or noreply email

### 3c. Save the file
- Press **Ctrl+S**

✅ **.env configured**

---

## Step 4: Run Test Script

**Duration:** ~5 minutes

### 4a. Open Terminal/PowerShell:
```bash
cd c:\laragon\www\DailyCup\webapp\backend\tests
```

### 4b. Run the test script:
```bash
php test_email.php
```

### 4c. Expected Output:
```
=== DailyCup Email Test ===

1. Testing Order Confirmation Email...
   ✅ Order confirmation sent!

2. Testing Payment Confirmation Email...
   ✅ Payment confirmation sent!

3. Testing Status Update Email...
   ✅ Status update sent!

4. Testing Welcome Email...
   ✅ Welcome email sent!

=== Test Complete ===

Note: Check your email at: test@example.com
...
```

### 4d. If you see ✅ for all tests:
**Great!** Emails are configured correctly. Go to **Step 5**.

### 4e. If you see ❌ errors:
- Go to **Troubleshooting** section
- Check error message
- Fix the issue

✅ **Test script executed**

---

## Step 5: Check Email Results

**Duration:** ~2 minutes

### 5a. Check your Gmail inbox:

1. **Open Gmail:** https://mail.google.com
2. **Look for emails from:** `DailyCup Coffee Shop <your.email@gmail.com>`
3. **Check these emails:**
   - Order Confirmation (subject: "Order Confirmation - ORD-TEST-...")
   - Payment Confirmation (subject: "Payment Received - ORD-TEST-...")
   - Order Status Update (subject: "Order Update - ORD-TEST-...")
   - Welcome Email (subject: "Welcome to DailyCup! ☕")

### 5b. Email received? ✅
- Emails appear in inbox
- All 4 test emails are there
- HTML formatting looks good
- Links are clickable

### 5c. Emails NOT received? ⚠️
- **Check Spam/Promotions folder**
- **Check Trash folder**
- If not there, go to **Troubleshooting**

✅ **Emails verified**

---

## Testing with Real Order Flow

### Test Order Creation with Email:

**1. Create an order via API:**
```bash
curl -X POST http://localhost/DailyCup/backend/api/create_order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "id": "1",
        "name": "Espresso",
        "price": 25000,
        "quantity": 1
      }
    ],
    "total": 25000,
    "subtotal": 25000,
    "discount": 0,
    "customer": {
      "name": "Test User",
      "email": "your.email@gmail.com",
      "phone": "081234567890",
      "address": "Jl. Test"
    },
    "paymentMethod": "midtrans",
    "deliveryMethod": "takeaway"
  }'
```

**2. Check email inbox:**
- Order confirmation should arrive within seconds
- Contains order details and items

**3. Simulate payment:**
```bash
curl -X POST http://localhost/DailyCup/backend/api/pay_order.php \
  -H "Content-Type: application/json" \
  -d '{
    "orderId": "ORD-1234567890-1234",
    "action": "paid"
  }'
```

**4. Check email inbox again:**
- Payment confirmation should arrive
- Shows total amount paid

---

## Troubleshooting

### ❌ Problem: "Failed to send email"

**Solution 1: Check SMTP_ENABLED**
```env
# Make sure it's set to true, not false
SMTP_ENABLED=true
```

**Solution 2: Check Gmail App Password**
- Copy password again from https://myaccount.google.com/apppasswords
- Make sure there are no typos
- Try removing spaces from password

**Solution 3: Check Gmail 2-Step Verification**
- Go to https://myaccount.google.com/security
- Verify "2-Step Verification" shows "On"
- If not, enable it again

### ❌ Problem: "Connection timeout" or "SMTP error"

**Solution 1: Check firewall**
- Port 587 might be blocked
- Try adding firewall exception for SMTP
- Or ask your IT admin

**Solution 2: Try different SMTP port**
```env
# Try port 465 (SSL instead of TLS)
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
```

### ❌ Problem: Emails in spam folder

**Solution 1: Mark as "Not Spam"**
- Open email in Gmail
- Click "Report spam" menu
- Select "It's not spam"

**Solution 2: Use verified domain email**
- In production, use business email instead of Gmail
- Or use service like SendGrid

### ❌ Problem: "Invalid signature" error

**Solution:**
- Check `SMTP_PASSWORD` is correct
- No extra spaces before/after
- Regenerate app password if needed

### ❌ Problem: Error in PHP error log

**Check logs:**
```bash
# On Windows, logs are usually in:
# c:\laragon\logs\php\
# Or check error_log in .env
```

---

## Quick Checklist ✅

- [ ] 2-Step Verification enabled on Gmail
- [ ] App Password generated (16 characters)
- [ ] `.env` updated with correct credentials
- [ ] `SMTP_ENABLED=true`
- [ ] Test script ran successfully
- [ ] 4 test emails received in inbox
- [ ] Emails show correct HTML formatting
- [ ] Ready for production!

---

## After Successful Testing

### Next Steps:

1. **Test with real orders:**
   - Create an order via frontend
   - Verify order confirmation email arrives
   - Process payment
   - Verify payment confirmation email arrives

2. **Test admin status updates:**
   - Update order status in admin panel
   - Verify status update email arrives

3. **Monitor email delivery:**
   - Check Gmail for bounce/delivery failures
   - Monitor for spam complaints
   - Track delivery rates

4. **Production Setup:**
   - Consider using SendGrid or AWS SES
   - Set up email queue system
   - Add bounce handling
   - Monitor deliverability

---

## Support

**If something doesn't work:**

1. Check error message carefully
2. Check `.env` file for typos
3. Check Gmail settings
4. Review troubleshooting section
5. Check PHP error logs

**Files to check:**
- `.env` - Configuration
- `test_email.php` - Test script
- `EmailService.php` - Main class
- `error_log` - Error messages

Good luck! 🚀
