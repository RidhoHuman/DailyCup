<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || getCartCount() == 0) {
    header('Location: ' . SITE_URL . '/customer/cart.php');
    exit;
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$db = getDB();
$paymentMethods = $db->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY display_order")->fetchAll();
?>

<div class="container my-5">
    <h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>
    
    <form method="POST" action="<?php echo SITE_URL; ?>/customer/payment.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Delivery Method -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-coffee text-white">
                        <h5 class="mb-0">Metode Pengiriman</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="delivery_method" id="dine-in" value="dine-in" required>
                            <label class="form-check-label" for="dine-in">
                                <strong>Dine In</strong> - Makan di tempat
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="delivery_method" id="takeaway" value="takeaway" required>
                            <label class="form-check-label" for="takeaway">
                                <strong>Takeaway</strong> - Bawa pulang
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="delivery_method" id="delivery" value="delivery" required>
                            <label class="form-check-label" for="delivery">
                                <strong>Delivery</strong> - Diantar ke alamat
                            </label>
                        </div>
                        
                        <div id="deliveryAddress" class="mt-3" style="display:none;">
                            <label class="form-label">Alamat Pengiriman</label>
                            <textarea name="delivery_address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Notes -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-coffee text-white">
                        <h5 class="mb-0">Catatan</h5>
                    </div>
                    <div class="card-body">
                        <textarea name="customer_notes" class="form-control" rows="3" 
                                  placeholder="Catatan untuk pesanan (opsional)"></textarea>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card shadow-sm">
                    <div class="card-header bg-coffee text-white">
                        <h5 class="mb-0">Metode Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($paymentMethods as $method): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" 
                                   id="payment-<?php echo $method['id']; ?>" 
                                   value="<?php echo $method['id']; ?>" required>
                            <label class="form-check-label" for="payment-<?php echo $method['id']; ?>">
                                <strong><?php echo htmlspecialchars($method['method_name']); ?></strong>
                                <?php if ($method['account_number']): ?>
                                - <?php echo htmlspecialchars($method['account_number']); ?>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-coffee text-white">
                        <h5 class="mb-0">Ringkasan</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="cart-subtotal"></span>
                        </div>
                        
                        <?php 
                        $currentUser = getCurrentUser();
                        $loyaltyPoints = $currentUser['loyalty_points'];
                        $db = getDB();
                        $stmt = $db->query("SELECT * FROM loyalty_settings WHERE is_active = 1 LIMIT 1");
                        $settings = $stmt->fetch();
                        $rupiahPerPoint = $settings ? $settings['rupiah_per_point'] : 100;
                        $minPointsRedeem = $settings ? $settings['min_points_redeem'] : 100;
                        ?>
                        
                        <!-- Loyalty Points Section -->
                        <?php if ($loyaltyPoints >= $minPointsRedeem): ?>
                        <div class="card mt-3 border-warning">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="text-muted d-block">Poin Anda</small>
                                        <strong class="text-warning"><i class="bi bi-star-fill"></i> <?php echo number_format($loyaltyPoints); ?> Poin</strong>
                                    </div>
                                    <small class="text-muted">= Rp <?php echo number_format($loyaltyPoints * $rupiahPerPoint); ?></small>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="use_loyalty_points" id="useLoyaltyPoints" value="1">
                                    <label class="form-check-label" for="useLoyaltyPoints">
                                        <small>Gunakan Poin untuk Diskon</small>
                                    </label>
                                </div>
                                <div id="pointsToUse" style="display:none;" class="mt-2">
                                    <label class="form-label"><small>Jumlah Poin</small></label>
                                    <input type="number" name="points_to_redeem" class="form-control form-control-sm" 
                                           min="<?php echo $minPointsRedeem; ?>" max="<?php echo $loyaltyPoints; ?>" 
                                           value="<?php echo $loyaltyPoints; ?>" id="pointsInput">
                                    <small class="text-muted">Min: <?php echo number_format($minPointsRedeem); ?> poin</small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div id="loyaltyDiscount" style="display:none;" class="d-flex justify-content-between mb-2 text-success">
                            <span><small>Diskon Poin:</small></span>
                            <span><small>- <span id="loyaltyDiscountAmount">Rp 0</span></small></span>
                        </div>
                        
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="cart-total text-coffee"></strong>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-coffee btn-lg">
                                <i class="bi bi-check-circle"></i> Buat Pesanan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('delivery').addEventListener('change', function() {
    document.getElementById('deliveryAddress').style.display = 'block';
});

document.getElementById('dine-in').addEventListener('change', function() {
    document.getElementById('deliveryAddress').style.display = 'none';
});

document.getElementById('takeaway').addEventListener('change', function() {
    document.getElementById('deliveryAddress').style.display = 'none';
});

// Loyalty points handling
const useLoyaltyCheckbox = document.getElementById('useLoyaltyPoints');
const pointsToUseDiv = document.getElementById('pointsToUse');
const pointsInput = document.getElementById('pointsInput');
const loyaltyDiscountDiv = document.getElementById('loyaltyDiscount');
const loyaltyDiscountAmount = document.getElementById('loyaltyDiscountAmount');
const rupiahPerPoint = <?php echo $rupiahPerPoint ?? 100; ?>;

if (useLoyaltyCheckbox) {
    useLoyaltyCheckbox.addEventListener('change', function() {
        if (this.checked) {
            pointsToUseDiv.style.display = 'block';
            updateLoyaltyDiscount();
        } else {
            pointsToUseDiv.style.display = 'none';
            loyaltyDiscountDiv.style.display = 'none';
            updateCartDisplay();
        }
    });
    
    pointsInput.addEventListener('input', function() {
        updateLoyaltyDiscount();
    });
}

function updateLoyaltyDiscount() {
    const points = parseInt(pointsInput.value) || 0;
    const discount = points * rupiahPerPoint;
    loyaltyDiscountAmount.textContent = 'Rp ' + discount.toLocaleString('id-ID');
    loyaltyDiscountDiv.style.display = 'flex';
    updateCartDisplay();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
