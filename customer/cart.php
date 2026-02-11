<?php
$pageTitle = 'Keranjang Belanja';
require_once __DIR__ . '/../includes/header.php';

requireLogin();
require_once __DIR__ . '/../includes/navbar.php';

$cart = $_SESSION['cart'] ?? [];
$discountAmount = $_SESSION['discount_amount'] ?? 0;

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$total = $subtotal - $discountAmount;
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-cart3"></i> Keranjang Belanja</h2>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body" id="cartItems">
                    <!-- Cart items will be loaded by JavaScript -->
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-coffee text-white">
                    <h5 class="mb-0">Ringkasan Belanja</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span class="cart-subtotal"><?php echo formatCurrency($subtotal); ?></span>
                    </div>
                    
                    <?php if ($discountAmount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Diskon:</span>
                        <span>-<?php echo formatCurrency($discountAmount); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="cart-total text-coffee"><?php echo formatCurrency($total); ?></strong>
                    </div>
                    
                    <!-- Discount Code -->
                    <div class="mb-3">
                        <label class="form-label">Kode Diskon</label>
                        <div class="input-group">
                            <input type="text" id="discountCode" class="form-control" placeholder="Masukkan kode">
                            <button class="btn btn-outline-coffee" onclick="applyDiscountCode(document.getElementById('discountCode').value)">
                                Gunakan
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/customer/checkout.php" class="btn btn-coffee btn-lg">
                            <i class="bi bi-credit-card"></i> Checkout
                        </a>
                        <a href="<?php echo SITE_URL; ?>/customer/menu.php" class="btn btn-outline-coffee">
                            <i class="bi bi-arrow-left"></i> Lanjut Belanja
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Recommendations -->
    <div class="mt-5" id="recommendations">
        <h3 class="mb-4"><i class="bi bi-stars"></i> You May Also Like</h3>
        <div class="row g-4" id="recommendedProducts">
            <!-- Recommendations will be loaded here -->
        </div>
    </div>
</div>

<script>
// Load cart on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();
    loadRecommendations();
});

// Load product recommendations based on cart items
function loadRecommendations() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
    if (cart.length === 0) {
        // Show trending products if cart is empty
        fetch('<?php echo SITE_URL; ?>/webapp/backend/api/recommendations.php?type=trending&limit=4')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecommendations(data.recommendations);
                }
            })
            .catch(error => console.error('Error loading recommendations:', error));
    } else {
        // Show complementary products based on cart
        const cartItems = cart.map(item => ({ product_id: item.id }));
        fetch(`<?php echo SITE_URL; ?>/webapp/backend/api/recommendations.php?type=cart&limit=4&cart_items=${encodeURIComponent(JSON.stringify(cartItems))}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayRecommendations(data.recommendations);
                }
            })
            .catch(error => console.error('Error loading recommendations:', error));
    }
}

// Display recommendations in the page
function displayRecommendations(products) {
    const container = document.getElementById('recommendedProducts');
    
    if (!products || products.length === 0) {
        document.getElementById('recommendations').style.display = 'none';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm hover-shadow">
                <div class="position-relative">
                    <img src="${product.image || '<?php echo SITE_URL; ?>/assets/images/placeholder.jpg'}" 
                         class="card-img-top" 
                         alt="${product.name}"
                         style="height: 200px; object-fit: cover;">
                    ${product.reason ? `
                        <span class="badge bg-primary position-absolute top-0 end-0 m-2">
                            ${product.reason}
                        </span>
                    ` : ''}
                    ${product.avg_rating > 0 ? `
                        <div class="position-absolute bottom-0 start-0 m-2 bg-white rounded px-2 py-1">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span class="fw-bold">${product.avg_rating.toFixed(1)}</span>
                            <small class="text-muted">(${product.review_count})</small>
                        </div>
                    ` : ''}
                </div>
                <div class="card-body">
                    <span class="badge bg-secondary mb-2">${product.category}</span>
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text text-muted small">${product.description.substring(0, 80)}...</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0 text-coffee">${formatCurrency(product.price)}</span>
                        ${product.stock > 0 ? 
                            '<button class="btn btn-sm btn-coffee" onclick="quickAddToCart(' + product.id + ', \'' + product.name + '\', ' + product.price + ', \'' + product.image + '\')">Add to Cart</button>' :
                            '<span class="text-danger small">Out of Stock</span>'
                        }
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Quick add to cart from recommendations
function quickAddToCart(productId, productName, productPrice, productImage) {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    loadRecommendations(); // Refresh recommendations
    
    // Show success notification
    showNotification('success', `${productName} added to cart!`);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function showNotification(type, message) {
    // Bootstrap toast or alert
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
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
