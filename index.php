<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Get featured products
$featuredProducts = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT p.*, c.name as category_name FROM products p 
                        JOIN categories c ON p.category_id = c.id 
                        WHERE p.is_featured = 1 AND p.is_active = 1 
                        LIMIT 6");
    $featuredProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database Error (Featured Products): " . $e->getMessage());
}

// Get active categories
$categories = [];
try {
    $categories = getCategories();
} catch (Exception $e) {
    error_log("Database Error (Categories): " . $e->getMessage());
}

// Get partner discounts
$partnerDiscounts = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM partner_discounts WHERE is_active = 1 AND end_date >= NOW() LIMIT 3");
    $partnerDiscounts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Database Error (Partner Discounts): " . $e->getMessage());
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-3 fw-bold mb-4 fade-in">
                    Nikmati Kopi Terbaik<br>
                    <span class="text-secondary">Setiap Hari</span>
                </h1>
                <p class="lead mb-4">
                    DailyCup menghadirkan pengalaman kopi premium dengan biji kopi pilihan berkualitas tinggi. Pesan sekarang dan dapatkan poin loyalitas!
                </p>
                <div class="d-flex gap-3">
                    <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-light btn-lg">
                        <i class="bi bi-cup-hot"></i> Lihat Menu
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-outline-light btn-lg">
                        Daftar Sekarang
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center mt-5 mt-lg-0">
                <i class="bi bi-cup-hot-fill" style="font-size: 15rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section-padding">
    <div class="container">
        <h2 class="section-title">Kategori Menu</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-6 col-lg-3">
                <a href="<?php echo SITE_URL; ?>/customer/menu.php?category=<?php echo $category['id']; ?>" 
                   class="text-decoration-none">
                    <div class="card product-card text-center">
                        <div class="card-body p-4">
                            <i class="bi bi-cup-straw text-coffee" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 mb-2"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="text-muted small">
                                <?php echo htmlspecialchars($category['description']); ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section-padding bg-white">
    <div class="container">
        <h2 class="section-title">Produk Unggulan</h2>
        <p class="section-subtitle">Produk terpopuler dan paling disukai pelanggan kami</p>
        
        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card product-card">
                    <img src="<?php 
                    $imgSrc = SITE_URL . '/assets/images/products/placeholder.jpg';
                    if (!empty($product['image'])) {
                        if (strpos($product['image'], 'uploads/') !== false) {
                            $imgSrc = SITE_URL . '/webapp/' . ltrim($product['image'], '/');
                        } else {
                            $imgSrc = SITE_URL . '/assets/images/products/' . $product['image'];
                        }
                    }
                    echo $imgSrc;
                    ?>" 
                         class="product-card-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-card-body">
                        <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="product-description text-truncate-2">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="product-price"><?php echo formatCurrency($product['base_price']); ?></div>
                            <a href="<?php echo SITE_URL; ?>/customer/product_detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-coffee btn-sm">
                                <i class="bi bi-cart-plus"></i> Pesan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-outline-coffee btn-lg">
                Lihat Semua Menu <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Partner Discounts Section -->
<?php if (count($partnerDiscounts) > 0): ?>
<section class="section-padding">
    <div class="container">
        <h2 class="section-title">Diskon Partner</h2>
        <p class="section-subtitle">Nikmati diskon spesial dari partner kami</p>
        
        <div class="row g-4">
            <?php foreach ($partnerDiscounts as $discount): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-award-fill text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($discount['partner_name']); ?></h5>
                        <h3 class="text-coffee mb-3">
                            <?php 
                            if ($discount['discount_type'] == 'percentage') {
                                echo $discount['discount_value'] . '%';
                            } else {
                                echo formatCurrency($discount['discount_value']);
                            }
                            ?>
                            <small class="d-block fs-6 text-muted">OFF</small>
                        </h3>
                        <p class="text-muted"><?php echo htmlspecialchars($discount['description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="section-padding bg-white">
    <div class="container">
        <h2 class="section-title">Kenapa DailyCup?</h2>
        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-4">
                    <i class="bi bi-cup-hot text-coffee" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Kopi Premium</h5>
                    <p class="text-muted">Biji kopi berkualitas tinggi dari petani lokal terpilih</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4">
                    <i class="bi bi-truck text-coffee" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Pengiriman Cepat</h5>
                    <p class="text-muted">Pesanan diantar dengan cepat dan aman ke lokasi Anda</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4">
                    <i class="bi bi-star text-coffee" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Loyalty Points</h5>
                    <p class="text-muted">Kumpulkan poin dan tukar dengan diskon menarik</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4">
                    <i class="bi bi-headset text-coffee" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Layanan Terbaik</h5>
                    <p class="text-muted">Tim kami siap melayani dengan ramah dan profesional</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="section-padding">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-4">Tentang DailyCup</h2>
                <p class="lead mb-3">
                    DailyCup adalah coffee shop yang berdedikasi untuk menyajikan kopi berkualitas tinggi dengan pelayanan terbaik.
                </p>
                <p class="text-muted mb-3">
                    Kami percaya bahwa setiap cangkir kopi adalah sebuah pengalaman yang istimewa. Dengan biji kopi pilihan dari berbagai daerah di Indonesia, kami berkomitmen memberikan cita rasa yang autentik dan konsisten.
                </p>
                <p class="text-muted">
                    Visi kami adalah menjadi coffee shop pilihan utama untuk semua pecinta kopi, dengan menyediakan produk berkualitas, layanan excellent, dan pengalaman berbelanja yang menyenangkan.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card text-center p-4 bg-coffee text-white">
                            <h2 class="fw-bold mb-0">1000+</h2>
                            <p class="mb-0">Pelanggan Setia</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card text-center p-4 bg-coffee text-white">
                            <h2 class="fw-bold mb-0">50+</h2>
                            <p class="mb-0">Varian Menu</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card text-center p-4 bg-coffee text-white">
                            <h2 class="fw-bold mb-0">5★</h2>
                            <p class="mb-0">Rating Pelanggan</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card text-center p-4 bg-coffee text-white">
                            <h2 class="fw-bold mb-0">24/7</h2>
                            <p class="mb-0">Layanan Online</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Customer Service Section -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-4">
                    <i class="bi bi-headset text-coffee"></i> Butuh Bantuan?
                </h2>
                <p class="lead mb-4">
                    Tim Customer Service kami siap membantu Anda 24/7. Ada pertanyaan atau kendala? Jangan ragu untuk menghubungi kami!
                </p>
                
                <div class="mb-3">
                    <h5 class="text-coffee mb-3"><i class="bi bi-clock"></i> Jam Operasional</h5>
                    <p class="mb-1"><strong>Senin - Jumat:</strong> 08:00 - 21:00 WIB</p>
                    <p class="mb-1"><strong>Sabtu - Minggu:</strong> 09:00 - 22:00 WIB</p>
                    <p class="text-muted mb-0"><small>*Chat & Email Support tersedia 24/7</small></p>
                </div>
                
                <div class="mb-4">
                    <h5 class="text-coffee mb-3"><i class="bi bi-telephone"></i> Kontak Kami</h5>
                    <p class="mb-1"><i class="bi bi-envelope"></i> support@dailycup.com</p>
                    <p class="mb-1"><i class="bi bi-phone"></i> +62 812 3456 7890</p>
                    <p class="mb-1"><i class="bi bi-whatsapp"></i> +62 812 3456 7890</p>
                </div>
                
                <a href="<?php echo SITE_URL; ?>/customer/contact.php" class="btn btn-coffee btn-lg">
                    <i class="bi bi-chat-dots"></i> Hubungi Kami Sekarang
                </a>
                <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/customer/tickets.php" class="btn btn-outline-coffee btn-lg ms-2">
                    <i class="bi bi-ticket-detailed"></i> My Tickets
                </a>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Yang Bisa Kami Bantu:</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>Informasi Produk</strong>
                                        <p class="small text-muted mb-0">Detail menu & harga</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>Status Pesanan</strong>
                                        <p class="small text-muted mb-0">Tracking & delivery</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>Pembayaran</strong>
                                        <p class="small text-muted mb-0">Metode & konfirmasi</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>Keluhan & Saran</strong>
                                        <p class="small text-muted mb-0">Feedback pelanggan</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>Return/Refund</strong>
                                        <p class="small text-muted mb-0">Proses pengembalian</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                    <div>
                                        <strong>Loyalty Points</strong>
                                        <p class="small text-muted mb-0">Poin & reward</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!isLoggedIn()): ?>
<section class="section-padding bg-coffee text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Mulai Petualangan Kopi Anda!</h2>
        <p class="lead mb-4">Daftar sekarang dan dapatkan diskon 10% untuk pembelian pertama</p>
        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-light btn-lg">
            Daftar Gratis Sekarang <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</section>
<?php endif; ?>

<?php 
require_once __DIR__ . '/includes/footer.php';
?>
