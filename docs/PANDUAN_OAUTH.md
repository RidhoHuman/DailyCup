# Panduan OAuth Setup untuk DailyCup

Panduan ini akan membantu Anda mendapatkan kredensial OAuth untuk Google dan Facebook login.

## 📌 Google OAuth Setup

### Langkah 1: Buat Project di Google Cloud Console

1. Buka [Google Cloud Console](https://console.cloud.google.com/)
2. Login dengan akun Google Anda
3. Klik **"Select a Project"** > **"New Project"**
4. Beri nama project: **"DailyCup"**
5. Klik **"Create"**

### Langkah 2: Enable Google+ API

1. Di menu kiri, pilih **"APIs & Services"** > **"Library"**
2. Cari **"Google+ API"**
3. Klik dan pilih **"Enable"**

### Langkah 3: Configure OAuth Consent Screen

1. Di menu kiri, pilih **"APIs & Services"** > **"OAuth consent screen"**
2. Pilih **"External"** > Klik **"Create"**
3. Isi form:
   - App name: **DailyCup Coffee Shop**
   - User support email: **email Anda**
   - Developer contact: **email Anda**
4. Klik **"Save and Continue"**
5. Di Scopes, klik **"Add or Remove Scopes"**
6. Pilih:
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
7. Klik **"Update"** > **"Save and Continue"**
8. Add Test Users (opsional untuk development)
9. Klik **"Save and Continue"**

### Langkah 4: Buat OAuth 2.0 Client ID

1. Di menu kiri, pilih **"Credentials"**
2. Klik **"Create Credentials"** > **"OAuth client ID"**
3. Application type: **"Web application"**
4. Name: **"DailyCup Web Client"**
5. Authorized redirect URIs:
   - Klik **"Add URI"**
   - Masukkan: `http://localhost/dailycup/auth/google_callback.php`
   - Untuk production: `https://yourdomain.com/auth/google_callback.php`
6. Klik **"Create"**

### Langkah 5: Copy Credentials

1. Setelah dibuat, akan muncul popup dengan:
   - **Client ID** (contoh: `123456789-abc.apps.googleusercontent.com`)
   - **Client Secret** (contoh: `GOCSPX-abc123def456`)
2. Copy kedua nilai ini

### Langkah 6: Update config/oauth_config.php

```php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID_HERE'); // Paste Client ID
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE'); // Paste Client Secret
```

---

## 📘 Facebook OAuth Setup

### Langkah 1: Buat Facebook App

1. Buka [Facebook Developers](https://developers.facebook.com/)
2. Login dengan akun Facebook Anda
3. Klik **"My Apps"** > **"Create App"**
4. Pilih **"Consumer"** > Klik **"Next"**
5. Isi form:
   - App Display Name: **DailyCup Coffee Shop**
   - App Contact Email: **email Anda**
6. Klik **"Create App"**

### Langkah 2: Add Facebook Login Product

1. Di Dashboard app, cari **"Facebook Login"**
2. Klik **"Set Up"**
3. Pilih platform: **"Web"**
4. Site URL: `http://localhost/dailycup/` (untuk development)
5. Klik **"Save"** > **"Continue"**

### Langkah 3: Configure Facebook Login Settings

1. Di menu kiri, pilih **"Facebook Login"** > **"Settings"**
2. Valid OAuth Redirect URIs:
   - Masukkan: `http://localhost/dailycup/auth/facebook_callback.php`
   - Untuk production: `https://yourdomain.com/auth/facebook_callback.php`
3. Klik **"Save Changes"**

### Langkah 4: Get App Credentials

1. Di menu kiri, pilih **"Settings"** > **"Basic"**
2. Anda akan melihat:
   - **App ID** (contoh: `1234567890123456`)
   - **App Secret** (klik **"Show"** untuk melihat)
3. Copy kedua nilai ini

### Langkah 5: Update config/oauth_config.php

```php
define('FACEBOOK_APP_ID', 'YOUR_APP_ID_HERE'); // Paste App ID
define('FACEBOOK_APP_SECRET', 'YOUR_APP_SECRET_HERE'); // Paste App Secret
```

### Langkah 6: Set App Mode (Production)

Untuk production:
1. Di dashboard, switch dari **"In Development"** ke **"Live"**
2. Isi required information
3. Submit for review jika diperlukan

---

## 🔒 Keamanan

### Tips Keamanan:

1. **Jangan commit credentials** ke Git
2. Gunakan **environment variables** untuk production:
   ```php
   define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
   ```
3. **Ganti redirect URI** untuk production dengan domain yang sesuai
4. **Enable HTTPS** untuk production
5. **Batasi domain** yang boleh menggunakan OAuth credentials

### File .gitignore

Pastikan `config/oauth_config.php` ada di `.gitignore` jika berisi credentials asli:

```
config/oauth_config.php
```

Buat file `config/oauth_config.example.php` sebagai template:

```php
<?php
// Copy file ini menjadi oauth_config.php dan isi dengan credentials Anda
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
// dst...
```

---

## 🧪 Testing OAuth

### Test Google Login:
1. Buka: `http://localhost/dailycup/auth/login.php`
2. Klik tombol **"Login dengan Google"**
3. Pilih akun Google
4. Grant permissions
5. Anda akan di-redirect kembali ke aplikasi dan otomatis login

### Test Facebook Login:
1. Buka: `http://localhost/dailycup/auth/login.php`
2. Klik tombol **"Login dengan Facebook"**
3. Login dengan akun Facebook
4. Grant permissions
5. Anda akan di-redirect kembali ke aplikasi dan otomatis login

---

## ❗ Troubleshooting

### Google OAuth Errors:

**Error: "redirect_uri_mismatch"**
- Pastikan redirect URI di Google Console sama persis dengan yang di config
- Check: `http://localhost/dailycup/auth/google_callback.php`

**Error: "access_denied"**
- User membatalkan authorization
- Check scope permissions

### Facebook OAuth Errors:

**Error: "Can't Load URL"**
- Pastikan redirect URI sudah ditambahkan di Facebook App Settings
- Check: Valid OAuth Redirect URIs

**Error: "App Not Setup"**
- Pastikan Facebook Login product sudah di-setup
- Check: Dashboard > Products

---

## 📝 Production Checklist

Sebelum deploy ke production:

- [ ] Update redirect URIs dengan domain production
- [ ] Enable HTTPS
- [ ] Move credentials ke environment variables
- [ ] Set Facebook app ke "Live" mode
- [ ] Configure domain whitelist di Google Console
- [ ] Test OAuth flow di production environment
- [ ] Setup proper error logging
- [ ] Add rate limiting
- [ ] Monitor OAuth usage

---

## 📚 Resources

- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login/)
- [OAuth 2.0 Best Practices](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-security-topics)

---

**Selamat! OAuth setup Anda sudah selesai! 🎉**

Jika ada pertanyaan atau masalah, silakan buat issue di repository atau hubungi tim development.
