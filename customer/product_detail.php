<?php
$pageTitle = 'Product Detail';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$productId = intval($_GET['id'] ?? 0);
if (!$productId) {
    header('Location: ' . SITE_URL . '/customer/menu.php');
    exit;
}

$db = getDB();
$product = getProduct($productId);

if (!$product || !$product['is_active']) {
    header('Location: ' . SITE_URL . '/customer/menu.php');
    exit;
}

// Get variants
$sizes = getProductVariants($productId, 'size');
$temperatures = getProductVariants($productId, 'temperature');

// Get reviews
$stmt = $db->prepare("SELECT r.*, u.name as user_name FROM reviews r 
                     JOIN users u ON r.user_id = u.id 
                     WHERE r.product_id = ? AND r.is_approved = 1 
                     ORDER BY r.created_at DESC LIMIT 10");
$stmt->execute([$productId]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                     FROM reviews WHERE product_id = ? AND is_approved = 1");
$stmt->execute([$productId]);
$ratingData = $stmt->fetch();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6 mb-4">
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
                 class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/customer/menu.php">Menu</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
            
            <h2 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
            
            <div class="mb-3">
                <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
            </div>
            
            <div class="mb-3">
                <div class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $avgRating): ?>
                        <i class="bi bi-star-fill"></i>
                        <?php else: ?>
                        <i class="bi bi-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <span class="ms-2"><?php echo $avgRating; ?> (<?php echo $totalReviews; ?> reviews)</span>
                </div>
            </div>
            
            <h3 class="text-coffee mb-4"><?php echo formatCurrency($product['base_price']); ?></h3>
            
            <p class="mb-4"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
            <?php if (isLoggedIn()): ?>
            <form id="addToCartForm">
                <?php if (count($sizes) > 0): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Size</label>
                    <select id="size-<?php echo $productId; ?>" class="form-select" required>
                        <?php foreach ($sizes as $size): ?>
                        <option value="<?php echo htmlspecialchars($size['variant_value']); ?>" 
                                data-price="<?php echo $size['price_adjustment']; ?>">
                            <?php echo htmlspecialchars($size['variant_value']); ?>
                            <?php if ($size['price_adjustment'] > 0): ?>
                            (+<?php echo formatCurrency($size['price_adjustment']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if (count($temperatures) > 0): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Temperature</label>
                    <select id="temperature-<?php echo $productId; ?>" class="form-select" required>
                        <?php foreach ($temperatures as $temp): ?>
                        <option value="<?php echo htmlspecialchars($temp['variant_value']); ?>" 
                                data-price="<?php echo $temp['price_adjustment']; ?>">
                            <?php echo htmlspecialchars($temp['variant_value']); ?>
                            <?php if ($temp['price_adjustment'] > 0): ?>
                            (+<?php echo formatCurrency($temp['price_adjustment']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity</label>
                    <div class="quantity-input">
                        <button type="button" onclick="changeQuantity(-1)">-</button>
                        <input type="number" id="quantity-<?php echo $productId; ?>" value="1" min="1" max="99">
                        <button type="button" onclick="changeQuantity(1)">+</button>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-coffee btn-lg add-to-cart-btn" 
                            data-product-id="<?php echo $productId; ?>"
                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                            data-price="<?php echo $product['base_price']; ?>">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                    <button type="button" class="btn btn-outline-danger" 
                            onclick="window.DailyCup.toggleFavorite(<?php echo $productId; ?>, this)">
                        <i class="bi bi-heart"></i> Add to Favorites
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="alert alert-info">
                <a href="<?php echo SITE_URL; ?>/auth/login.php">Login</a> to order this product
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="mt-5">
        <h3 class="mb-4">Customer Reviews</h3>
        
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                    <i class="bi bi-star-fill"></i>
                                    <?php else: ?>
                                    <i class="bi bi-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <small class="text-muted"><?php echo timeAgo($review['created_at']); ?></small>
                    </div>
                    <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
        <?php endif; ?>
    </div>
</div>

<script>
function changeQuantity(delta) {
    const input = document.getElementById('quantity-<?php echo $productId; ?>');
    let value = parseInt(input.value) + delta;
    if (value < 1) value = 1;
    if (value > 99) value = 99;
    input.value = value;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
