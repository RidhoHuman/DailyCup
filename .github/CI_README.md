# CI README — DailyCup

Dokumentasi singkat untuk menjalankan dan men-debug workflow CI (GitHub Actions) secara lokal dan memahami artefak test.

## Tujuan ✅
- Menjalankan build Next.js dan Playwright E2E yang sama seperti di CI.
- Mempermudah repro langkah yang gagal dan mengumpulkan artefak (screenshot, video, trace).

---

## File workflow
- Lokasi: `.github/workflows/ci.yml`
- Ringkasan: checkout → install (`npm ci`) → build (`npm run build`) → start server (`npx next start -p 3001`) → install playwright browsers → run `npx playwright test` → upload artefak bila gagal.

---

## Cara menjalankan CI **lokal** (di mesin developer)
1. Buka terminal dan masuk ke folder frontend:

   cd frontend

2. Install dependency (fresh):

   npm ci

3. Build aplikasi (memastikan build produksi):

   npm run build

4. Jalankan server produksi pada port yang sama dengan CI (3001):

   npx next start -p 3001

   atau (dev):

   npm run dev -- -p 3001

5. Pastikan server responsive (di terminal lain):

   curl -I http://127.0.0.1:3001

6. Install browser Playwright (hanya pertama kali atau saat di CI):

   npx playwright install --with-deps

7. Jalankan Playwright test:

   npx playwright test --config=e2e/playwright.config.ts

8. Debug / lihat artefak bila tes gagal:

   - Screenshot & video tersimpan di folder `test-results/`
   - Trace: `npx playwright show-trace <path-to-trace.zip>`

---

## Tips & catatan (Windows)
- Jika Playwright mengalami masalah spawn Chrome/Chromium, gunakan flag peluncuran yang sudah ada di config: `--no-sandbox --disable-dev-shm-usage`.
- Jika server dev bind ke network IP bukan loopback (mis. `192.168.x.x`), jalankan server dengan `-p 3001` dan pastikan Playwright `baseURL` cocok (config sekarang menggunakan `http://127.0.0.1:3001`).
- Jika ada kegagalan yang hanya muncul di CI, ambil trace/screenshot/video dari direktori `test-results/` lalu jalankan `npx playwright show-trace` untuk analisis visual.

---

## Troubleshooting cepat
- `npx playwright install` gagal: bersihkan cache (`npm ci`), jalankan lagi `npx playwright install --with-deps`.
- `ERR_CONNECTION_REFUSED`: pastikan Next.js server berjalan pada port yang Playwright pakai (`127.0.0.1:3001`) dan firewall tidak memblokir.
- `playwright` menemukan banyak elemen untuk selector: gunakan selector yang lebih spesifik (ex: `getByRole`, `locator('h3', { hasText: 'Name' })`).

## Xendit sandbox (opsional)
Untuk mengaktifkan integrasi Xendit sandbox (payment provider untuk pasar Indonesia), tambahkan variabel lingkungan pada server backend (PHP) dengan nama `XENDIT_SECRET_KEY`.

- Langkah cepat (lokal Laragon):
  - Buka pengaturan environment atau virtual host, dan tambahkan `XENDIT_SECRET_KEY="xnd_test_..."`, lalu restart Apache.
  - (Opsional) set `XENDIT_CALLBACK_URL` jika webhook harus diarahkan ke URL tertentu, atau biarkan sistem menebak berdasarkan host.

- Di GitHub Actions (untuk CI), tambahkan secret `XENDIT_SECRET_KEY` di Settings → Secrets, lalu gunakan `secrets.XENDIT_SECRET_KEY` pada server deployment/CI yang menjalankan backend.

Setelah Anda menambahkan secrets/vars tersebut, saat checkout dibuat server akan memanggil Xendit sandbox dan mengembalikan `invoice_url` yang akan mengarahkan pengguna ke UI pembayaran Xendit.

Catatan keamanan: Jangan membagikan `XENDIT_SECRET_KEY` publik di chat atau repositori; simpan di Secrets/Environment yang aman.
---

Jika Anda mau, saya bisa tambahkan badge status CI ke `README.md` proyek setelah workflow berjalan di GitHub (butuh satu run sukses untuk menampilkan badge).

Butuh penjelasan tambahan atau langsung ingin saya lanjutkan ke Phase 8 (Checkout & Payment)?
