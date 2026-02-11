<?php
$cartCount = 0;
if (isLoggedIn() && isset($_SESSION['cart'])) {
    $cartCount = getCartCount();
}

$unreadCount = 0;
if (isLoggedIn()) {
    $unreadCount = getUnreadNotificationCount($_SESSION['user_id']);
}
?>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-coffee sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
            <i class="bi bi-cup-hot-fill me-2 fs-4"></i>
            <span class="fw-bold">DailyCup</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/customer/menu.php">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php#about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/customer/contact.php">
                        <i class="bi bi-headset"></i> Customer Service
                    </a>
                </li>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Cart Icon -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/customer/cart.php">
                            <i class="bi bi-cart3 fs-5"></i>
                            <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                                  style="<?php echo $cartCount > 0 ? 'display: inline-block;' : 'display: none;'; ?>">
                                <?php echo $cartCount; ?>
                            </span>
                        </a>
                    </li>
                    
                    <!-- Notification Icon -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/customer/notifications.php">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="notification-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  style="<?php echo $unreadCount > 0 ? 'display: inline-block;' : 'display: none;'; ?>">
                                <?php echo $unreadCount; ?>
                            </span>
                        </a>
                    </li>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/customer/profile.php">
                                <i class="bi bi-person"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/customer/orders.php">
                                <i class="bi bi-bag"></i> Pesanan Saya
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/customer/favorites.php">
                                <i class="bi bi-heart"></i> Favorit
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/customer/loyalty_points.php">
                                <i class="bi bi-star"></i> Loyalty Points
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/customer/tickets.php">
                                <i class="bi bi-ticket-detailed"></i> My Tickets
                            </a></li>
                            <?php if (isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/index.php">
                                <i class="bi bi-speedometer2"></i> Admin Panel
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-2" href="<?php echo SITE_URL; ?>/auth/register.php">Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
