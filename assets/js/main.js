/**
 * Main JavaScript for DailyCup
 */

// Global site URL - declare once here to avoid duplicate declarations
window.SITE_URL_JS = window.SITE_URL_JS || window.location.origin + '/DailyCup';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize smooth scrolling
    initSmoothScroll();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize image preview
    initImagePreview();
});

/**
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Smooth scrolling for anchor links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
}

/**
 * Form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Image preview before upload
 */
function initImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.match('image.*')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Find or create preview container
                    let preview = input.parentElement.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'image-preview img-thumbnail mt-2';
                        input.parentElement.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

/**
 * Show loading spinner
 */
function showLoading(message = 'Loading...') {
    const loadingHTML = `
        <div class="loading-overlay" id="loadingOverlay">
            <div class="text-center">
                <div class="spinner-coffee mx-auto mb-3"></div>
                <p class="text-white">${message}</p>
            </div>
        </div>
    `;
    
    // Add overlay to body
    const div = document.createElement('div');
    div.innerHTML = loadingHTML;
    document.body.appendChild(div.firstElementChild);
    
    // Add CSS for overlay
    const style = document.createElement('style');
    style.textContent = `
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.remove();
    }
}

/**
 * Show alert message
 */
function showAlert(message, type = 'success') {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
             role="alert" style="z-index: 9999; max-width: 500px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const div = document.createElement('div');
    div.innerHTML = alertHTML;
    document.body.appendChild(div.firstElementChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.parentElement) {
                alert.remove();
            }
        });
    }, 5000);
}

/**
 * Confirm dialog
 */
function confirmDialog(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format currency to Rupiah
 */
function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

/**
 * Debounce function for search
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Teks berhasil disalin!', 'success');
    }).catch(() => {
        showAlert('Gagal menyalin teks', 'danger');
    });
}

/**
 * Toggle favorite product
 */
function toggleFavorite(productId, element) {
    fetch(`/dailycup/api/favorites.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            element.classList.toggle('active');
            if (data.is_favorite) {
                element.innerHTML = '<i class="bi bi-heart-fill"></i>';
                showAlert('Ditambahkan ke favorit', 'success');
            } else {
                element.innerHTML = '<i class="bi bi-heart"></i>';
                showAlert('Dihapus dari favorit', 'info');
            }
        } else {
            showAlert(data.message || 'Terjadi kesalahan', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Terjadi kesalahan', 'danger');
    });
}

/**
 * Update quantity with validation
 */
function updateQuantity(input, min = 1, max = 999) {
    let value = parseInt(input.value);
    
    if (isNaN(value) || value < min) {
        value = min;
    } else if (value > max) {
        value = max;
    }
    
    input.value = value;
    return value;
}

/**
 * Star rating handler
 */
function initStarRating() {
    const starContainers = document.querySelectorAll('.star-rating');
    
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const input = container.querySelector('input[type="hidden"]');
        
        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                const rating = index + 1;
                input.value = rating;
                
                // Update visual stars
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                        s.innerHTML = '<i class="bi bi-star-fill"></i>';
                    } else {
                        s.classList.remove('active');
                        s.innerHTML = '<i class="bi bi-star"></i>';
                    }
                });
            });
            
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => {
                    if (i <= index) {
                        s.innerHTML = '<i class="bi bi-star-fill"></i>';
                    } else {
                        s.innerHTML = '<i class="bi bi-star"></i>';
                    }
                });
            });
        });
        
        container.addEventListener('mouseleave', () => {
            const currentRating = parseInt(input.value) || 0;
            stars.forEach((s, i) => {
                if (i < currentRating) {
                    s.innerHTML = '<i class="bi bi-star-fill"></i>';
                } else {
                    s.innerHTML = '<i class="bi bi-star"></i>';
                }
            });
        });
    });
}

// Export functions for use in other scripts
window.DailyCup = {
    showLoading,
    hideLoading,
    showAlert,
    confirmDialog,
    formatCurrency,
    debounce,
    copyToClipboard,
    toggleFavorite,
    updateQuantity,
    initStarRating
};
