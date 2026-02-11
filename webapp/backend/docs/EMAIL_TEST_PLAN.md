# Email Testing Action Plan

## 📋 Your Testing Checklist (Copy & Follow)

### STEP 1: Gmail Setup ⏱️ ~5 minutes
- [ ] Go to myaccount.google.com
- [ ] Click Security
- [ ] Enable 2-Step Verification (if not done)
- [ ] Go to App passwords
- [ ] Generate new app password for Mail
- [ ] Copy the 16-character password

### STEP 2: Configure .env ⏱️ ~2 minutes
- [ ] Open: `backend/api/.env`
- [ ] Find SMTP section
- [ ] Set `SMTP_ENABLED=true`
- [ ] Set `SMTP_USERNAME=your.email@gmail.com`
- [ ] Set `SMTP_PASSWORD=xxxx xxxx xxxx xxxx` (app password)
- [ ] Save file

### STEP 3: Run Test ⏱️ ~1 minute
- [ ] Open PowerShell/Terminal
- [ ] Run: `cd c:\laragon\www\DailyCup\webapp\backend\tests`
- [ ] Run: `php test_email.php`
- [ ] Check for ✅ on all 4 tests

### STEP 4: Check Email ⏱️ ~1 minute
- [ ] Open Gmail
- [ ] Look for emails from "DailyCup Coffee Shop"
- [ ] Verify all 4 test emails arrived
- [ ] Check spam folder if not in inbox

### STEP 5: Test Real Order ⏱️ ~2 minutes
- [ ] Create order via frontend/API
- [ ] Check email for order confirmation
- [ ] Complete payment
- [ ] Check email for payment confirmation

---

## ✅ Success Criteria

Email system is ready when:
1. ✅ test_email.php shows all green (✅)
2. ✅ All 4 test emails in Gmail inbox
3. ✅ HTML templates look professional
4. ✅ Real order creates confirmation email
5. ✅ Real payment creates confirmation email

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `backend/api/.env` | SMTP Configuration |
| `backend/api/email/EmailService.php` | Main email service |
| `backend/templates/email/*.html` | Email templates |
| `backend/tests/test_email.php` | Simple CLI test |
| `backend/tests/email_checklist.php` | Interactive web test |
| `backend/docs/GMAIL_SETUP_GUIDE.md` | Detailed setup guide |
| `backend/docs/QUICK_EMAIL_TEST.md` | Quick reference |

---

## 🔧 Common Issues & Quick Fixes

| Issue | Fix |
|-------|-----|
| "Failed to send email" | Check `SMTP_ENABLED=true` in .env |
| "Connection timeout" | Try `SMTP_PORT=465` with `SMTP_ENCRYPTION=ssl` |
| Emails in spam | Mark as "Not spam" in Gmail |
| "Authentication failed" | Regenerate App Password, check for typos |
| PHP Error | Check `error_log` in logs folder |

---

## 🎯 Next Tasks (After Email Testing)

Once email system is verified:

### Immediate:
- [ ] Test with real orders from frontend
- [ ] Verify status update emails work
- [ ] Monitor Gmail delivery

### Phase 11 Remaining:
- [ ] Admin Analytics Dashboard (detailed charts)
- [ ] Inventory Management System

### Phase 12:
- [ ] Advanced Features (PWA, Push notifications)

---

## 💡 Tips

1. **Use Gmail for testing** - Easy to set up, good delivery
2. **Monitor spam folder** - Check if emails going there
3. **Keep credentials safe** - Don't commit .env to git
4. **Log everything** - Check error_log for debugging
5. **Test often** - Run tests after any changes

---

## 🚀 Ready to Start?

Follow the checklist above in order. Each step takes just a few minutes!

**Total time: ~10 minutes from start to verified email system**

Any questions? Check the detailed guides:
- `GMAIL_SETUP_GUIDE.md` - Step-by-step with screenshots
- `QUICK_EMAIL_TEST.md` - Quick reference with examples
- `EMAIL_SYSTEM.md` - Complete documentation
