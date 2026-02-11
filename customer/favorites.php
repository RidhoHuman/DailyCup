<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Favorit Saya';
requireLogin();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = getDB();
$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM favorites f 
                      JOIN products p ON f.product_id = p.id 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE f.user_id = ? AND p.is_active = 1 
                      ORDER BY f.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$favorites = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-heart-fill text-danger"></i> Favorit Saya</h2>
    
    <?php if (count($favorites) > 0): ?>
    <div class="row g-4">
        <?php foreach ($favorites as $product): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card product-card h-100">
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
                    <p class="product-description text-truncate-3">
                        <?php echo htmlspecialchars($product['description']); ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="product-price"><?php echo formatCurrency($product['base_price']); ?></div>
                        <div class="btn-group">
                            <button class="btn btn-outline-danger btn-sm favorite-icon active" 
                                    onclick="window.DailyCup.toggleFavorite(<?php echo $product['id']; ?>, this)">
                                <i class="bi bi-heart-fill"></i>
                            </button>
                            <a href="<?php echo SITE_URL; ?>/customer/product_detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-coffee btn-sm">
                                <i class="bi bi-cart-plus"></i> Pesan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <i class="bi bi-heart" style="font-size: 4rem; color: #ccc;"></i>
        <h4 class="mt-3">Belum Ada Favorit</h4>
        <p class="text-muted">Tambahkan produk ke favorit untuk akses cepat</p>
        <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-coffee mt-3">Lihat Menu</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
