# 🛒 IMPLEMENTASI PERSISTENT CART (Cart Tidak Hilang Saat Logout)

## ❌ MASALAH AWAL

### User Experience Yang Buruk:
```
1. User login ✅
2. User tambah 5 produk ke cart ✅
3. Badge cart menunjukkan [5] ✅
4. User logout ❌
5. User login kembali ❌
6. Cart KOSONG! [0] ❌
7. User harus input ulang semua produk ❌❌❌
```

**Ini sangat merepotkan user!** 😤

---

## 🔍 PENYEBAB MASALAH

### Root Cause: Cart Disimpan di PHP Session

**File:** `api/cart.php`, `includes/functions.php`

**Implementasi Lama:**
```php
// Cart disimpan di session
$_SESSION['cart'] = [
    ['product_id' => 1, 'quantity' => 2, ...],
    ['product_id' => 5, 'quantity' => 1, ...]
];

// Saat logout: session_destroy()
// → $_SESSION['cart'] HILANG!
```

**Kenapa Hilang?**
- PHP Session disimpan di **server memory** atau **temporary files**
- Saat logout, `session_destroy()` menghapus semua data session
- Cart tidak disimpan ke **database** yang permanent
- Saat login lagi, session baru dimulai → cart kosong!

**Analogi:**
- Session = **Post-it note** (gampang hilang)
- Database = **Buku catatan** (tersimpan permanent)

---

## ✅ SOLUSI: PERSISTENT CART SYSTEM

### Konsep Persistent Cart:
1. **Simpan cart ke DATABASE**, bukan hanya session
2. **Load cart dari database** saat user login
3. **Sync setiap perubahan** cart ke database
4. **Cart tetap ada** meskipun logout/login berkali-kali
5. **Clear cart** hanya saat checkout berhasil

---

## 📊 ARSITEKTUR SOLUSI

### Flow Persistent Cart:

```
┌─────────────────────────────────────────────────┐
│  USER ACTION                                     │
├─────────────────────────────────────────────────┤
│  1. Login                                        │
│     → Load cart from database to session        │
│                                                  │
│  2. Add Product                                  │
│     → Save to session                           │
│     → Save to database ✅                        │
│                                                  │
│  3. Update Quantity                              │
│     → Update session                            │
│     → Update database ✅                         │
│                                                  │
│  4. Remove Item                                  │
│     → Remove from session                       │
│     → Remove from database ✅                    │
│                                                  │
│  5. Logout                                       │
│     → Session destroyed                         │
│     → Database TETAP ADA ✅                      │
│                                                  │
│  6. Login Again                                  │
│     → Load cart from database ✅                │
│     → Cart kembali seperti semula! 🎉          │
│                                                  │
│  7. Checkout                                     │
│     → Clear session                             │
│     → Clear database ✅                          │
└─────────────────────────────────────────────────┘
```

---

## 🗄️ DATABASE SCHEMA

### Tabel Baru: `cart_items`

**File:** `database/cart_table.sql`

```sql
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,                    -- User pemilik cart
    product_id INT NOT NULL,                 -- ID produk
    product_name VARCHAR(255) NOT NULL,      -- Nama produk
    price DECIMAL(10,2) NOT NULL,           -- Harga
    size VARCHAR(50),                        -- Size (Small/Medium/Large)
    temperature VARCHAR(50),                 -- Temperature (Hot/Cold/Iced)
    quantity INT NOT NULL DEFAULT 1,        -- Jumlah
    image VARCHAR(255),                      -- Gambar produk
    cart_key VARCHAR(100) NOT NULL,         -- Unique key (product+size+temp)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_cart_key (cart_key),
    UNIQUE KEY unique_user_cart_key (user_id, cart_key)
);
```

**Field Penting:**
- `user_id`: Identifikasi cart milik user mana
- `cart_key`: Unique identifier untuk kombinasi product+size+temperature
- `UNIQUE KEY`: Mencegah duplicate item untuk user yang sama
- `ON DELETE CASCADE`: Jika user/product dihapus, cart items juga ikut terhapus

---

## 💻 IMPLEMENTASI KODE

### 1️⃣ Fungsi Helper di `includes/functions.php`

**Fungsi Baru yang Ditambahkan:**

#### **A. Load Cart dari Database**
```php
function loadCartFromDatabase($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    
    $_SESSION['cart'] = [];
    foreach ($items as $item) {
        $_SESSION['cart'][] = [
            'cart_key' => $item['cart_key'],
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'price' => $item['price'],
            'size' => $item['size'],
            'temperature' => $item['temperature'],
            'quantity' => $item['quantity'],
            'image' => $item['image']
        ];
    }
    return $_SESSION['cart'];
}
```
**Dipanggil saat:** User login

---

#### **B. Save Cart Item ke Database**
```php
function saveCartItemToDatabase($userId, $cartItem) {
    $db = getDB();
    
    // Cek apakah item sudah ada
    $stmt = $db->prepare("SELECT id, quantity FROM cart_items 
                          WHERE user_id = ? AND cart_key = ?");
    $stmt->execute([$userId, $cartItem['cart_key']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update quantity jika sudah ada
        $newQuantity = $existing['quantity'] + $cartItem['quantity'];
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $existing['id']]);
    } else {
        // Insert item baru
        $stmt = $db->prepare("INSERT INTO cart_items (...) VALUES (...)");
        $stmt->execute([...]);
    }
}
```
**Dipanggil saat:** User tambah produk ke cart

---

#### **C. Update Quantity di Database**
```php
function updateCartItemQuantityInDatabase($userId, $cartKey, $quantity) {
    $db = getDB();
    
    if ($quantity <= 0) {
        // Hapus jika quantity = 0
        $stmt = $db->prepare("DELETE FROM cart_items 
                             WHERE user_id = ? AND cart_key = ?");
        $stmt->execute([$userId, $cartKey]);
    } else {
        // Update quantity
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? 
                             WHERE user_id = ? AND cart_key = ?");
        $stmt->execute([$quantity, $userId, $cartKey]);
    }
}
```
**Dipanggil saat:** User ubah quantity (+/-)

---

#### **D. Remove Item dari Database**
```php
function removeCartItemFromDatabase($userId, $cartKey) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart_items 
                         WHERE user_id = ? AND cart_key = ?");
    $stmt->execute([$userId, $cartKey]);
}
```
**Dipanggil saat:** User hapus item dari cart

---

#### **E. Clear Semua Cart**
```php
function clearCartFromDatabase($userId) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->execute([$userId]);
}
```
**Dipanggil saat:** Checkout berhasil

---

#### **F. Sync Cart saat Login**
```php
function syncCartToDatabase($userId) {
    // Jika ada cart di session (sebelum login), save ke database
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            saveCartItemToDatabase($userId, $item);
        }
    }
    // Load cart dari database (merge dengan yang baru)
    loadCartFromDatabase($userId);
}
```
**Dipanggil saat:** Login berhasil

**Use Case:**
- User browse tanpa login → Tambah item ke cart → Login
- Cart sebelum login **digabung** dengan cart yang sudah ada di database!

---

### 2️⃣ Update `auth/login.php`

**Sebelum:**
```php
// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// Langsung redirect
header('Location: ' . $redirect);
```

**Sesudah:**
```php
// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

// Load cart from database (PERSISTENT CART) ✅
syncCartToDatabase($user['id']);

// Redirect
header('Location: ' . $redirect);
```

**Benefit:** Cart langsung ter-load saat login!

---

### 3️⃣ Update `api/cart.php`

#### **A. Initialize Cart**
**Sebelum:**
```php
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];  // Cart kosong
}
```

**Sesudah:**
```php
$userId = $_SESSION['user_id'];

if (!isset($_SESSION['cart'])) {
    loadCartFromDatabase($userId);  // Load dari database ✅
}
```

---

#### **B. Add Item**
**Sebelum:**
```php
$_SESSION['cart'][] = $newItem;
// Hanya save ke session
```

**Sesudah:**
```php
$_SESSION['cart'][] = $newItem;

// Save to database (PERSISTENT CART) ✅
saveCartItemToDatabase($userId, $newItem);
```

---

#### **C. Update Quantity**
**Sebelum:**
```php
$_SESSION['cart'][$key]['quantity'] = $quantity;
// Hanya update session
```

**Sesudah:**
```php
$_SESSION['cart'][$key]['quantity'] = $quantity;

// Update database (PERSISTENT CART) ✅
updateCartItemQuantityInDatabase($userId, $cartKey, $quantity);
```

---

#### **D. Remove Item**
**Sebelum:**
```php
unset($_SESSION['cart'][$key]);
// Hanya hapus dari session
```

**Sesudah:**
```php
unset($_SESSION['cart'][$key]);

// Remove from database (PERSISTENT CART) ✅
removeCartItemFromDatabase($userId, $cartKey);
```

---

#### **E. Clear Cart**
**Sebelum:**
```php
$_SESSION['cart'] = [];
// Hanya clear session
```

**Sesudah:**
```php
$_SESSION['cart'] = [];

// Clear from database (PERSISTENT CART) ✅
clearCartFromDatabase($userId);
```

---

### 4️⃣ Update `customer/payment.php`

**Sebelum:**
```php
// Clear cart
unset($_SESSION['cart']);
```

**Sesudah:**
```php
// Clear cart and discount
unset($_SESSION['cart']);
unset($_SESSION['discount_amount']);
unset($_SESSION['discount_code']);

// Clear cart from database (PERSISTENT CART) ✅
clearCartFromDatabase($userId);
```

**Penting:** Cart di-clear HANYA setelah checkout berhasil!

---

## 🧪 CARA TESTING

### Test 1: Cart Persistent Saat Logout/Login

**Langkah:**
```
1. Login sebagai customer
2. Buka Menu → Tambahkan 5 produk berbeda ke cart
3. Lihat badge cart: [5] ✅
4. Buka halaman Cart → Semua 5 produk ada ✅
5. Logout
6. Login kembali dengan akun yang sama
7. ✅ Expected: Badge cart [5] (tidak hilang!)
8. Buka halaman Cart
9. ✅ Expected: Semua 5 produk masih ada!
```

---

### Test 2: Cart Update Tersimpan

**Langkah:**
```
1. Login → Tambah 3 produk ke cart
2. Ubah quantity produk pertama dari 1 → 5
3. Badge cart berubah: [7] (1+1+5)
4. Logout
5. Login kembali
6. ✅ Expected: Badge cart [7]
7. ✅ Expected: Quantity produk pertama tetap 5
```

---

### Test 3: Remove Item Persistent

**Langkah:**
```
1. Login → Cart punya 5 produk
2. Hapus 2 produk
3. Cart sekarang: 3 produk
4. Logout
5. Login kembali
6. ✅ Expected: Cart tetap 3 produk
7. ✅ Expected: 2 produk yang dihapus tidak muncul lagi
```

---

### Test 4: Clear Cart Saat Checkout

**Langkah:**
```
1. Login → Tambah 3 produk ke cart
2. Checkout → Payment → Buat order
3. Order berhasil dibuat
4. ✅ Expected: Cart otomatis kosong [0]
5. Logout → Login kembali
6. ✅ Expected: Cart tetap kosong (tidak muncul lagi)
```

---

### Test 5: Multi-Device/Browser

**Langkah:**
```
1. Browser 1 (Chrome): Login user A → Tambah 3 produk
2. Browser 2 (Firefox): Login user A (akun sama)
3. ✅ Expected: Browser 2 langsung punya 3 produk di cart
4. Browser 2: Tambah 2 produk lagi
5. Browser 1: Refresh halaman
6. ✅ Expected: Browser 1 sekarang punya 5 produk
```

**Catatan:** Perlu refresh manual karena tidak ada websocket real-time sync.

---

### Test 6: Cart Merge (Browse Tanpa Login)

**Langkah:**
```
1. Logout (belum login)
2. Browse Menu → Tambah 2 produk ke cart (anonymous cart)
3. Login dengan akun yang sudah punya 3 produk di database
4. ✅ Expected: Cart sekarang punya 5 produk (3 lama + 2 baru merge!)
```

---

## 🔧 TROUBLESHOOTING

### Problem 1: Cart Masih Hilang Saat Logout

**Cek:**
```sql
-- Cek apakah tabel cart_items ada
SHOW TABLES LIKE 'cart_items';

-- Jika tidak ada, buat tabel
SOURCE c:/laragon/www/DailyCup/database/cart_table.sql;
```

---

### Problem 2: Error "Table cart_items doesn't exist"

**Solusi:**
1. Buka phpMyAdmin
2. Pilih database `dailycup_db`
3. Klik tab "SQL"
4. Copy-paste isi file `database/cart_table.sql`
5. Klik "Go"

---

### Problem 3: Cart Tidak Load Saat Login

**Debug:**
```php
// Tambahkan di auth/login.php setelah syncCartToDatabase()
error_log("Cart loaded: " . print_r($_SESSION['cart'], true));

// Cek log file atau console
```

**Cek manual di database:**
```sql
SELECT * FROM cart_items WHERE user_id = YOUR_USER_ID;
```

---

### Problem 4: Duplicate Items di Cart

**Penyebab:** `cart_key` tidak unique

**Cek:**
```sql
SELECT user_id, cart_key, COUNT(*) as cnt 
FROM cart_items 
GROUP BY user_id, cart_key 
HAVING cnt > 1;
```

**Fix:**
```sql
-- Hapus duplicate (keep only latest)
DELETE c1 FROM cart_items c1
INNER JOIN cart_items c2 
WHERE c1.user_id = c2.user_id 
  AND c1.cart_key = c2.cart_key 
  AND c1.id < c2.id;
```

---

## 📊 PERBANDINGAN: SEBELUM vs SESUDAH

### SEBELUM (Session-Only Cart):

| Situasi | Hasil |
|---------|-------|
| Logout | ❌ Cart hilang |
| Browser crash | ❌ Cart hilang |
| Session expire | ❌ Cart hilang |
| Multi-device | ❌ Cart tidak sync |
| Long shopping | ❌ Harus re-add items |

**User Experience:** 😤 Sangat buruk!

---

### SESUDAH (Persistent Cart):

| Situasi | Hasil |
|---------|-------|
| Logout | ✅ Cart tetap ada |
| Browser crash | ✅ Cart tetap ada |
| Session expire | ✅ Cart tetap ada |
| Multi-device | ✅ Cart sync (after refresh) |
| Long shopping | ✅ Cart saved for days |

**User Experience:** 😊 Jauh lebih baik!

---

## 📁 FILE YANG DIUBAH

| File | Status | Perubahan |
|------|--------|-----------|
| `database/cart_table.sql` | ✨ Created | Schema tabel cart_items |
| `includes/functions.php` | ✏️ Modified | Tambah 6 fungsi persistent cart |
| `auth/login.php` | ✏️ Modified | Load cart saat login |
| `api/cart.php` | ✏️ Modified | Save/update/remove ke database |
| `customer/payment.php` | ✏️ Modified | Clear cart dari database setelah checkout |

---

## 🎯 BENEFIT UNTUK USER

### 1. **Tidak Perlu Re-add Items**
User tidak harus mengingat dan memasukkan ulang produk setelah logout.

### 2. **Shopping Flexibility**
User bisa:
- Browse hari ini → Checkout besok
- Logout/login berkali-kali → Cart tetap ada
- Ganti device → Cart tetap sync

### 3. **Better Conversion Rate**
Mengurangi abandoned cart karena user terpaksa logout.

### 4. **Professional Experience**
Seperti e-commerce besar (Tokopedia, Shopee, Amazon).

---

## ⚡ PERFORMANCE CONSIDERATIONS

### Query Optimization:

**Indexing:**
```sql
INDEX idx_user (user_id)           -- Fast lookup by user
INDEX idx_cart_key (cart_key)       -- Fast lookup by cart_key
UNIQUE KEY (user_id, cart_key)      -- Prevent duplicates
```

**Query Count Per Action:**
- Login: 1 SELECT (load cart)
- Add item: 1 SELECT + 1 INSERT/UPDATE
- Update quantity: 1 UPDATE
- Remove item: 1 DELETE
- Checkout: 1 DELETE

**Total:** Minimal database calls, efficient!

---

## 🔐 SECURITY CONSIDERATIONS

### 1. **User Isolation**
```php
WHERE user_id = ?  // Always filter by user_id
```
User hanya bisa akses cart miliknya sendiri.

### 2. **SQL Injection Protection**
```php
$stmt = $db->prepare("..."); // Prepared statements
$stmt->execute([$userId, ...]); // Parameterized
```

### 3. **Data Validation**
```php
$userId = intval($_SESSION['user_id']);
$quantity = intval($data['quantity']);
```

---

## 📚 DOKUMENTASI LENGKAP

File dokumentasi ini tersimpan di:  
📄 `docs/PERSISTENT_CART_IMPLEMENTATION.md`

---

## ✅ STATUS IMPLEMENTASI

**SEBELUM:**
- ❌ Cart hilang saat logout
- ❌ User harus re-add items
- ❌ Poor user experience
- ❌ High cart abandonment

**SESUDAH:**
- ✅ Cart persistent (tidak hilang)
- ✅ User bisa logout/login kapan saja
- ✅ Cart saved to database
- ✅ Better user experience
- ✅ Professional e-commerce feature

---

**Tanggal Implementasi:** 7 Januari 2026  
**Feature Type:** Cart Persistence  
**Impact:** High (Major UX Improvement)  
**Status:** ✅ IMPLEMENTED & READY TO TEST
