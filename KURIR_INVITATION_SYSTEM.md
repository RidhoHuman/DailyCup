# 🎫 Sistem Undangan Kurir DailyVery

## 📋 Ringkasan

Sistem undangan kurir telah berhasil diimplementasikan untuk mengubah pendaftaran kurir dari **terbuka untuk publik** menjadi **khusus karyawan internal dengan undangan**.

---

## ✅ Komponen yang Telah Dibuat

### 1. **Database**
- ✅ Tabel `kurir_invitations` dengan struktur:
  - `invitation_code` (unik, format DC-XXXXX-XXXXX-XXXX)
  - `invited_name`, `invited_phone`, `invited_email`, `vehicle_type`
  - `status` (pending/used/expired)
  - `created_by` (FK ke admin), `used_by` (FK ke kurir)
  - `expires_at`, `used_at`, `created_at`
  - `notes` untuk catatan tambahan

**File**: [database/kurir_invitation_system.sql](database/kurir_invitation_system.sql)

---

### 2. **Backend API**

#### A. Verifikasi Undangan
**Endpoint**: `GET /api/kurir_verify_invitation.php?code={invitation_code}`
- Validasi kode undangan
- Cek status (pending/used/expired)
- Cek tanggal kedaluwarsa
- Return data pre-filled (nama, phone, email, vehicle_type)

**File**: [backend/api/kurir_verify_invitation.php](backend/api/kurir_verify_invitation.php)

#### B. Buat Undangan Baru
**Endpoint**: `POST /api/create_kurir_invitation.php`
- Requires admin JWT token
- Generate kode unik format DC-XXXXX-XXXXX-XXXX
- Set tanggal kedaluwarsa (1-30 hari)
- Store data calon kurir

**File**: [backend/api/create_kurir_invitation.php](backend/api/create_kurir_invitation.php)

#### C. List Undangan
**Endpoint**: `GET /api/get_kurir_invitations.php?status={all|pending|used|expired}`
- Requires admin JWT token
- Filter by status
- Return array undangan dengan detail lengkap

**File**: [backend/api/get_kurir_invitations.php](backend/api/get_kurir_invitations.php)

#### D. Hapus Undangan
**Endpoint**: `DELETE /api/delete_kurir_invitation.php?id={invitation_id}`
- Requires admin JWT token
- Hanya bisa hapus undangan yang belum digunakan
- Prevent deletion undangan yang sudah used

**File**: [backend/api/delete_kurir_invitation.php](backend/api/delete_kurir_invitation.php)

#### E. Registrasi Kurir (Modified)
**Endpoint**: `POST /api/kurir/register.php`
- Validasi `invitation_code` required
- Cek status & expiry invitation
- Mark invitation as 'used' setelah registrasi sukses
- Set `used_by` dan `used_at`

**File**: [backend/api/kurir/register.php](backend/api/kurir/register.php) *(modified)*

---

### 3. **Frontend**

#### A. Halaman Info Kurir (Public Landing Page)
**URL**: `/kurir/info`
- Hero section dengan branding DailyVery
- 6 benefit cards (gaji, fleksibilitas, asuransi, subsidi kendaraan, karir, komunitas)
- 4-step onboarding process
- Modal persyaratan (usia, KTP, SIM, STNK, kendaraan)
- CTA untuk kontak HR

**File**: [app/kurir/info/page.tsx](app/kurir/info/page.tsx)

#### B. Formulir Registrasi Kurir (Modified)
**URL**: `/kurir/register?code={invitation_code}`
- Auto-detect invitation code dari URL query parameter
- Verifikasi kode saat page load
- Pre-fill form jika kode valid
- Show error jika kode invalid/expired/used
- Submit dengan `invitation_code` included

**File**: [app/kurir/register/page.tsx](app/kurir/register/page.tsx) *(modified)*

#### C. Admin - Kelola Undangan
**URL**: `/admin/kurir-invitations`
- Dashboard stats (total, pending, used, expired)
- Tabel list undangan dengan filter status
- Form buat undangan baru
- Copy invitation link dengan 1-click
- Delete undangan yang belum digunakan
- Countdown timer untuk expiry

**File**: [app/admin/(panel)/kurir-invitations/page.tsx](app/admin/(panel)/kurir-invitations/page.tsx)

#### D. Admin Sidebar (Modified)
**Menu Item Baru**: "Undangan Kurir"
- Icon: `bi-ticket-perforated`
- Link: `/admin/kurir-invitations`
- Posisi: Setelah menu "Kurir"

**File**: [components/admin/Sidebar.tsx](components/admin/Sidebar.tsx) *(modified)*

#### E. Homepage Footer (Modified)
**Section Karir**:
- Link "Bergabung Jadi Kurir" → `/kurir/info`
- Icon bicycle untuk visual
- Terletak di footer column "Karir"

**File**: [app/page.tsx](app/page.tsx) *(modified)*

#### F. Kurir API Library (Modified)
**Method**: `register(data)`
- Added `invitation_code?: string` parameter
- Include invitation code in registration payload

**File**: [lib/kurir-api.ts](lib/kurir-api.ts) *(modified)*

---

## 🔄 Alur Kerja Sistem

### 1. **Admin Membuat Undangan**
```
Admin Login → Sidebar "Undangan Kurir" → Form Buat Undangan Baru
↓
Input: Nama, Phone, Email, Tipe Kendaraan, Masa Berlaku, Catatan
↓
Generate Code (DC-12ABC-56DEF-GH34) → Simpan ke Database
↓
Copy Link: https://dailycup.com/kurir/register?code=DC-12ABC-56DEF-GH34
↓
Share via WhatsApp/Email ke calon kurir
```

### 2. **Calon Kurir Mendaftar**
```
Akses Link dengan Code → Halaman /kurir/register?code=...
↓
Sistem Verifikasi Code (valid/expired/used)
↓
Jika VALID: Form auto-fill dengan data pre-registered
↓
Calon kurir lengkapi data (password, vehicle_number, dll)
↓
Submit Registration
↓
Backend validasi invitation → Mark as 'used' → Create kurir account
↓
Akun kurir dibuat dengan status 'pending' (menunggu approval admin)
```

### 3. **Admin Monitoring**
```
Dashboard /admin/kurir-invitations
↓
Lihat statistik: Pending, Used, Expired
↓
Filter by status untuk audit
↓
Delete undangan yang expired/tidak terpakai
```

---

## 🔐 Keamanan

1. **JWT Authentication**: Semua admin endpoint require valid JWT token
2. **Role Check**: Hanya admin yang bisa create/read/delete invitations
3. **One-Time Use**: Setiap invitation code hanya bisa dipakai 1x
4. **Expiry Date**: Invitation otomatis expired setelah masa berlaku habis
5. **Validation**: Backend double-check invitation sebelum create kurir account

---

## 🧪 Testing Checklist

### Backend Testing
- [ ] Test create invitation dengan admin token
- [ ] Test create invitation tanpa admin token (harus fail)
- [ ] Test verify invitation dengan code valid
- [ ] Test verify invitation dengan code expired
- [ ] Test verify invitation dengan code used
- [ ] Test register kurir dengan invitation valid
- [ ] Test register kurir tanpa invitation (harus fail)
- [ ] Test delete invitation pending
- [ ] Test delete invitation used (harus fail)

### Frontend Testing
- [ ] Akses `/kurir/info` tanpa login (public)
- [ ] Akses `/kurir/register` tanpa code (harus minta code)
- [ ] Akses `/kurir/register?code=INVALID` (harus error)
- [ ] Akses `/kurir/register?code=VALID` (harus pre-fill form)
- [ ] Submit registration dengan invitation valid
- [ ] Admin: buat invitation baru
- [ ] Admin: copy invitation link
- [ ] Admin: delete invitation pending
- [ ] Admin: filter invitations by status
- [ ] Footer link "Bergabung Jadi Kurir" → `/kurir/info`

### Integration Testing
- [ ] Full flow: Admin create → Copy link → Calon kurir register → Invitation marked used
- [ ] Expiry automation: Invitation otomatis expired setelah tanggal lewat
- [ ] Prevent double use: Register dengan same code 2x (harus fail)

---

## 📱 Demo Flow

### Admin Flow
```bash
1. Login sebagai admin
2. Menu sidebar: klik "Undangan Kurir"
3. Klik "Buat Undangan Baru"
4. Isi form:
   - Nama: John Doe
   - Phone: 08123456789
   - Email: john@example.com
   - Kendaraan: Motor
   - Masa Berlaku: 7 hari
   - Catatan: Rekomendasi dari HR
5. Klik "Generate"
6. Copy link invitation
7. Share ke calon kurir via WhatsApp
```

### Calon Kurir Flow
```bash
1. Terima link via WhatsApp
2. Klik link → redirect ke /kurir/register?code=...
3. Form sudah pre-filled (nama, phone, email, kendaraan)
4. Lengkapi:
   - Password
   - Nomor Kendaraan
   - Upload KTP/SIM (jika required)
5. Submit
6. Akun created → Status: Pending Approval
7. Tunggu admin approve
```

---

## 🚀 Deployment Notes

1. **Database Migration**: Jalankan `database/kurir_invitation_system.sql`
2. **Environment Check**: Pastikan JWT secret configured
3. **File Permissions**: Backend API files harus executable
4. **CORS**: Pastikan CORS headers configured untuk API calls
5. **Email/WhatsApp**: (Optional) Auto-send invitation link via email

---

## 🎯 Manfaat Sistem

1. **Keamanan**: Mencegah registrasi kurir sembarangan
2. **Verifikasi**: Admin bisa pre-screen calon kurir
3. **Tracking**: Monitoring siapa yang diundang, kapan, oleh siapa
4. **Auditable**: History invitation di database
5. **Ekspirasi Otomatis**: Undangan tidak berlaku selamanya
6. **Professional**: Process recruitment lebih terstruktur

---

## 📝 Next Steps (Optional Enhancements)

1. **Email Integration**: Auto-send invitation link via email
2. **WhatsApp API**: Auto-send invitation via WhatsApp Business API
3. **Bulk Import**: Upload CSV untuk create multiple invitations
4. **Notification**: Alert admin when invitation expires
5. **Analytics**: Dashboard untuk invitation conversion rate
6. **Reminder**: Auto-reminder sebelum invitation expired
7. **Referral System**: Track siapa yang refer calon kurir

---

## 👨‍💻 Developer Notes

- Semua file backend di folder: `backend/api/`
- Semua file frontend di folder: `app/` dan `components/`
- Database schema di: `database/kurir_invitation_system.sql`
- API client utilities di: `lib/kurir-api.ts` dan `lib/api-client.ts`

---

**Status**: ✅ Implementasi Complete - Ready for Testing

**Last Updated**: 2025-01-XX

**Implemented by**: GitHub Copilot (Claude Sonnet 4.5)
