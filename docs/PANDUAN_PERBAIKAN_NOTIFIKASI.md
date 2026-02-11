# 🔔 PANDUAN LENGKAP PERBAIKAN SISTEM NOTIFIKASI

## 📋 DAFTAR ISI
1. [Masalah Yang Ditemukan](#masalah-yang-ditemukan)
2. [Penyebab Masalah](#penyebab-masalah)
3. [Solusi Yang Diterapkan](#solusi-yang-diterapkan)
4. [Cara Testing Notifikasi](#cara-testing-notifikasi)
5. [Troubleshooting](#troubleshooting)

---

## ❌ MASALAH YANG DITEMUKAN

### Gejala:
- Halaman notifikasi selalu menampilkan "Tidak ada notifikasi"
- Tidak ada notifikasi yang muncul meskipun admin mengubah status pesanan
- Badge notifikasi tidak bertambah
- Fungsi notifikasi tidak berjalan sama sekali

### Status Sebelum Perbaikan:
✅ Tabel `notifications` sudah ada di database  
✅ Fungsi `createNotification()` sudah tersedia  
❌ **TIDAK ADA kode yang memanggil fungsi `createNotification()`**  

---

## 🔍 PENYEBAB MASALAH

**ROOT CAUSE:** Sistem notifikasi tidak pernah dipanggil!

Walaupun infrastruktur notifikasi (database, API, tampilan) sudah lengkap, tetapi **trigger untuk membuat notifikasi tidak ada**. Jadi saat admin mengubah status pesanan atau ada aktivitas lain, sistem tidak membuat entry baru di tabel notifications.

**Analogi:** Seperti punya speaker bagus tapi tidak ada yang menekan tombol play!

---

## ✅ SOLUSI YANG DITERAPKAN

### 1️⃣ **Notifikasi Update Status Pesanan**
**File:** `admin/orders/view.php`

**Yang Ditambahkan:**
```php
// CREATE NOTIFICATION for customer when status changes
if ($oldStatus !== $newStatus) {
    $statusLabel = ORDER_STATUS[$newStatus];
    $notifTitle = "Status Pesanan Diperbarui";
    $notifMessage = "Pesanan #{$order['order_number']} telah diperbarui menjadi: {$statusLabel}";
    $notifType = 'order_update';
    
    createNotification($order['user_id'], $notifTitle, $notifMessage, $notifType, $orderId);
}
```

**Kapan Dipanggil:** Setiap kali admin mengubah status pesanan di halaman order detail

**Jenis Notifikasi:**
- Status berubah dari "pending" → "confirmed" ✅
- Status berubah dari "confirmed" → "processing" ✅
- Status berubah dari "processing" → "ready" ✅
- Status berubah dari "ready" → "delivering" ✅
- Status berubah dari "delivering" → "completed" ✅
- Dan semua perubahan status lainnya ✅

---

### 2️⃣ **Notifikasi Pesanan Baru**
**File:** `customer/payment.php`

**Yang Ditambahkan:**
```php
// CREATE NOTIFICATION for customer - Order Created
createNotification(
    $userId, 
    "Pesanan Berhasil Dibuat", 
    "Pesanan #{$orderNumber} telah berhasil dibuat. Total pembayaran: " . formatCurrency($finalAmount), 
    'order_created', 
    $orderId
);
```

**Kapan Dipanggil:** Setiap kali customer berhasil membuat pesanan baru

**Benefit:** Customer langsung mendapat konfirmasi bahwa pesanan telah diterima sistem

---

### 3️⃣ **Notifikasi Loyalty Points**
**File:** `includes/functions.php` - Fungsi `updateUserPoints()`

**Yang Ditambahkan:**
```php
// CREATE NOTIFICATION for points update
if ($type === 'earned' && $points > 0) {
    createNotification(
        $userId,
        "Poin Loyalty Bertambah!",
        "Selamat! Anda mendapatkan {$points} poin loyalty. {$description}",
        'loyalty_earned',
        $orderId
    );
} elseif ($type === 'redeemed' && $points < 0) {
    createNotification(
        $userId,
        "Poin Loyalty Digunakan",
        "Anda telah menggunakan " . abs($points) . " poin loyalty. {$description}",
        'loyalty_used',
        $orderId
    );
}
```

**Kapan Dipanggil:** 
- Saat customer mendapat poin dari order completed ✅
- Saat customer menggunakan poin untuk redeem rewards ✅

---

## 🧪 CARA TESTING NOTIFIKASI

### Test 1: Notifikasi Order Baru
**Langkah:**
1. Login sebagai customer
2. Tambahkan produk ke cart
3. Checkout dan buat pesanan baru
4. Buka halaman Notifikasi
5. **Expected:** Muncul notifikasi "Pesanan Berhasil Dibuat"

---

### Test 2: Notifikasi Update Status Order
**Langkah:**
1. Login sebagai customer dan buat 1 pesanan (catat order number)
2. Logout, lalu login sebagai admin
3. Buka menu Orders → View detail pesanan yang baru dibuat
4. Ubah status dari "pending" ke "confirmed", klik "Simpan Perubahan"
5. Logout admin, login kembali sebagai customer
6. Buka halaman Notifikasi
7. **Expected:** Muncul notifikasi "Status Pesanan Diperbarui"

---

### Test 3: Notifikasi Badge Count
**Langkah:**
1. Setelah melakukan Test 1 dan Test 2 di atas
2. Perhatikan icon notifikasi di navbar (icon lonceng)
3. **Expected:** Ada badge merah dengan angka 2 (atau lebih jika ada notifikasi lain)
4. Klik icon notifikasi
5. Klik "Tandai Semua Dibaca"
6. **Expected:** Badge merah hilang

---

### Test 4: Auto-Refresh Notifikasi
**Langkah:**
1. Buka 2 browser/tab berbeda:
   - Browser 1: Login sebagai customer, buka halaman Notifikasi
   - Browser 2: Login sebagai admin
2. Di Browser 2 (admin): Ubah status order milik customer tersebut
3. Di Browser 1 (customer): Tunggu maksimal 30 detik (auto-refresh)
4. **Expected:** Notifikasi baru muncul otomatis tanpa refresh manual

---

## 🔧 TROUBLESHOOTING

### Problem 1: Notifikasi Masih Tidak Muncul

**Kemungkinan Penyebab:**
1. Database tidak ter-update dengan struktur terbaru

**Solusi:**
```sql
-- Cek apakah tabel notifications ada
SHOW TABLES LIKE 'notifications';

-- Jika tidak ada, import ulang database
SOURCE c:/laragon/www/DailyCup/database/dailycup_db.sql;
```

---

### Problem 2: Error "Call to undefined function createNotification()"

**Kemungkinan Penyebab:**
- File functions.php tidak di-include dengan benar

**Solusi:**
Pastikan di awal file ada:
```php
require_once __DIR__ . '/../includes/functions.php';
```

---

### Problem 3: Notifikasi Dibuat Tapi Tidak Muncul

**Kemungkinan Penyebab:**
1. JavaScript error di console browser
2. API endpoint notifications.php error

**Solusi:**
1. Buka browser DevTools (F12) → Console tab
2. Cek apakah ada error JavaScript
3. Buka tab Network → Refresh halaman
4. Cek API call ke `/api/notifications.php?action=get`
5. Pastikan response sukses dan ada data notifications

**Debug Query Manual:**
```sql
-- Cek isi tabel notifications
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;

-- Cek notifikasi untuk user tertentu
SELECT * FROM notifications WHERE user_id = 1;
```

---

### Problem 4: Badge Count Tidak Update

**Kemungkinan Penyebab:**
- JavaScript tidak berjalan dengan baik

**Solusi:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Hard refresh halaman (Ctrl+F5)
3. Cek file `/assets/js/notification.js` sudah ter-load
4. Pastikan `NOTIFICATION_CHECK_INTERVAL` berjalan setiap 30 detik

---

### Problem 5: Testing di 1 Device Tidak Kelihatan Real-time

**PERHATIAN PENTING:**
Jika testing menggunakan **1 laptop/device yang sama** (bukan 2 device berbeda):

**Cara Testing Yang Benar:**
1. Gunakan 2 browser berbeda (Chrome + Firefox)
2. ATAU gunakan 1 browser normal + 1 incognito/private window
3. ATAU gunakan profile browser berbeda

**Mengapa?** 
Session PHP di-share dalam 1 browser yang sama. Jadi jika Anda logout dari customer dan login sebagai admin di tab berbeda, session customer hilang.

**Workaround:**
- Login sebagai customer di Chrome
- Login sebagai admin di Firefox
- Ubah order di Firefox (admin)
- Lihat notifikasi di Chrome (customer) - tunggu 30 detik untuk auto-refresh

---

## 📊 RINGKASAN PERUBAHAN FILE

| File | Perubahan | Fungsi |
|------|-----------|--------|
| `admin/orders/view.php` | ✏️ Modified | Tambah trigger notifikasi saat update status |
| `customer/payment.php` | ✏️ Modified | Tambah notifikasi saat order created |
| `includes/functions.php` | ✏️ Modified | Tambah notifikasi di fungsi updateUserPoints |

---

## 🎯 KESIMPULAN

### Sebelum Perbaikan:
❌ Notifikasi tidak pernah dibuat  
❌ Tidak ada trigger untuk createNotification()  
❌ Tampilan selalu "Tidak ada notifikasi"  

### Setelah Perbaikan:
✅ Notifikasi dibuat saat order baru  
✅ Notifikasi dibuat saat update status order  
✅ Notifikasi dibuat saat loyalty points berubah  
✅ Auto-refresh setiap 30 detik  
✅ Badge count update otomatis  
✅ Mark as read berfungsi  

---

## 📝 CATATAN TAMBAHAN

### Fitur Notifikasi Yang Sudah Berfungsi:
1. ✅ Notifikasi order created
2. ✅ Notifikasi order status update
3. ✅ Notifikasi loyalty points earned
4. ✅ Notifikasi loyalty points used
5. ✅ Auto-refresh setiap 30 detik
6. ✅ Badge count unread notifications
7. ✅ Mark single notification as read
8. ✅ Mark all notifications as read
9. ✅ Notification dropdown di navbar
10. ✅ Full notification page

### Notifikasi Yang Bisa Ditambahkan Nanti (Opsional):
- Notifikasi saat ada promo/diskon baru
- Notifikasi saat payment berhasil di-confirm
- Notifikasi reminder untuk order yang belum dibayar
- Notifikasi birthday/anniversary reward

---

**Dokumen dibuat:** 7 Januari 2026  
**Versi:** 1.0  
**Status:** ✅ Implementasi Selesai
