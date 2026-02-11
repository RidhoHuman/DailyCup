# Email Queue System - Panduan Solusi Timeout

## Masalah
Ketika mengirim email dari web interface, terjadi error `AbortError: signal is aborted without reason` karena timeout di frontend (3-5 detik) padahal proses email membutuhkan 5-10 detik.

## Solusi: Email Queue (Async Email Sending)

Sistem email queue memisahkan proses email menjadi dua tahap:

### Tahap 1: Queuing (< 100ms)
```
User Order → API menambahkan email ke queue → Response langsung ke user ✅
```

### Tahap 2: Processing (Background)
```
Email Worker → Proses queue → Kirim ke Gmail SMTP → Update status
```

**Keuntungan:**
- ✅ Frontend response instant (< 100ms)
- ✅ Tidak ada timeout error
- ✅ Email tetap terkirim di background
- ✅ Retry otomatis jika gagal
- ✅ Log lengkap untuk tracking

---

## Cara Kerja

### Struktur Files:
```
backend/
├── api/
│   ├── email/
│   │   ├── EmailService.php       (Main service)
│   │   ├── EmailQueue.php         (Queue manager)
│   │   └── queue_worker.php       (Worker script)
│   ├── process_email_queue.php    (API endpoint)
│   └── create_order.php           (Menggunakan queue otomatis)
│
└── queue/                         (Directory untuk queue files)
    ├── email_xxx.json            (Email yang pending)
    └── email_yyy.json
```

### Alur:
1. **create_order.php** dipanggil dari frontend
2. EmailService.send() → Automatically queues ke `backend/queue/`
3. API respond **instantly** ke frontend (< 100ms)
4. Background worker memproses email dari queue

---

## Cara Menggunakan

### Option 1: Manual Queue Processing
```bash
# Run dari terminal (sekali-kali)
php backend/api/email/queue_worker.php
```

### Option 2: Cron Job (Recommended)
Setup cron job untuk menjalankan worker setiap beberapa menit:

```bash
# Jalankan setiap 5 menit
*/5 * * * * cd /path/to/webapp && php backend/api/email/queue_worker.php

# Atau via curl
*/5 * * * * curl http://localhost/DailyCup/webapp/backend/api/process_email_queue.php
```

### Option 3: API Call dari Frontend
```javascript
// Jalankan background processing via API
fetch('/DailyCup/webapp/backend/api/process_email_queue.php')
  .then(r => r.json())
  .then(data => console.log('Emails processed:', data.processed))
```

---

## Testing

### Test 1: Verify Queue Async
```bash
# Terminal 1: Trigger order creation (biasanya ~< 100ms)
curl -X POST http://localhost:3000/api/create_order \
  -H "Content-Type: application/json" \
  -d '{"total": 50000, "items": [...], "customer": {...}}'

# Terminal 2: Check queue files (should exist)
ls -la backend/queue/

# Terminal 3: Process queue
php backend/api/email/queue_worker.php
```

### Test 2: Check Queue Stats
```bash
# Create API endpoint untuk check queue:
curl http://localhost/DailyCup/webapp/backend/api/process_email_queue.php

# Output:
# {
#   "success": true,
#   "processed": 5,
#   "stats": {
#     "total": 8,
#     "pending": 2,
#     "failed": 1
#   }
# }
```

---

## Konfigurasi (.env)

```ini
# Enable/disable queue mode
EMAIL_USE_QUEUE=true          # Use async queue (default, recommended)
EMAIL_USE_QUEUE=false         # Send immediately (blocking, slow)
```

### Default Behavior:
- ✅ `EMAIL_USE_QUEUE=true` → Async (fast)
- ✅ Emails disimpan di `backend/queue/`
- ✅ Worker memproses via cron/manual
- ✅ Frontend response instant

---

## Troubleshooting

### Emails tidak terkirim?
```bash
# Check queue files exist
ls -la backend/queue/

# Check error logs
tail -f backend/logs/error.log

# Run worker with verbose output
php backend/api/email/queue_worker.php
```

### Disable queue (gunakan direct send):
```env
EMAIL_USE_QUEUE=false
```
⚠️ **Perhatian:** Akan lambat, bisa timeout!

### Hapus queue files (reset):
```bash
rm -rf backend/queue/*.json
```

---

## Monitoring

### Check queue status:
```bash
# Quick check
ls -1 backend/queue/*.json | wc -l

# Detailed stats
curl http://localhost/DailyCup/webapp/backend/api/process_email_queue.php | jq '.stats'
```

### Logs:
```bash
# Check PHP error log
tail -50 /var/log/php-errors.log

# Or via app logs
grep "Email" backend/logs/app.log
```

---

## FAQ

**Q: Berapa lama email sampai ke inbox?**
A: Tergantung frekuensi cron job. Dengan `*/5 * * * *`, maksimal 5 menit.

**Q: Bagaimana jika server mati saat worker jalan?**
A: Email tetap di queue, bisa diproses ulang saat server hidup kembali.

**Q: Bisa direct send tanpa queue?**
A: Ya, set `EMAIL_USE_QUEUE=false` di `.env` (tapi akan lambat & bisa timeout)

**Q: Queue files aman dimana?**
A: Di `backend/queue/` folder. Harus accessible oleh PHP & readable.

---

## Perubahan di Kode

### EmailService.php:
- Mendeteksi `EMAIL_USE_QUEUE` flag
- Jika true → simpan ke queue (< 100ms)
- Jika false → send langsung (5-10s)

### create_order.php & pay_order.php:
- Tidak berubah, tetap memanggil `EmailService::send()`
- Otomatis menggunakan queue

### Frontend timeout:
- Sudah dinaikkan dari 3s → 15s untuk `create_order`
- Sudah dinaikkan dari 3s → 15s untuk `pay_order`
- Plus async queue = tidak ada timeout lagi

---

## Next Steps

1. ✅ Timeout frontend sudah fixed (3s → 15s)
2. ✅ Email queue system sudah implemented
3. ⏳ Setup cron job untuk auto process emails
4. ⏳ Test dari web interface

---

## Setup Cron Job (Laragon/Windows)

Karena Windows tidak punya cron native, gunakan **Task Scheduler**:

### Via Command:
```powershell
# Buka Task Scheduler
taskkill /F /IM firefox.exe

# Atau gunakan Windows Task Scheduler GUI
# Create new task yang jalankan: php C:\laragon\www\DailyCup\webapp\backend\api\email\queue_worker.php
```

### Alternatif: Jalankan manual setelah setiap test
```powershell
# Terminal
cd C:\laragon\www\DailyCup\webapp
php backend\api\email\queue_worker.php
```

---

**Status:** ✅ Sistem ready! Tinggal setup cron job + test dari web interface.
