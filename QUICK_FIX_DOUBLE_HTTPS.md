# 🚨 QUICK FIX - Error dengan Ngrok

## Masalah yang Ditemukan

### Error 1: Double HTTPS
```
POST https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/...
net::ERR_NAME_NOT_RESOLVED
```

### Error 2: Ngrok Browser Warning
Ngrok menampilkan **HTML warning page** instead of proxying request ke backend.

## ROOT CAUSE

1. ❌ **Environment variable di Vercel** kemungkinan berisi double `https://`
2. ❌ **Frontend tidak mengirim header `ngrok-skip-browser-warning`**
3. ❌ **Ngrok intercept request** dan return HTML warning page
4. ❌ **Browser expect JSON** tapi dapat HTML → Error

## Penyebab Detail

Ngrok (free tier) memiliki **browser warning page** yang muncul untuk mencegah phishing. 
Frontend harus mengirim header `ngrok-skip-browser-warning` untuk bypass warning ini.

## Solusi - URGENT!

### 1. Login ke Vercel Dashboard
👉 https://vercel.com

### 2. Edit Environment Variable

1. Pilih project **dailycup**
2. Klik **Settings** → **Environment Variables**
3. Cari variable `NEXT_PUBLIC_API_URL`
4. Click **Edit** atau **Delete** lalu buat baru

### 3. Isi dengan URL yang BENAR

**❌ SALAH** (Yang sekarang):
```
https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api
```

**✅ BENAR** (Yang harus diisi):
```
https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api
```

**PENTING:**
- Hanya **SATU** `https://` di awal
- Tidak ada `//` setelah `https:`
- Tidak ada `/` di akhir URL

### 4. Apply ke Semua Environment

Pastikan di-set untuk:
- ✅ Production
- ✅ Preview  
- ✅ Development

### 5. Redeploy

1. Klik **Deployments**
2. Pilih deployment terakhir
3. Klik titik tiga (**...**) → **Redeploy**
4. Tunggu sampai selesai

### 6. Test

Buka https://dailycup.vercel.app/login dan coba login.

## Verifikasi URL Ngrok yang Benar

Sebelum isi environment variable, test dulu di browser:

```
https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api/products.php
```

Jika muncul JSON products, berarti URL path-nya benar!

## Jika Masih Error

1. **Pastikan Laragon Running**
   ```
   http://localhost/DailyCup/webapp/backend/api/products.php
   ```

2. **Pastikan Ngrok Running**
   ```powershell
   ngrok http 80
   ```

3. **Update URL di Vercel** dengan ngrok URL yang baru

4. **Redeploy Vercel**

## Script Helper

Jalankan script ini untuk check URL format:

```powershell
.\check_api_url.ps1
```

Script akan:
- ✅ Detect jika ada double `https://`
- ✅ Suggest URL yang benar
- ✅ Show common mistakes

## Catatan Penting

### Perbaikan yang Sudah Dilakukan di Code:

1. ✅ **next.config.ts** - Auto-fix double `https://` jika ada
2. ✅ **admin login** - Auto-fix double `https://` + header ngrok bypass
3. ✅ **api-client.ts** - Header ngrok bypass untuk semua API calls
4. ✅ **utils/api.ts** - Helper function untuk ngrok bypass header
5. ✅ **cors.php** - Support ngrok domains + allow ngrok headers
6. ✅ **login.php** - Hapus duplikasi CORS headers

### Yang Harus Anda Lakukan:

1. ⏳ **Fix environment variable di Vercel** (URGENT!)
2. ⏳ **Redeploy Vercel**
3. ⏳ **Test login**

---

**Estimasi Waktu**: 5-10 menit
**Priority**: 🔴 CRITICAL - App tidak bisa login tanpa fix ini
