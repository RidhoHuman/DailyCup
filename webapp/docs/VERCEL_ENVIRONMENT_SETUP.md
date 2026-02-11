# Vercel Environment Setup - Fix CORS Error

## Masalah yang Terjadi

**Error 1 - CORS**: `Access to fetch at '...' has been blocked by CORS policy`

**Error 2 - Double HTTPS**: `POST https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/... net::ERR_NAME_NOT_RESOLVED`

### Analisis Error

1. **Faktor Error**: 
   - Duplikasi CORS headers di `login.php` (di-set manual + di-set oleh `cors.php`)
   - Environment variable `NEXT_PUBLIC_API_URL` di Vercel berisi URL dengan **double `https://`**
   - Path API di `next.config.ts` hardcoded ke ngrok URL yang salah

2. **Kenapa Bisa Error**:
   - **CORS Error**: Headers CORS yang di-set dua kali menyebabkan konflik
   - **Double HTTPS**: URL menjadi `https://https//...` karena typo di environment variable
   - Browser tidak bisa resolve hostname yang tidak valid
   - Frontend tidak bisa connect ke backend

3. **Perbaikan yang Dilakukan**:
   - ✅ Hapus duplikasi CORS headers di `login.php`
   - ✅ Gunakan centralized `cors.php` di semua API endpoints
   - ✅ Update `cors.php` untuk support ngrok domains
   - ✅ Perbaiki `next.config.ts` untuk menggunakan environment variable
   - ✅ Tambahkan validasi di `next.config.ts` untuk detect & fix double `https://`
   - ✅ Tambahkan `create_order.php` untuk menggunakan `cors.php`

## Setup Environment Variables di Vercel

### Step 1: Login ke Vercel Dashboard
1. Buka https://vercel.com
2. Pilih project **dailycup**
3. Klik **Settings** → **Environment Variables**

### Step 2: Tambahkan Environment Variables

Tambahkan variable berikut:

| Key | Value | Environment |
|-----|-------|-------------|
| `NEXT_PUBLIC_API_URL` | `https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api` | Production, Preview, Development |

**⚠️ CRITICAL - PASTIKAN URL BENAR:**

✅ **BENAR**:
```
https://decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api
```
- Hanya **SATU** `https://` di awal
- Tidak ada `https//` atau `https://https//`

❌ **SALAH** (Penyebab Error):
```
https://https//decagonal-subpolygonally-brecken.ngrok-free.dev/DailyCup/webapp/backend/api
```
- Ada double `https://` atau `https//`
- Akan menyebabkan error `ERR_NAME_NOT_RESOLVED`

**PENTING**: 
- Ganti `decagonal-subpolygonally-brecken.ngrok-free.dev` dengan ngrok URL Anda yang aktif
- Path harus sesuai dengan struktur folder di Laragon Anda
- Jika backend Anda di root ngrok, gunakan: `https://YOUR-NGROK-URL/api`
- **JANGAN** copy-paste dari browser address bar yang mungkin sudah corrupted

### Step 3: Verifikasi Path Backend di Ngrok

1. **Cek Laragon Configuration**:
   - Buka Laragon
   - Klik **Menu** → **Preferences** → **General**
   - Lihat **Document Root** (biasanya `C:\laragon\www`)

2. **Tentukan Path yang Benar**:
   
   **Jika backend Anda di**: `C:\laragon\www\DailyCup\webapp\backend\api`
   
   **Maka ngrok path adalah**: 
   - Jika ngrok serve dari `C:\laragon\www`: `/DailyCup/webapp/backend/api`
   - Jika ngrok serve dari `C:\laragon\www\DailyCup`: `/webapp/backend/api`
   - Jika ngrok serve dari `C:\laragon\www\DailyCup\webapp\backend`: `/api`

3. **Test ngrok URL**:
   ```powershell
   # Test di browser atau curl
   curl https://YOUR-NGROK-URL/DailyCup/webapp/backend/api/products.php
   ```

### Step 4: Redeploy di Vercel

1. Setelah environment variable di-set
2. Klik **Deployments** → **Redeploy** pada deployment terakhir
3. Atau push commit baru untuk trigger deployment

## Cara Setup Ngrok (Jika Belum)

### Install Ngrok
```powershell
# Install via chocolatey
choco install ngrok

# Atau download dari https://ngrok.com/download
```

### Start Ngrok Tunnel

```powershell
# Jika Laragon menggunakan port 80
ngrok http 80

# Atau dengan domain custom (berbayar)
ngrok http 80 --domain=your-custom-domain.ngrok-free.app
```

### Update NEXT_PUBLIC_API_URL di Vercel

Setiap kali ngrok restart, URL akan berubah (kecuali pakai domain berbayar). 

Update environment variable di Vercel dengan URL baru:
```
https://NEW-NGROK-URL/DailyCup/webapp/backend/api
```

## Alternative: Gunakan Backend Production

Jika Anda sudah deploy backend ke hosting/VPS:

```env
NEXT_PUBLIC_API_URL=https://api.dailycup.com/api
```

Pastikan CORS di backend sudah allow `https://dailycup.vercel.app`

## Testing

### 1. Test CORS Headers

```bash
curl -I -X OPTIONS \
  -H "Origin: https://dailycup.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  https://YOUR-NGROK-URL/DailyCup/webapp/backend/api/login.php
```

**Expected Response**:
```
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://dailycup.vercel.app
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH
Access-Control-Allow-Headers: Content-Type, Authorization, ...
```

### 2. Test Login API

```bash
curl -X POST \
  -H "Content-Type: application/json" \
  -H "Origin: https://dailycup.vercel.app" \
  -d '{"email":"test@example.com","password":"password123"}' \
  https://YOUR-NGROK-URL/DailyCup/webapp/backend/api/login.php
```

### 3. Test dari Browser

1. Buka https://dailycup.vercel.app/login
2. Buka Developer Tools → Network tab
3. Login dengan credentials
4. Cek response headers dari request ke `/api/login.php`
5. Pastikan tidak ada CORS error

## Troubleshooting

### Error: "Network error - Backend may be offline"

**Penyebab**:
- Ngrok tunnel tidak aktif
- Backend server (Laragon) tidak running
- Environment variable salah

**Solusi**:
1. Pastikan Laragon running: `http://localhost/DailyCup/webapp/backend/api/products.php`
2. Pastikan ngrok running: `ngrok http 80`
3. Update `NEXT_PUBLIC_API_URL` di Vercel dengan ngrok URL yang benar
4. Redeploy Vercel

### Error: "CORS policy: No 'Access-Control-Allow-Origin' header"

**Penyebab**:
- Backend tidak include `cors.php`
- CORS headers di-set setelah output
- Duplikasi CORS headers

**Solusi**:
1. Pastikan `require_once __DIR__ . '/cors.php';` di baris pertama PHP
2. Tidak ada `echo` atau output sebelum `cors.php`
3. Tidak ada duplikasi CORS headers manual

### Ngrok URL berubah terus

**Penyebab**: Ngrok free tier generates random URL setiap restart

**Solusi**:
1. Upgrade ke ngrok Pro untuk static domain
2. Atau gunakan ngrok authtoken untuk reserved domain
3. Atau deploy backend ke hosting production

## Production Deployment (Recommended)

Untuk production, sebaiknya deploy backend ke:
- ✅ VPS (DigitalOcean, Linode, etc.)
- ✅ Shared Hosting dengan PHP support
- ✅ Cloud Platform (AWS, GCP, Azure)

Update environment variable:
```env
NEXT_PUBLIC_API_URL=https://api.dailycup.com/api
```

Jangan gunakan ngrok untuk production!

## Checklist

- [x] Perbaiki duplikasi CORS di `login.php`
- [x] Update `cors.php` untuk support ngrok
- [x] Perbaiki `next.config.ts` rewrite path
- [ ] Set `NEXT_PUBLIC_API_URL` di Vercel
- [ ] Test ngrok connection
- [ ] Redeploy Vercel
- [ ] Test login dari https://dailycup.vercel.app

## Support

Jika masih ada masalah, cek:
1. **Browser Console**: Lihat error detail
2. **Network Tab**: Cek request/response headers
3. **Backend Logs**: Cek PHP error logs
4. **Ngrok Logs**: Lihat incoming requests

---

**Last Updated**: 2 Februari 2026
