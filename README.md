# ☕ DailyCup Coffee Shop - CRM System

Website CRM lengkap untuk Coffee Shop "DailyCup" menggunakan PHP Native dengan database MySQL.

![DailyCup](https://img.shields.io/badge/PHP-Native-blue)
![MySQL](https://img.shields.io/badge/Database-MySQL-orange)
![Bootstrap](https://img.shields.io/badge/UI-Bootstrap_5-purple)

## 📋 Deskripsi

DailyCup adalah sistem manajemen coffee shop yang komprehensif dengan fitur-fitur modern termasuk:
- 🔐 Authentication (Manual & OAuth Google/Facebook)
- 🛒 Shopping Cart & Checkout
- 💳 Multiple Payment Methods
- 📦 Order Tracking Real-time
- ⭐ Rating & Review System
- 🎁 Loyalty Points Program
- 🔄 Return/Retur Management
- 📊 Admin Dashboard
- 📧 Email Notifications

## 🎨 Design Reference

Design UI/UX tersedia dalam file PNG/JPG di repository ini:
- `First Page.png` - Landing page
- `Home.png` - Halaman utama
- `Home Login.png` & `Home Sign Up.png` - Authentication
- `Menu & Info.jpg` & `Extra Menu.png` - Menu pages
- `My Cart.png` - Shopping cart
- `Payment 1.png` & `Payment 2.png` - Payment pages
- `About.png` - About page

## 🚀 Instalasi

### Prerequisites

- **Laragon** (atau XAMPP/WAMP) dengan:
  - PHP 7.4 atau lebih tinggi
  - MySQL 5.7 atau lebih tinggi
  - Apache dengan mod_rewrite enabled
- Browser modern (Chrome, Firefox, Edge, Safari)

### Langkah Instalasi

#### 1. Clone Repository

```bash
cd C:\laragon\www
git clone https://github.com/RidhoHuman/UI_UX-DailyCup-CMLABS.git dailycup
cd dailycup
```

#### 2. Import Database

1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`)
2. Buat database baru bernama `dailycup_db`
3. Import file `database/dailycup_db.sql`

Atau menggunakan command line:

```bash
mysql -u root -p
CREATE DATABASE dailycup_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dailycup_db;
SOURCE database/dailycup_db.sql;
EXIT;
```

#### 3. Konfigurasi Database

File `config/database.php` sudah dikonfigurasi untuk Laragon default:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dailycup_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosong untuk Laragon default
```

Jika menggunakan kredensial berbeda, edit file tersebut sesuai kebutuhan.

#### 4. Setup OAuth (Opsional)

Untuk mengaktifkan login Google & Facebook:

1. Baca panduan lengkap di `docs/PANDUAN_OAUTH.md`
2. Dapatkan credentials dari Google Cloud Console dan Facebook Developers
3. Edit file `config/oauth_config.php` dan masukkan credentials Anda:

```php
define('GOOGLE_CLIENT_ID', 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', 'your-google-client-secret');
define('FACEBOOK_APP_ID', 'your-facebook-app-id');
define('FACEBOOK_APP_SECRET', 'your-facebook-app-secret');
```

#### 5. Set Permissions (Jika diperlukan)

Pastikan folder berikut dapat ditulis (writable):

```bash
chmod -R 777 assets/images/products
chmod -R 777 assets/images/reviews
chmod -R 777 assets/images/returns
```

#### 6. Akses Website

Buka browser dan akses:

```
http://localhost/dailycup/
```

## 👤 Default Admin Account

Gunakan kredensial berikut untuk login sebagai Super Admin:

- **Email**: `admin@dailycup.com`
- **Password**: `admin123`

⚠️ **PENTING**: Ganti password default setelah instalasi pertama!

## 📁 Struktur Folder

```
dailycup/
├── config/              # Konfigurasi database & OAuth
├── includes/            # Header, footer, navbar, functions
├── assets/             
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   └── images/         # Images & uploads
├── auth/               # Authentication (login, register, OAuth)
├── customer/           # Customer pages
│   ├── menu.php
│   ├── cart.php
│   ├── checkout.php
│   ├── orders.php
│   └── ...
├── admin/              # Admin panel
│   ├── index.php       # Dashboard
│   ├── products/       # Product management
│   ├── orders/         # Order management
│   └── ...
├── api/                # API endpoints
│   ├── cart.php
│   ├── notifications.php
│   └── favorites.php
├── emails/             # Email templates
├── database/           # SQL schema
├── docs/               # Documentation
├── index.php           # Landing page
├── .htaccess           # Apache configuration
└── README.md
```

## 🎯 Fitur Utama

### 🔐 Authentication & Authorization

- **Manual Login/Register**: Email & password dengan password hashing (BCrypt)
- **OAuth Integration**: Login dengan Google & Facebook
- **Role Management**: Customer, Admin, Super Admin
- **CSRF Protection**: Token-based security untuk semua forms

### 👥 Customer Features

| Fitur | Deskripsi |
|-------|-----------|
| 🍕 **Menu Browsing** | Lihat produk berdasarkan kategori dengan search & filter |
| 📱 **Product Detail** | Detail produk dengan varian (size, temperature) |
| 🛒 **Shopping Cart** | Add, update, remove items dengan notes |
| 💳 **Multiple Payment** | Bank Transfer, QRIS, E-Wallet (GoPay, OVO, Dana, ShopeePay) |
| 📦 **Order Tracking** | Real-time status: pending → confirmed → processing → ready → delivering → completed |
| 🔔 **Notifications** | Web notifications + Email saat order selesai |
| ⭐ **Reviews** | Rating 1-5 bintang dengan text & foto |
| 💝 **Favorites** | Simpan produk favorit |
| 🎁 **Loyalty Points** | Dapatkan poin dari pembelian, tukar untuk diskon |
| 🔄 **Returns** | Form retur dengan alasan, deskripsi, dan foto |
| 👤 **Profile** | Edit data diri, lihat history pesanan |

### 👨‍💼 Admin Features

| Fitur | Deskripsi |
|-------|-----------|
| 📊 **Dashboard** | Statistik penjualan, pesanan hari ini, revenue |
| 🍔 **Products CRUD** | Create, Read, Update, Delete produk |
| 🏷️ **Categories CRUD** | Kelola kategori menu |
| 📦 **Orders Management** | Lihat semua pesanan, update status |
| 👥 **Users Management** | Kelola akun customer & admin (Super Admin only) |
| 🎟️ **Discounts CRUD** | Kelola diskon dan partner |
| 🎫 **Redeem Codes** | Buat dan kelola kode voucher |
| 🔄 **Returns Review** | Review & process permintaan retur |
| ⭐ **Reviews Management** | Lihat & balas review customer |
| 🎁 **Loyalty Settings** | Atur poin per rupiah, nilai tukar |

## 💻 Teknologi Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Custom styling dengan CSS Variables
- **Bootstrap 5.3** - Responsive UI framework
- **Bootstrap Icons** - Icon library
- **Vanilla JavaScript** - No jQuery, pure ES6+

### Backend
- **PHP Native 7.4+** - Tanpa framework
- **PDO** - Prepared statements untuk keamanan
- **Sessions** - User authentication & cart management

### Database
- **MySQL 5.7+** - Relational database
- **InnoDB Engine** - Transaction support
- **UTF8MB4** - Full Unicode support

### Security
- **Password Hashing** - BCrypt algorithm
- **CSRF Protection** - Token-based
- **XSS Prevention** - Input sanitization
- **SQL Injection Prevention** - Prepared statements
- **Secure Headers** - X-Frame-Options, X-XSS-Protection, etc.

## 🔧 Konfigurasi

### Site URL

Edit `config/constants.php` untuk mengubah base URL:

```php
define('SITE_URL', 'http://localhost/dailycup');
```

### Database

Edit `config/database.php` untuk konfigurasi database:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dailycup_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Upload Limits

Edit `config/constants.php` untuk limits upload:

```php
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
```

### Email Configuration

Untuk notifikasi email, edit `config/constants.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

**Note**: Gunakan App Password untuk Gmail, bukan password akun.

## 📱 Responsive Design

Website fully responsive untuk semua device:

- 📱 Mobile (< 576px)
- 📱 Tablet (576px - 992px)
- 💻 Desktop (> 992px)

Lihat `assets/css/responsive.css` untuk breakpoints.

## 🎨 Tema & Warna

```css
--primary-color: #6F4E37;   /* Coffee Brown */
--secondary-color: #D4A574; /* Cream/Beige */
--accent-color: #4A3728;    /* Dark Brown */
```

Edit di `assets/css/style.css` untuk customize.

## 🔒 Keamanan

### Best Practices Implemented:

✅ Password hashing dengan BCrypt  
✅ CSRF tokens untuk semua forms  
✅ SQL injection prevention (PDO Prepared Statements)  
✅ XSS prevention (Input sanitization)  
✅ Session security  
✅ File upload validation  
✅ Secure HTTP headers  
✅ Role-based access control  

### Recommendations:

1. **Production**: Gunakan HTTPS
2. **Production**: Set `display_errors = Off` di PHP
3. **Production**: Move sensitive config ke environment variables
4. **Production**: Regular security updates
5. **Production**: Implement rate limiting
6. **Production**: Use strong passwords

## 🐛 Troubleshooting

### Database Connection Error

**Error**: `Database connection failed`

**Solution**:
1. Pastikan MySQL service running
2. Check kredensial di `config/database.php`
3. Pastikan database `dailycup_db` sudah dibuat

### OAuth Not Working

**Error**: `redirect_uri_mismatch`

**Solution**:
1. Pastikan redirect URI di config sama dengan yang di OAuth Console
2. Check `config/oauth_config.php`
3. Lihat panduan lengkap di `docs/PANDUAN_OAUTH.md`

### Upload Image Failed

**Error**: `Failed to upload file`

**Solution**:
1. Check permissions folder `assets/images/`
2. Check `upload_max_filesize` di php.ini
3. Check `MAX_FILE_SIZE` di `config/constants.php`

### Page Not Found (404)

**Solution**:
1. Pastikan `.htaccess` ada dan mod_rewrite enabled
2. Check `RewriteBase` di `.htaccess`
3. Restart Apache

## 📚 Dokumentasi Tambahan

- [Panduan OAuth Setup](docs/PANDUAN_OAUTH.md) - Cara mendapatkan Google & Facebook OAuth credentials

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## 📝 License

This project is created for educational purposes.

## 👨‍💻 Developer

**RidhoHuman**

- GitHub: [@RidhoHuman](https://github.com/RidhoHuman)

## 🙏 Acknowledgments

- Bootstrap 5 for the UI framework
- Bootstrap Icons for the icon library
- Google & Facebook for OAuth integration
- CMLABS for the project opportunity

---

**Happy Coding! ☕**

Jika ada pertanyaan atau issues, silakan buat issue di repository ini.
