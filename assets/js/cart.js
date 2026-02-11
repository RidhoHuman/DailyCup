/**
 * Shopping Cart JavaScript
 */

// Cart data
let cart = [];
// Reference global SITE_URL_JS from main.js (no redeclaration)

// Initialize cart from session storage
document.addEventListener('DOMContentLoaded', function() {
    const isProductPage = window.location.pathname.includes('product') || 
                         window.location.pathname.includes('menu') ||
                         window.location.pathname.includes('cart');
    
    if (isProductPage) {
        console.log('Cart.js loaded - initializing...');
    }
    
    loadCart();
    
    // Delay event initialization to ensure DOM is ready
    setTimeout(function() {
        initCartEvents();
        if (isProductPage) {
            console.log('Cart events initialized');
        }
    }, 100);
});

/**
 * Load cart from server
 */
function loadCart() {
    const SITE_URL_JS = window.SITE_URL_JS;
    const isCartRelatedPage = window.location.pathname.includes('cart') || 
                              window.location.pathname.includes('product') ||
                              window.location.pathname.includes('menu');
    
    if (isCartRelatedPage) {
        console.log('Loading cart from API...');
    }
    
    fetch(`${SITE_URL_JS}/api/cart.php?action=get`)
        .then(response => response.json())
        .then(data => {
            if (isCartRelatedPage) {
                console.log('Cart API response:', data);
            }
            if (data.success) {
                cart = data.cart || [];
                if (isCartRelatedPage) {
                    console.log('Cart loaded:', cart);
                }
                updateCartDisplay();
                
                // If we're on cart page, render it
                const cartItemsContainer = document.getElementById('cartItems');
                if (cartItemsContainer) {
                    renderCartPage(cart);
                }
            } else {
                // Sembunyikan error jika penyebabnya karena user belum login (401/403)
                // Pesan error "Silakan login" wajar muncul di halaman publik
                if (data.message && data.message.toLowerCase().includes('login')) {
                    // Silent fail for login requirement on public pages
                    if (isCartRelatedPage) {
                         // Only show error if we are specifically on a cart page
                         console.warn('User not logged in, empty cart initialized.');
                    }
                } else {
                    console.error('Failed to load cart:', data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error loading cart:', error);
        });
}

/**
 * Add item to cart
 */
function addToCart(productId, productName, price, size = null, temperature = null, quantity = 1) {
    console.log('Adding to cart:', { productId, productName, price, size, temperature, quantity });
    
    const SITE_URL_JS = window.SITE_URL_JS;
    const data = {
        action: 'add',
        product_id: productId,
        product_name: productName,
        price: price,
        size: size,
        temperature: temperature,
        quantity: quantity
    };
    
    console.log('Sending data to API:', data);
    
    fetch(`${SITE_URL_JS}/api/cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            cart = data.cart;
            updateCartDisplay();
            if (window.DailyCup && window.DailyCup.showAlert) {
                window.DailyCup.showAlert('Produk ditambahkan ke keranjang!', 'success');
            } else {
                alert('Produk ditambahkan ke keranjang!');
            }
        } else {
            console.error('Failed to add to cart:', data.message);
            if (window.DailyCup && window.DailyCup.showAlert) {
                window.DailyCup.showAlert(data.message || 'Gagal menambahkan ke keranjang', 'danger');
            } else {
                alert(data.message || 'Gagal menambahkan ke keranjang');
            }
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        if (window.DailyCup && window.DailyCup.showAlert) {
            window.DailyCup.showAlert('Terjadi kesalahan koneksi', 'danger');
        } else {
            alert('Terjadi kesalahan koneksi');
        }
    });
}

/**
 * Update cart item quantity
 */
function updateCartItem(cartKey, quantity) {
    const SITE_URL_JS = window.SITE_URL_JS;
    if (quantity < 1) {
        removeFromCart(cartKey);
        return;
    }
    
    const data = {
        action: 'update',
        cart_key: cartKey,
        quantity: quantity
    };
    
    fetch(`${SITE_URL_JS}/api/cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = data.cart;
            updateCartDisplay();
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Remove item from cart
 */
function removeFromCart(cartKey) {
    const SITE_URL_JS = window.SITE_URL_JS;
    if (!confirm('Hapus item dari keranjang?')) {
        return;
    }
    
    const data = {
        action: 'remove',
        cart_key: cartKey
    };
    
    fetch(`${SITE_URL_JS}/api/cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = data.cart;
            updateCartDisplay();
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Clear entire cart
 */
function clearCart() {
    const SITE_URL_JS = window.SITE_URL_JS;
    if (!confirm('Hapus semua item dari keranjang?')) {
        return;
    }
    
    fetch(`${SITE_URL_JS}/api/cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'clear' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = [];
            updateCartDisplay();
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Update cart display
 */
function updateCartDisplay() {
    // Update cart count in navbar
    const cartCountElements = document.querySelectorAll('.cart-count');
    
    // Ensure cart is an array
    const cartArray = Array.isArray(cart) ? cart : Object.values(cart);
    
    // Calculate total quantity instead of unique items
    let totalQuantity = 0;
    cartArray.forEach(item => {
        totalQuantity += parseInt(item.quantity) || 0;
    });
    
    cartCountElements.forEach(element => {
        element.textContent = totalQuantity;
        element.style.display = totalQuantity > 0 ? 'inline-block' : 'none';
    });
    
    // Update cart items list if on cart page
    const cartItemsContainer = document.getElementById('cartItems');
    if (cartItemsContainer) {
        renderCartPage(cartArray);
    }
}

/**
 * Render cart page content
 */
function renderCartPage(cartArray = []) {
    console.log('renderCartPage called with:', cartArray);
    const SITE_URL_JS = window.SITE_URL_JS;
    const container = document.getElementById('cartItems');
    if (!container) {
        console.log('cartItems container not found');
        return;
    }

    if (!cartArray || cartArray.length === 0) {
        console.log('Cart is empty, showing empty message');
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-cart-x" style="font-size: 4rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">Keranjang Anda kosong</p>
                <a href="${SITE_URL_JS}/customer/menu.php" class="btn btn-coffee">Belanja Sekarang</a>
            </div>
        `;
        return;
    }

    console.log('Rendering', cartArray.length, 'items');
    let html = '';
    let subtotal = 0;

    cartArray.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        subtotal += itemTotal;
        
        html += `
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2 col-4">
                            <img src="${getProductImageUrl(item.image)}" 
                                 class="img-fluid rounded" alt="${item.product_name}">
                        </div>
                        <div class="col-md-4 col-8">
                            <h5 class="mb-1">${item.product_name}</h5>
                            <p class="text-muted small mb-0">
                                ${item.size ? 'Size: ' + item.size : ''} 
                                ${item.temperature ? '| Temp: ' + item.temperature : ''}
                            </p>
                            <p class="text-coffee fw-bold mb-0">${formatCurrency(item.price)}</p>
                        </div>
                        <div class="col-md-3 col-6 mt-3 mt-md-0">
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <button class="btn btn-outline-coffee" onclick="updateCartItem(${index}, ${item.quantity - 1})">-</button>
                                <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                                <button class="btn btn-outline-coffee" onclick="updateCartItem(${index}, ${item.quantity + 1})">+</button>
                            </div>
                        </div>
                        <div class="col-md-2 col-4 mt-3 mt-md-0 text-end">
                            <span class="fw-bold">${formatCurrency(itemTotal)}</span>
                        </div>
                        <div class="col-md-1 col-2 mt-3 mt-md-0 text-end">
                            <button class="btn btn-link text-danger p-0" onclick="removeFromCart(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    console.log('Cart items rendered, subtotal:', subtotal);
    
    // Update summary if elements exist
    const subtotalElement = document.querySelector('.cart-subtotal');
    const totalElement = document.querySelector('.cart-total');
    
    if (subtotalElement) {
        subtotalElement.textContent = formatCurrency(subtotal);
    }
    
    if (totalElement) {
        totalElement.textContent = formatCurrency(subtotal);
    }
}

/**
 * Initialize cart events
 */
function initCartEvents() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    console.log('Found', addToCartButtons.length, 'add-to-cart buttons');
    
    addToCartButtons.forEach((button, index) => {
        console.log(`Setting up button ${index + 1}:`, {
            productId: button.dataset.productId,
            productName: button.dataset.productName,
            price: button.dataset.price
        });
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Button clicked!');
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            let basePrice = parseFloat(this.dataset.price);
            
            console.log('Product data:', { productId, productName, basePrice });
            
            const sizeSelect = document.getElementById('size-' + productId);
            const tempSelect = document.getElementById('temperature-' + productId);
            const quantityInput = document.getElementById('quantity-' + productId);
            
            console.log('Form elements:', {
                sizeSelect: sizeSelect ? sizeSelect.value : 'not found',
                tempSelect: tempSelect ? tempSelect.value : 'not found',
                quantityInput: quantityInput ? quantityInput.value : 'not found'
            });
            
            // Calculate adjusted price
            let adjustedPrice = basePrice;
            let size = null;
            let temperature = null;

            if (sizeSelect) {
                size = sizeSelect.value;
                const sizeOption = sizeSelect.options[sizeSelect.selectedIndex];
                const adjustment = parseFloat(sizeOption.dataset.price || 0);
                adjustedPrice += adjustment;
                console.log('Size adjustment:', adjustment, 'New price:', adjustedPrice);
            }

            if (tempSelect) {
                temperature = tempSelect.value;
                const tempOption = tempSelect.options[tempSelect.selectedIndex];
                const adjustment = parseFloat(tempOption.dataset.price || 0);
                adjustedPrice += adjustment;
                console.log('Temp adjustment:', adjustment, 'New price:', adjustedPrice);
            }

            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            if (isNaN(quantity) || quantity < 1) {
                console.error('Invalid quantity:', quantity);
                if (window.DailyCup && window.DailyCup.showAlert) {
                    window.DailyCup.showAlert('Jumlah tidak valid', 'warning');
                } else {
                    alert('Jumlah tidak valid');
                }
                return;
            }
            
            console.log('Final data before adding:', { productId, productName, adjustedPrice, size, temperature, quantity });
            addToCart(productId, productName, adjustedPrice, size, temperature, quantity);
        });
    });
    
    if (addToCartButtons.length === 0) {
        // Only log on pages that should have these buttons
        const currentPage = window.location.pathname;
        if (currentPage.includes('product') || currentPage.includes('menu')) {
            console.warn('⚠️ No add-to-cart buttons found on product/menu page');
        } else {
            console.debug('ℹ️ No add-to-cart buttons (normal for non-product pages)');
        }
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Get product image URL with correct path handling
 */
function getProductImageUrl(imagePath) {
    const SITE_URL_JS = window.SITE_URL_JS;
    
    if (!imagePath) {
        return SITE_URL_JS + '/assets/images/products/placeholder.jpg';
    }
    
    // If path contains 'uploads/', it's from webapp
    if (imagePath.indexOf('uploads/') !== -1) {
        return SITE_URL_JS + '/webapp/' + imagePath.replace(/^\//, '');
    }
    
    // Otherwise it's from legacy assets
    return SITE_URL_JS + '/assets/images/products/' + imagePath;
}

/**
 * Apply discount code
 */
function applyDiscountCode(code) {
    const SITE_URL_JS = window.SITE_URL_JS;
    if (!code || code.trim() === '') {
        if (window.DailyCup && window.DailyCup.showAlert) {
            window.DailyCup.showAlert('Masukkan kode diskon', 'warning');
        } else {
            alert('Masukkan kode diskon');
        }
        return;
    }
    
    fetch(`${SITE_URL_JS}/api/cart.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'apply_discount',
            code: code
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.DailyCup && window.DailyCup.showAlert) {
                window.DailyCup.showAlert(data.message, 'success');
            } else {
                alert(data.message);
            }
            // Reload page to show updated discount
            location.reload();
        } else {
            if (window.DailyCup && window.DailyCup.showAlert) {
                window.DailyCup.showAlert(data.message, 'danger');
            } else {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.DailyCup && window.DailyCup.showAlert) {
            window.DailyCup.showAlert('Terjadi kesalahan', 'danger');
        } else {
            alert('Terjadi kesalahan');
        }
    });
}

// Make functions available globally
window.addToCart = addToCart;
window.updateCartItem = updateCartItem;
window.removeFromCart = removeFromCart;
window.clearCart = clearCart;
window.initCartEvents = initCartEvents;
window.applyDiscountCode = applyDiscountCode;
