    <!-- Footer -->
    <footer class="footer bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="text-uppercase mb-3">DailyCup Coffee Shop</h5>
                    <p>Nikmati pengalaman kopi terbaik dengan menu pilihan dari biji kopi berkualitas premium.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4">
                    <h6 class="text-uppercase mb-3">Menu</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/customer/menu.php" class="text-white-50">Coffee</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/customer/menu.php" class="text-white-50">Non-Coffee</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/customer/menu.php" class="text-white-50">Snacks</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/customer/menu.php" class="text-white-50">Desserts</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="text-uppercase mb-3">Layanan</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/customer/orders.php" class="text-white-50">Pesanan Saya</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/customer/favorites.php" class="text-white-50">Favorit</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/customer/loyalty_points.php" class="text-white-50">Loyalty Points</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/kurir/info.php" class="text-white-50"><i class="bi bi-bicycle"></i> Bergabung Jadi Kurir</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/index.php#about" class="text-white-50">Tentang Kami</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h6 class="text-uppercase mb-3">Kontak</h6>
                    <ul class="list-unstyled">
                        <li class="text-white-50"><i class="bi bi-geo-alt"></i> Jakarta, Indonesia</li>
                        <li class="text-white-50"><i class="bi bi-telephone"></i> +62 812-3456-7890</li>
                        <li class="text-white-50"><i class="bi bi-envelope"></i> info@dailycup.com</li>
                    </ul>
                </div>
            </div>
            <hr class="bg-white">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> DailyCup Coffee Shop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript - Load in correct order -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/cart.js"></script>
    
    <?php if (isLoggedIn()): ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/notification.js"></script>
    <?php endif; ?>
    
    <!-- Customer Service Widget (only for customer pages) -->
    <?php if (!isset($isAdminPage) && !isset($isKurirPage)): ?>
    <?php include __DIR__ . '/cs_widget.php'; ?>
    <?php endif; ?>
    
    <!-- Admin Mobile Navigation -->
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
    <!-- Mobile Top Navbar -->
    <div class="admin-mobile-navbar">
        <div class="navbar-brand">
            <i class="bi bi-speedometer2"></i>
            <span>Admin Panel</span>
        </div>
        <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn btn-sm btn-light">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
    
    <!-- Mobile Bottom Navigation -->
    <div class="admin-bottom-nav">
        <div class="nav-container">
            <a href="<?php echo SITE_URL; ?>/admin/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : ''; ?>">
                <i class="bi bi-house-door-fill"></i>
                <span>Home</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/orders/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/orders') !== false ? 'active' : ''; ?>">
                <i class="bi bi-bag-fill"></i>
                <span>Orders</span>
                <?php 
                $db = getDB();
                $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
                $pendingCount = $stmt->fetchColumn();
                if ($pendingCount > 0): ?>
                <span class="badge-notification"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/kurir/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/kurir') !== false ? 'active' : ''; ?>">
                <i class="bi bi-bicycle"></i>
                <span>Kurir</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/products/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/products') !== false ? 'active' : ''; ?>">
                <i class="bi bi-cup-straw-fill"></i>
                <span>Products</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/admin/users/" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/discounts') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/loyalty') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/reviews') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/returns') !== false ? 'active' : ''; ?>">
                <i class="bi bi-three-dots"></i>
                <span>More</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
    
</body>
</html>
