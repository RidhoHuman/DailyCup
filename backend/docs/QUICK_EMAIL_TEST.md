# Quick Start Email Testing - Terminal Guide

## 🚀 Langkah 1: Setup Gmail (5 menit)

### A. Buka Gmail Account Settings:
```
https://myaccount.google.com/
```

### B. Klik "Security" di menu kiri

### C. Cari "2-Step Verification":
- Jika belum enabled → Click "Get Started" → ikuti instruksi
- Jika sudah → lanjut ke D

### D. Klik "App passwords" (muncul setelah 2-Step aktif):
- Pilih "Mail"
- Pilih "Windows Computer"
- Click "Generate"
- **COPY password yang ditampilkan** (16 karakter dengan spasi)

---

## ⚙️ Langkah 2: Update .env (2 menit)

### A. Buka file .env:
```
c:\laragon\www\DailyCup\webapp\backend\api\.env
```

### B. Cari section SMTP dan update:

```env
SMTP_ENABLED=true
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your.email@gmail.com
SMTP_PASSWORD=xxxx xxxx xxxx xxxx
SMTP_FROM_EMAIL=your.email@gmail.com
SMTP_FROM_NAME="DailyCup Coffee Shop"
SMTP_ENCRYPTION=tls
APP_URL=http://localhost:3000
```

⚠️ **PENTING:**
- `SMTP_ENABLED` harus `true` (tidak `false`)
- `SMTP_USERNAME` = Gmail address mu (ex: john.doe@gmail.com)
- `SMTP_PASSWORD` = App password dari Step 1 (boleh dengan/tanpa spasi)
- `SMTP_FROM_EMAIL` = Gmail address mu

### C. Save file (Ctrl+S)

---

## 🧪 Langkah 3: Run Test (1 menit)

### CLI Test (Recommended - Paling Simple):

```bash
cd c:\laragon\www\DailyCup\webapp\backend\tests
php test_email.php
```

**Expected Output:**
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
```

✅ = Berhasil
❌ = Ada error (lihat Troubleshooting)

---

## 📧 Langkah 4: Check Email Inbox (1 menit)

1. **Buka Gmail:** https://mail.google.com
2. **Cari emails dari:** "DailyCup Coffee Shop"
3. **Verifikasi:**
   - [ ] Order Confirmation
   - [ ] Payment Confirmation
   - [ ] Status Update
   - [ ] Welcome Email

Jika tidak ada di inbox:
- ✅ Check **Spam** folder
- ✅ Check **Promotions** folder
- ✅ Lihat Troubleshooting di bawah

---

## 🎉 Success Checklist

- [ ] SMTP_ENABLED=true di .env
- [ ] Gmail App Password di SMTP_PASSWORD
- [ ] test_email.php shows ✅ untuk semua tests
- [ ] 4 test emails terima di inbox/spam
- [ ] HTML formatting terlihat bagus

Kalau semua ✅ → **Email system ready untuk production!**

---

## ❌ Troubleshooting

### Problem: "Failed to send email"

**Check 1: SMTP_ENABLED**
```env
# Pastikan ada di .env:
SMTP_ENABLED=true
```

**Check 2: App Password**
- Buka https://myaccount.google.com/apppasswords
- Regenerate password baru
- Copy lagi ke .env

**Check 3: 2-Step Verification**
- Pastikan di https://myaccount.google.com/security
- "2-Step Verification" harus "On"

### Problem: "Connection timeout"

Try different port:
```env
# Instead of:
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# Try:
SMTP_PORT=465
SMTP_ENCRYPTION=ssl
```

### Problem: Emails di Spam Folder

**Solution 1:** Mark as not spam di Gmail
- Buka email di Gmail
- Menu → "Report spam" → "It's not spam"

**Solution 2:** Gunakan business email di production

### Problem: "Authentication failed"

- Copy app password lagi (jangan ada typo)
- Coba hapus spasi dari password
- Regenerate app password baru

### Problem: PHP Error Log

**Check error log:**
```bash
# Buka error.log:
c:\laragon\logs\php\
```

---

## 📝 Testing dengan Real Order

Setelah test_email.php berhasil, test dengan real flow:

### Test 1: Create Order
```bash
curl -X POST http://localhost/DailyCup/backend/api/create_order.php \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"id":"1","name":"Espresso","price":25000,"quantity":1}],
    "total": 25000,
    "subtotal": 25000,
    "discount": 0,
    "customer": {
      "name": "Test",
      "email": "your.email@gmail.com",
      "phone": "08123",
      "address": "Test Addr"
    },
    "paymentMethod": "manual",
    "deliveryMethod": "takeaway"
  }'
```

Check email → Should receive **Order Confirmation**

### Test 2: Confirm Payment
```bash
curl -X POST http://localhost/DailyCup/backend/api/pay_order.php \
  -H "Content-Type: application/json" \
  -d '{
    "orderId": "ORD-XXXXXXX-XXXX",
    "action": "paid"
  }'
```

Check email → Should receive **Payment Confirmation**

---

## 🎯 Quick Decision Tree

```
Email tidak masuk?
├─ Ada di Spam? → Mark as not spam
├─ Check SMTP_ENABLED=true? → Update .env
├─ Check App Password? → Regenerate baru
├─ Check 2-Step? → Enable kalau belum
└─ Still error? → Check error logs
```

---

## 📞 Support

**Files to check if error:**
- `.env` - Configuration
- `test_email.php` - Test script
- `error.log` - Error messages

**Success indicators:**
- ✅ test_email.php shows all green
- ✅ 4 emails in Gmail inbox
- ✅ HTML template terlihat professional

Good luck! 🚀
