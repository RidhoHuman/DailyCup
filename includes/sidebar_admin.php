<!-- Admin Sidebar (Desktop Only) -->
<div class="admin-sidebar bg-dark text-white" id="adminSidebar">
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <h5 class="mb-0">
            <i class="bi bi-speedometer2"></i>
            Admin Panel
        </h5>
    </div>
    
    <div class="sidebar-menu p-3">
        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/index.php">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
            </li>

            <li class="nav-item mb-2">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/analytics.php">
                    <i class="bi bi-graph-up-arrow"></i> Enterprise Analytics
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/products/">
                    <i class="bi bi-cup-straw"></i> Products
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/categories/">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/orders/">
                    <i class="bi bi-bag"></i> Orders
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/reviews/">
                    <i class="bi bi-star"></i> Reviews
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/returns/">
                    <i class="bi bi-arrow-return-left"></i> Returns
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/kurir/">
                    <i class="bi bi-bicycle"></i> Kurir
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/kurir/monitor.php">
                    <i class="bi bi-broadcast-pin"></i> Live Monitor
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/cs/">
                    <i class="bi bi-headset"></i> Customer Service
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/discounts/">
                    <i class="bi bi-percent"></i> Discounts
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/redeem_codes/">
                    <i class="bi bi-ticket-perforated"></i> Redeem Codes
                </a>
            </li>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/loyalty/">
                    <i class="bi bi-gift"></i> Loyalty Settings
                </a>
            </li>
            
            <?php if (isSuperAdmin()): ?>
            <li class="nav-item mb-2">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/admin/users/">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <?php endif; ?>
            
            <li class="nav-item mb-2">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'gdpr_requests.php' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/gdpr_requests.php">
                    <i class="bi bi-shield-check"></i> GDPR Requests
                </a>
            </li>

            <li class="nav-item mb-2 border-bottom border-secondary pb-3 mb-3">
                <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'performance.php' ? 'active' : ''; ?>" 
                   href="<?php echo SITE_URL; ?>/admin/performance.php">
                    <i class="bi bi-speedometer"></i> Performance
                </a>
            </li>

            <li class="nav-item mb-2 mt-4">
                <a class="nav-link text-white" href="<?php echo SITE_URL; ?>/customer/index.php">
                    <i class="bi bi-arrow-left"></i> Back to Store
                </a>
            </li>
        </ul>
    </div>
</div>
