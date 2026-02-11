<?php
$pageTitle = 'Menu';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = getDB();

// Get filter parameters
$categoryId = $_GET['category'] ?? null;
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Build query
$sql = "SELECT p.*, c.name as category_name FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1";
$params = [];

if ($categoryId) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total
$countSql = str_replace("SELECT p.*, c.name as category_name", "SELECT COUNT(*)", $sql);
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalProducts = $stmt->fetchColumn();

// Pagination
$pagination = getPagination($totalProducts, $page);

// Get products
$sql .= " ORDER BY p.is_featured DESC, p.name ASC LIMIT ? OFFSET ?";
$params[] = $pagination['items_per_page'];
$params[] = $pagination['offset'];

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$categories = getCategories();
?>

<?php if (isset($_SESSION['success'])): ?>
<div class="container mt-3">
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['info'])): ?>
<div class="container mt-3">
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0"><i class="bi bi-filter"></i> Filter</h5>
                </div>
                <div class="card-body">
                    <!-- Search -->
                    <form method="GET" action="" class="mb-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-coffee btn-sm w-100 mt-2">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </form>
                    
                    <!-- Categories -->
                    <h6 class="fw-bold mb-3">Kategori</h6>
                    <div class="list-group">
                        <a href="<?php echo SITE_URL; ?>/customer/menu.php" 
                           class="list-group-item list-group-item-action <?php echo !$categoryId ? 'active' : ''; ?>">
                            Semua Produk
                        </a>
                        <?php foreach ($categories as $category): ?>
                        <a href="<?php echo SITE_URL; ?>/customer/menu.php?category=<?php echo $category['id']; ?>" 
                           class="list-group-item list-group-item-action <?php echo $categoryId == $category['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0 fw-bold">Menu Kami</h2>
                    <p class="text-muted mb-0">Temukan kopi terbaik untuk harimu</p>
                </div>
                <span class="badge bg-light text-dark border p-2"><?php echo $totalProducts; ?> Produk Tersedia</span>
            </div>
            
            <?php if (count($products) > 0): ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4 fade-in">
                    <div class="card product-card h-100 border-0 shadow-sm-hover">
                        <?php if ($product['is_featured']): ?>
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-warning text-dark rounded-pill px-3">
                                <i class="bi bi-star-fill"></i> Terlaris
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-img-container position-relative">
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
                            <?php if ($product['stock'] <= 0): ?>
                            <div class="stock-overlay">
                                <span class="badge bg-danger">Habis</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-light text-coffee border border-coffee-subtle"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            <h5 class="product-title mb-2"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-description text-muted small mb-3 flex-grow-1">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="product-price text-coffee fw-bold"><?php echo formatCurrency($product['base_price']); ?></div>
                                    <div class="btn-group">
                                        <?php if (isLoggedIn()): ?>
                                        <button class="btn btn-outline-danger btn-sm rounded-circle me-2 favorite-icon" 
                                                onclick="window.DailyCup.toggleFavorite(<?php echo $product['id']; ?>, this)"
                                                title="Tambah ke Favorit">
                                            <i class="bi bi-heart"></i>
                                        </button>
                                        <?php endif; ?>
                                        <a href="<?php echo SITE_URL; ?>/customer/product_detail.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-coffee btn-sm rounded-pill px-3 <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>">
                                            <i class="bi bi-cart-plus"></i> <?php echo $product['stock'] <= 0 ? 'Habis' : 'Pesan'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                            Previous
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $pagination['total_pages'] ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3 text-muted">Produk tidak ditemukan</h4>
                <p class="text-muted">Coba kata kunci lain atau lihat semua produk</p>
                <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-coffee">Lihat Semua</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Personalized Recommendations -->
    <div class="mt-5" id="recommendationsSection">
        <h3 class="mb-4 text-center"><i class="bi bi-stars"></i> <span id="recommendationsTitle">Recommended For You</span></h3>
        <div class="row g-4" id="recommendedProducts">
            <!-- Loading skeleton -->
            <?php for($i = 0; $i < 4; $i++): ?>
            <div class="col-md-6 col-lg-3 recommendation-skeleton">
                <div class="card h-100">
                    <div class="placeholder-glow">
                        <div class="placeholder col-12" style="height: 200px;"></div>
                    </div>
                    <div class="card-body placeholder-glow">
                        <div class="placeholder col-6 mb-2"></div>
                        <div class="placeholder col-12 mb-2"></div>
                        <div class="placeholder col-8"></div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
// Load personalized recommendations
document.addEventListener('DOMContentLoaded', function() {
    loadPersonalizedRecommendations();
});

function loadPersonalizedRecommendations() {
    const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
    const type = userId ? 'personalized' : 'trending';
    const title = userId ? 'Recommended For You' : 'Trending Now';
    
    let url = `<?php echo SITE_URL; ?>/webapp/backend/api/recommendations.php?type=${type}&limit=4`;
    if (userId) {
        url += `&user_id=${userId}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('recommendationsTitle').textContent = title;
                displayMenuRecommendations(data.recommendations);
            }
        })
        .catch(error => {
            console.error('Error loading recommendations:', error);
            document.getElementById('recommendationsSection').style.display = 'none';
        });
}

function displayMenuRecommendations(products) {
    const container = document.getElementById('recommendedProducts');
    
    // Remove skeleton
    document.querySelectorAll('.recommendation-skeleton').forEach(el => el.remove());
    
    if (!products || products.length === 0) {
        document.getElementById('recommendationsSection').style.display = 'none';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="col-md-6 col-lg-3">
            <div class="card product-card h-100 shadow-sm">
                <div class="position-relative product-image-container">
                    <img src="${product.image || '<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'}" 
                         class="card-img-top product-image" 
                         alt="${product.name}">
                    ${product.reason ? `
                        <span class="badge bg-primary position-absolute top-0 end-0 m-2">
                            ${product.reason}
                        </span>
                    ` : ''}
                    ${product.avg_rating > 0 ? `
                        <div class="position-absolute bottom-0 start-0 m-2 bg-white bg-opacity-75 rounded px-2 py-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span class="fw-bold">${product.avg_rating.toFixed(1)}</span>
                            <small class="text-muted">(${product.review_count})</small>
                        </div>
                    ` : ''}
                </div>
                <div class="card-body d-flex flex-column">
                    <div class="mb-2">
                        <span class="badge bg-secondary text-white">${product.category}</span>
                    </div>
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text text-muted small flex-grow-1">${product.description.substring(0, 80)}...</p>
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="h5 mb-0 text-coffee fw-bold">${formatMenuCurrency(product.price)}</span>
                            ${product.stock > 0 ? 
                                '<span class="badge bg-success">In Stock</span>' :
                                '<span class="badge bg-danger">Out of Stock</span>'
                            }
                        </div>
                        <div class="d-grid gap-2">
                            ${product.stock > 0 ? `
                                <button class="btn btn-coffee btn-sm" onclick="quickAddFromRecommendation(${product.id}, '${product.name}', ${product.price}, '${product.image}')">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            ` : `
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="bi bi-x-circle"></i> Out of Stock
                                </button>
                            `}
                            <a href="<?php echo SITE_URL; ?>/customer/product_detail.php?id=${product.id}" class="btn btn-outline-coffee btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function quickAddFromRecommendation(productId, productName, productPrice, productImage) {
    addToCart(productId, {
        name: productName,
        price: productPrice,
        image: productImage
    }, 1);
    
    // Show success notification
    showNotification('success', `${productName} added to cart!`);
}

function formatMenuCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function showNotification(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
}
.product-image-container {
    overflow: hidden;
    height: 200px;
}
.product-image {
    height: 100%;
    width: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.product-card:hover .product-image {
    transform: scale(1.1);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
