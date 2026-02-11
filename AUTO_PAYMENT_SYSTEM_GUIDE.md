# 🚀 AUTO PAYMENT STATUS SYSTEM - IMPLEMENTATION GUIDE

## ✅ YANG SUDAH DIIMPLEMENTASI

### 1. Backend Auto-Update Logic (`orders.php`)

**Rule 1: Cash Payment + Order Completed = Auto Paid** ✅
```
Ketika admin update order status:
- Status = "Completed" + Payment Method = "Cash"
  → Payment Status otomatis jadi "Paid"
  
Reason: Cash payment sudah diterima langsung di POS
```

**Rule 2: Order Cancelled = Auto Failed** ✅
```
Ketika admin update order status:
- Status = "Cancelled"
  → Payment Status otomatis jadi "Failed"
  
Reason: Order dibatalkan, payment tidak jadi
```

**Rule 3: Transfer/QRIS = Xendit Webhook** ⏳ (Setup diperlukan)
```
Ketika customer bayar via Xendit:
- Xendit kirim webhook ke backend
- Backend terima notifikasi payment success
- Payment Status otomatis jadi "Paid"
  
Reason: Verifikasi payment dari Xendit (secure & official)
```

---

## 📋 CARA KERJA SISTEM

### Flow Diagram:

```
┌─────────────────────────────────────────────────────────────┐
│                    ORDER CREATION                            │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
                 ┌────────────────────────┐
                 │   Payment Method?      │
                 └────────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         │                    │                    │
         ▼                    ▼                    ▼
    ┌────────┐          ┌──────────┐        ┌──────────┐
    │  CASH  │          │ TRANSFER │        │   QRIS   │
    │  (POS) │          │ (Xendit) │        │ (Xendit) │
    └────────┘          └──────────┘        └──────────┘
         │                    │                    │
         ▼                    ▼                    ▼
 Payment Status:      Payment Status:      Payment Status:
   "pending"             "pending"            "pending"
         │                    │                    │
         │                    │                    │
    Admin update         Customer bayar       Customer scan QRIS
  Order → Completed      via Xendit          & bayar
         │                    │                    │
         ▼                    ▼                    ▼
    🤖 AUTO               🌐 Xendit            🌐 Xendit
   UPDATE TO           sends webhook       sends webhook
     "paid"                  │                    │
                             ▼                    ▼
                        🤖 AUTO              🤖 AUTO
                       UPDATE TO            UPDATE TO
                         "paid"              "paid"
```

---

## 🔧 XENDIT + NGROK SETUP (Untuk Development)

### Step 1: Install & Setup ngrok

```bash
# Download ngrok dari https://ngrok.com/download
# atau via winget (Windows)
winget install ngrok

# Login ke ngrok (daftar gratis dulu di ngrok.com)
ngrok config add-authtoken YOUR_AUTHTOKEN_HERE

# Jalankan ngrok untuk expose localhost
ngrok http 80
```

**Output ngrok:**
```
Session Status                online
Account                       Your Name (Plan: Free)
Forwarding                    https://abc123.ngrok-free.app -> http://localhost:80
```

✅ **Copy URL ini:** `https://abc123.ngrok-free.app`

---

### Step 2: Konfigurasi Xendit Dashboard

1. **Login ke Xendit Dashboard**: https://dashboard.xendit.co/

2. **Buka Settings → Webhooks**

3. **Add Webhook URL:**
   ```
   https://abc123.ngrok-free.app/DailyCup/webapp/backend/api/xendit_webhook.php
   ```

4. **Pilih Events yang mau di-subscribe:**
   - ✅ Invoice Paid
   - ✅ Payment Completed
   - ✅ Payment Failed
   - ✅ QR Code Paid

5. **Copy Webhook Verification Token** (untuk security)

6. **Test Webhook** (Xendit ada button "Send Test")

---

### Step 3: Environment Variables

Edit file `.env` di root project:
```bash
# Xendit Configuration
XENDIT_API_KEY=xnd_development_xxx...
XENDIT_WEBHOOK_TOKEN=your_webhook_verification_token_from_dashboard
ENVIRONMENT=development  # Set 'production' for live server
```

---

### Step 4: Test Payment Flow

#### Test Case 1: Cash Payment (POS)
```
1. Buat order baru via POS dengan payment method = "Cash"
2. Admin buka Order Management
3. Update order status dari "Pending" → "Completed"
4. ✅ Otomatis payment status jadi "Paid"
5. ✅ Muncul alert: "Payment auto-updated to PAID (Cash payment)"
```

#### Test Case 2: Xendit QRIS
```
1. Customer checkout di frontend, pilih QRIS
2. Frontend call Xendit API, dapat QR code
3. Customer scan & bayar via aplikasi bank
4. Xendit kirim webhook ke:
   https://abc123.ngrok-free.app/.../xendit_webhook.php
5. ✅ Backend terima webhook, auto-update payment_status = "paid"
6. ✅ Admin lihat di Order Management, status sudah "Paid"
```

#### Test Case 3: Order Cancelled
```
1. Buka Order Management, pilih order apapun
2. Update order status ke "Cancelled"
3. ✅ Otomatis payment status jadi "Failed"
```

---

## 📝 WEBHOOK LOG & DEBUGGING

### Cek Log Webhook
```php
// File: webapp/backend/logs/xendit_webhooks.log

[2026-02-07 15:30:45] Webhook received:
{
  "id": "invoice_xxx",
  "external_id": "ORD-1770446347-8417",
  "status": "PAID",
  "paid_amount": 50000,
  "payment_method": "QRIS"
}
---
```

### Test Webhook Manual (Postman/curl)
```bash
curl -X POST http://localhost/DailyCup/webapp/backend/api/xendit_webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Callback-Token: your_webhook_token" \
  -d '{
    "external_id": "ORD-1770446347-8417",
    "status": "PAID",
    "paid_amount": 50000
  }'
```

---

## 🌐 NGROK TIPS & TRICKS

### Keuntungan Pakai ngrok:
- ✅ Tidak perlu deploy ke server hosting untuk test
- ✅ HTTPS gratis (Xendit butuh HTTPS)
- ✅ URL public, bisa di-test dari HP/device lain
- ✅ Inspect webhooks real-time di ngrok dashboard

### Kekurangan ngrok Free:
- ⚠️ URL berubah tiap restart (harus update Xendit webhook URL lagi)
- ⚠️ Session timeout setelah 2 jam (untuk free plan)
- ⚠️ Rate limit request

### Solusi URL Berubah:
**Option 1:** Upgrade ke ngrok Paid Plan ($8/month)
- Fixed URL: `https://dailycup.ngrok.app` (tidak berubah)

**Option 2:** Pakai Xendit Test Mode
- Tidak perlu webhook untuk test
- Simulate payment via Xendit dashboard

**Option 3:** Deploy ke Server yang punya IP Public
- VPS (Digital Ocean, AWS, etc.)
- atau Shared Hosting dengan domain

---

## 🔐 SECURITY CHECKLIST

✅ **1. Verify Webhook Signature**
```php
// Sudah dihandle di xendit_webhook.php
$receivedToken = $_SERVER['HTTP_X_CALLBACK_TOKEN'];
if ($receivedToken !== $xenditWebhookToken) {
    die('Invalid token');
}
```

✅ **2. Validate Payment Amount**
```php
if ($paidAmount < $order['final_amount']) {
    // Reject or flag suspicious payment
}
```

✅ **3. Prevent Replay Attacks**
- Log semua webhook ID
- Cek duplicate webhook (Xendit bisa kirim multiple times)

✅ **4. Use HTTPS (Production)**
- Let's Encrypt SSL (gratis)
- Atau SSL dari hosting provider

---

## 📊 MONITORING & DASHBOARD

### Admin Dashboard Indicators
Di Order Management modal, sekarang ada indicator:

**Cash Orders:**
```
Payment Status: [Paid ▼]
⚡ Auto-managed
ℹ️ Auto-updated to Paid when order is Completed
```

**Transfer/QRIS Orders:**
```
Payment Status: [Pending ▼]
🛡️ Xendit webhook will auto-update upon payment confirmation
```

---

## 🐛 TROUBLESHOOTING

### Problem 1: Webhook Tidak Diterima
```
Cek:
1. ngrok masih running? (ngrok http 80)
2. URL di Xendit dashboard sudah benar?
3. Firewall block ngrok?
4. Cek log: webapp/backend/logs/xendit_webhooks.log
```

### Problem 2: Payment Status Tidak Update
```
Cek:
1. Database query success? (cek error_log)
2. Order number match? (external_id = order_number)
3. Xendit kirim correct status? (cek webhook log)
```

### Problem 3: Cash Auto-Update Tidak Jalan
```
Cek:
1. Payment method = "cash" (lowercase)?
2. Status update ke "completed" (lowercase)?
3. Cek browser alert muncul?
4. Refresh page setelah update
```

---

## 📞 NEXT STEPS

1. **Test semua flow** (cash, transfer, qris, cancelled)
2. **Setup ngrok** dan test webhook dengan Xendit test mode
3. **Monitor logs** untuk ensure webhook diterima
4. **Document** payment flow untuk user guide
5. **Prepare deployment** checklist (ngrok → production server)

---

## 🎯 SUMMARY

| Feature | Status | Notes |
|---------|--------|-------|
| Cash Auto-Paid | ✅ DONE | Completed order → Paid |
| Cancelled Auto-Failed | ✅ DONE | Cancelled order → Failed |
| Xendit Webhook Handler | ✅ DONE | File created, ready to test |
| Frontend Indicators | ✅ DONE | Auto-managed badges |
| ngrok Integration | 📝 DOCUMENTATION | Setup guide ready |
| Production Deployment | ⏳ PENDING | After testing phase |

**Status Keseluruhan: 90% COMPLETE** ✅

**Tinggal:**
- Setup ngrok + test webhook
- Integration test with Xendit
- Production deployment planning
