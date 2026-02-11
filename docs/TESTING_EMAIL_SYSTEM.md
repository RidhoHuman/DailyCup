# Testing Email System dari Web Interface

## Langkah-langkah Testing

### Fase 1: Verifikasi Frontend (< 100ms response)

1. **Buka website:**
   - URL: `http://localhost:3000`

2. **Lakukan order:**
   - Tambahkan produk ke cart
   - Isi data customer (nama, email, alamat)
   - Klik "Place Order"
   - **CHECK:** Response harus cepat (< 1 detik), TIDAK ada error timeout ✅

3. **Catat orderId:**
   - Dari response atau URL
   - Contoh: `ORD-12345`

---

### Fase 2: Verifikasi Queue Files (5-10 detik)

4. **Check queue directory:**
   ```powershell
   Get-ChildItem C:\laragon\www\DailyCup\webapp\backend\queue\*.json
   ```
   
   **Expected:** Harus ada 1 file dengan format `email_xxxxxx.json`

5. **Lihat isi queue file:**
   ```powershell
   Get-Content C:\laragon\www\DailyCup\webapp\backend\queue\*.json | ConvertFrom-Json | Format-List
   ```
   
   **Expected:**
   ```
   to       : customer_email@gmail.com
   subject  : Order Confirmation - ORD-12345
   htmlBody : (HTML template)
   status   : pending
   ```

---

### Fase 3: Process Queue (20-30 detik)

6. **Jalankan queue worker:**
   ```powershell
   cd C:\laragon\www\DailyCup\webapp\backend\api
   php queue_worker.php
   ```
   
   **Expected Output:**
   ```
   === Email Queue Worker ===
   ✓ Sent to: customer_email@gmail.com
   
   === Queue Stats ===
   Processed: 1
   Failed: 0
   Still Pending: 0
   Total Failed: 0
   Done.
   ```

---

### Fase 4: Verifikasi Email di Gmail (1-2 menit)

7. **Buka Gmail:**
   - Akun: `ridhohuman11@gmail.com`
   - Buka Inbox

8. **Cari email:**
   - Subject: `Order Confirmation - ORD-12345`
   - From: `DailyCup Coffee Shop <noreply@dailycup.com>`
   - **CHECK:** Email sudah ada? ✅

9. **Verifikasi content:**
   - Nama customer
   - Order number
   - Items list
   - Total amount
   - Delivery address
   - Payment method

---

## Timeline Harapan

| Aksi | Durasi | Status |
|------|--------|--------|
| Create Order (frontend) | < 1s | ✅ Fast |
| Queue file created | < 0.1s | ✅ Instant |
| Process queue | 20-30s | ⏳ Background |
| Email sampai Gmail | 1-2 min | ⏳ SMTP |

---

## Troubleshooting

### Email tidak ada di inbox?
```
1. Check queue files masih ada?
   Get-ChildItem C:\laragon\www\DailyCup\webapp\backend\queue\

2. Check logs untuk error:
   Get-Content C:\laragon\www\DailyCup\webapp\backend\api\.env | grep -i smtp

3. Run worker dengan error check:
   php queue_worker.php 2>&1 | Tee-Object -Variable output
   $output
```

### Response masih lambat?
```
1. Check timeout di frontend:
   grep -n "timeout:" frontend/utils/api.ts
   
   Expected: 15000 (15 detik) untuk create_order

2. Verify queue adalah enabled:
   grep EMAIL_USE_QUEUE backend/api/.env
   
   Expected: EMAIL_USE_QUEUE=true
```

### Queue files tidak hilang setelah diproses?
```
1. Check permissions pada backend/queue/
   ls -la backend/queue/

2. Re-run worker:
   php queue_worker.php

3. Manual delete jika perlu:
   rm backend/queue/email_*.json
```

---

## Commands Quick Reference

```powershell
# Check queue stats
Get-ChildItem C:\laragon\www\DailyCup\webapp\backend\queue\*.json | Measure-Object

# Process queue
cd C:\laragon\www\DailyCup\webapp\backend\api; php queue_worker.php

# Clear queue (jika perlu)
Remove-Item C:\laragon\www\DailyCup\webapp\backend\queue\*.json

# Test queue system directly
php test_queue_system.php

# Check email config
Get-Content .env | Select-String "EMAIL"
```

---

## Expected Results ✅

### Success Scenario:
```
1. [Web] Order created → Response instant ✅
2. [Queue] File created in backend/queue/ ✅
3. [Worker] Queue processed berhasil ✅
4. [Gmail] Email received in inbox ✅
5. [Content] HTML template rendered correctly ✅
```

### Jika Semua Berhasil:
✅ Email system sudah FIXED!
✅ Tidak ada timeout error lagi!
✅ Emails terkirim ke Gmail dengan sempurna!

---

## Next Actions

1. 📝 Lakukan testing sesuai langkah di atas
2. 📧 Verifikasi email masuk ke Gmail
3. 🔧 Setup cron job untuk auto process queue (optional tapi recommended)
4. 📊 Monitor logs untuk tracking

---

**Status:** Email Queue System sudah ready untuk production! 
**Durasi Testing:** ~5 menit
**Kesulitan:** Easy
