-- ============================================
-- SCRIPT TEST NOTIFIKASI DUMMY
-- Untuk testing sistem notifikasi
-- ============================================

-- CARA PAKAI:
-- 1. Buka phpMyAdmin atau MySQL client
-- 2. Pilih database dailycup_db
-- 3. Copy-paste script ini dan jalankan
-- 4. Login sebagai customer
-- 5. Buka halaman Notifikasi
-- 6. Seharusnya muncul beberapa notifikasi dummy

-- ============================================
-- CATATAN PENTING:
-- Ganti user_id = 1 dengan ID user yang sedang Anda gunakan untuk testing
-- ============================================

-- Cek dulu ID user yang ada
SELECT id, name, email, role FROM users WHERE role = 'customer' LIMIT 5;

-- Jika sudah tahu user_id, ganti angka 1 di bawah dengan user_id yang benar

-- ============================================
-- INSERT NOTIFIKASI DUMMY
-- ============================================

-- Notifikasi 1: Order Created
INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
VALUES (
    1,  -- GANTI dengan user_id Anda
    NULL,
    'Pesanan Berhasil Dibuat',
    'Pesanan #ORD2026010700001 telah berhasil dibuat. Total pembayaran: Rp 75.000',
    'order_created',
    0,
    NOW() - INTERVAL 5 MINUTE
);

-- Notifikasi 2: Order Status Update - Confirmed
INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
VALUES (
    1,  -- GANTI dengan user_id Anda
    NULL,
    'Status Pesanan Diperbarui',
    'Pesanan #ORD2026010700001 telah diperbarui menjadi: Confirmed - Pesanan Dikonfirmasi',
    'order_update',
    0,
    NOW() - INTERVAL 4 MINUTE
);

-- Notifikasi 3: Order Status Update - Processing
INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
VALUES (
    1,  -- GANTI dengan user_id Anda
    NULL,
    'Status Pesanan Diperbarui',
    'Pesanan #ORD2026010700001 telah diperbarui menjadi: Processing - Sedang Diproses',
    'order_update',
    0,
    NOW() - INTERVAL 3 MINUTE
);

-- Notifikasi 4: Loyalty Points Earned
INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
VALUES (
    1,  -- GANTI dengan user_id Anda
    NULL,
    'Poin Loyalty Bertambah!',
    'Selamat! Anda mendapatkan 75 poin loyalty. Dari pesanan #ORD2026010700001',
    'loyalty_earned',
    0,
    NOW() - INTERVAL 2 MINUTE
);

-- Notifikasi 5: Order Status Update - Completed
INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
VALUES (
    1,  -- GANTI dengan user_id Anda
    NULL,
    'Status Pesanan Diperbarui',
    'Pesanan #ORD2026010700001 telah diperbarui menjadi: Completed - Pesanan Selesai',
    'order_update',
    1,  -- Sudah dibaca
    NOW() - INTERVAL 1 MINUTE
);

-- Notifikasi 6: Order Created (Pesanan ke-2)
INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
VALUES (
    1,  -- GANTI dengan user_id Anda
    NULL,
    'Pesanan Berhasil Dibuat',
    'Pesanan #ORD2026010700002 telah berhasil dibuat. Total pembayaran: Rp 125.000',
    'order_created',
    0,
    NOW()
);

-- ============================================
-- VERIFIKASI
-- ============================================

-- Cek notifikasi yang baru dibuat
SELECT * FROM notifications WHERE user_id = 1 ORDER BY created_at DESC;

-- Hitung jumlah notifikasi yang belum dibaca
SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = 1 AND is_read = 0;

-- ============================================
-- CLEAN UP (Jika mau hapus notifikasi dummy)
-- ============================================

-- Uncomment line di bawah jika mau hapus semua notifikasi dummy
-- DELETE FROM notifications WHERE user_id = 1 AND order_id IS NULL;
