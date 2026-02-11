<?php
require_once __DIR__ . '/../includes/functions.php';

// Kurir login check
if (!isset($_SESSION['kurir_id'])) {
    header('Location: ' . SITE_URL . '/kurir/login.php');
    exit;
}

$db = getDB();
$kurirId = $_SESSION['kurir_id'];
$orderId = intval($_GET['id'] ?? 0);

if (!$orderId) {
    header('Location: ' . SITE_URL . '/kurir/index.php');
    exit;
}

// Get order details
$stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone, u.address as customer_address
                     FROM orders o
                     JOIN users u ON o.user_id = u.id
                     WHERE o.id = ? AND o.kurir_id = ?");
$stmt->execute([$orderId, $kurirId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/kurir/index.php?error=invalid_order');
    exit;
}

// Get order items
$stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Get kurir info
$stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
$stmt->execute([$kurirId]);
$kurir = $stmt->fetch();

// Calculate time information
$now = new DateTime();
$estimatedReady = $order['estimated_ready_at'] ? new DateTime($order['estimated_ready_at']) : null;
$minArrivalTime = $estimatedReady ? clone $estimatedReady : null;
if ($minArrivalTime) $minArrivalTime->modify('-15 minutes');

$kurirArrived = $order['kurir_arrived_at'] ? new DateTime($order['kurir_arrived_at']) : null;
$pickupTime = $order['pickup_time'] ? new DateTime($order['pickup_time']) : null;
$deliveryTime = $order['delivery_time'] ? new DateTime($order['delivery_time']) : null;

// Check status
$canArriveAtStore = ($order['status'] == 'processing' || $order['status'] == 'ready') && !$kurirArrived;
$canPickup = ($order['status'] == 'ready' || $order['status'] == 'delivering') && $kurirArrived && !$pickupTime;
$canComplete = $order['status'] == 'delivering' && $pickupTime;

// Check if late
$isLate = $estimatedReady && $now > $estimatedReady && !$kurirArrived;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - DailyCup Kurir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6F4E37;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            background: #f8f9fa;
            padding-bottom: 100px;
        }
        
        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8B4513 100%);
            color: white;
            padding: 20px;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 50px;
            padding-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 25px;
            bottom: -30px;
            width: 2px;
            background: #ddd;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-dot {
            position: absolute;
            left: 8px;
            top: 8px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #ddd;
            border: 3px solid white;
        }
        
        .timeline-dot.active {
            background: var(--primary-color);
        }
        
        .timeline-dot.completed {
            background: var(--success-color);
        }
        
        .photo-preview {
            width: 100%;
            max-height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-top: 10px;
        }
        
        .time-warning {
            background: #fff3cd;
            border-left: 4px solid var(--warning-color);
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .time-danger {
            background: #f8d7da;
            border-left: 4px solid var(--danger-color);
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .action-btn {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 40px);
            max-width: 500px;
            z-index: 1000;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header-section">
        <a href="index.php" class="text-white mb-2 d-inline-block">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <h5 class="mb-1">Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
        <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-light text-dark">
                <?php echo ucfirst($order['status']); ?>
            </span>
            <small><?php echo formatCurrency($order['final_amount']); ?></small>
        </div>
    </div>

    <div class="container py-3">
        
        <!-- Time Information -->
        <?php if ($estimatedReady): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="bi bi-clock"></i> Informasi Waktu</h6>
                <hr>
                <div class="row text-center">
                    <div class="col-6 border-end">
                        <div class="text-muted small">Standby Paling Lambat</div>
                        <div class="fw-bold text-primary"><?php echo $minArrivalTime->format('H:i'); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Estimasi Siap</div>
                        <div class="fw-bold text-success"><?php echo $estimatedReady->format('H:i'); ?></div>
                    </div>
                </div>
                
                <?php if ($isLate): ?>
                <div class="time-danger mt-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Terlambat!</strong> Pesanan sudah siap. Segera ke toko!
                </div>
                <?php elseif ($canArriveAtStore && $now >= $minArrivalTime): ?>
                <div class="time-warning mt-3">
                    <i class="bi bi-info-circle-fill"></i>
                    Waktunya standby di toko! Pesanan akan siap dalam <?php echo $order['preparation_time']; ?> menit.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Store Location & Navigation -->
        <div class="card mb-3 bg-light">
            <div class="card-body">
                <h6><i class="bi bi-shop"></i> Lokasi Toko</h6>
                <hr>
                <p class="mb-2"><strong><?php echo STORE_NAME; ?></strong></p>
                <p class="mb-2">
                    <i class="bi bi-geo-alt-fill text-danger"></i> 
                    <?php echo STORE_ADDRESS; ?>
                </p>
                <p class="mb-3">
                    <i class="bi bi-telephone-fill text-success"></i> 
                    <?php echo STORE_PHONE; ?>
                </p>
                
                <!-- Navigation Buttons -->
                <div class="d-grid gap-2">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo STORE_LATITUDE; ?>,<?php echo STORE_LONGITUDE; ?>" 
                       target="_blank" 
                       class="btn btn-primary">
                        <i class="bi bi-map"></i> Navigasi ke Toko (Google Maps)
                    </a>
                    <a href="geo:<?php echo STORE_LATITUDE; ?>,<?php echo STORE_LONGITUDE; ?>?q=<?php echo urlencode(STORE_NAME); ?>" 
                       class="btn btn-outline-primary">
                        <i class="bi bi-compass"></i> Buka di Maps App
                    </a>
                </div>
                
                <div class="alert alert-info mt-3 mb-0 small">
                    <i class="bi bi-info-circle"></i> 
                    Pastikan GPS aktif untuk navigasi yang akurat
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="bi bi-person-circle"></i> Informasi Customer</h6>
                <hr>
                <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                <p class="mb-2"><i class="bi bi-telephone"></i> 
                    <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </a>
                </p>
                <?php if ($order['delivery_method'] == 'delivery'): ?>
                <p class="mb-2">
                    <i class="bi bi-geo-alt"></i> 
                    <?php echo htmlspecialchars($order['delivery_address'] ?: $order['customer_address']); ?>
                </p>
                
                <!-- Navigation to Customer (Only show after pickup) -->
                <?php if ($pickupTime): ?>
                <div class="d-grid gap-2 mt-3">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($order['delivery_address'] ?: $order['customer_address']); ?>" 
                       target="_blank" 
                       class="btn btn-success">
                        <i class="bi bi-map"></i> Navigasi ke Customer
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="bi bi-bag"></i> Detail Pesanan</h6>
                <hr>
                <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between mb-2">
                    <div>
                        <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <small class="text-muted">
                            <?php echo htmlspecialchars($item['size']); ?> | <?php echo htmlspecialchars($item['temperature']); ?>
                            | x<?php echo $item['quantity']; ?>
                        </small>
                    </div>
                    <div class="text-end">
                        <?php echo formatCurrency($item['subtotal']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($order['customer_notes']): ?>
                <hr>
                <div class="alert alert-info mb-0">
                    <strong>Catatan:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Delivery Timeline -->
        <div class="card mb-3">
            <div class="card-body">
                <h6><i class="bi bi-list-check"></i> Progress Pengantaran</h6>
                <hr>
                <div class="timeline">
                    <!-- Step 1: Assigned -->
                    <div class="timeline-item">
                        <div class="timeline-dot completed"></div>
                        <div>
                            <strong>Pesanan Ditugaskan</strong>
                            <div class="text-muted small"><?php echo $order['assigned_at'] ? date('H:i', strtotime($order['assigned_at'])) : '-'; ?></div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Arrived at Store -->
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $kurirArrived ? 'completed' : ($canArriveAtStore ? 'active' : ''); ?>"></div>
                        <div>
                            <strong>Tiba di Toko</strong>
                            <div class="text-muted small">
                                <?php echo $kurirArrived ? $kurirArrived->format('H:i') : 'Belum tiba'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Pickup -->
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $pickupTime ? 'completed' : ($canPickup ? 'active' : ''); ?>"></div>
                        <div>
                            <strong>Ambil Pesanan</strong>
                            <div class="text-muted small">
                                <?php echo $pickupTime ? $pickupTime->format('H:i') : 'Belum diambil'; ?>
                            </div>
                            <?php if ($order['kurir_departure_photo']): ?>
                            <img src="<?php echo SITE_URL; ?>/assets/images/delivery/<?php echo htmlspecialchars($order['kurir_departure_photo']); ?>" 
                                 class="photo-preview" alt="Foto Keberangkatan">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Step 4: Delivered -->
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $deliveryTime ? 'completed' : ($canComplete ? 'active' : ''); ?>"></div>
                        <div>
                            <strong>Diantar ke Customer</strong>
                            <div class="text-muted small">
                                <?php echo $deliveryTime ? $deliveryTime->format('H:i') : 'Belum selesai'; ?>
                            </div>
                            <?php if ($order['kurir_arrival_photo']): ?>
                            <img src="<?php echo SITE_URL; ?>/assets/images/delivery/<?php echo htmlspecialchars($order['kurir_arrival_photo']); ?>" 
                                 class="photo-preview" alt="Foto Sampai">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Action Buttons -->
    <?php if ($canArriveAtStore): ?>
    <div class="action-btn">
        <button type="button" class="btn btn-primary w-100 btn-lg" onclick="arriveAtStore()">
            <i class="bi bi-check-circle"></i> Saya Sudah Tiba di Toko
        </button>
    </div>
    <?php elseif ($canPickup): ?>
    <div class="action-btn">
        <button type="button" class="btn btn-success w-100 btn-lg" data-bs-toggle="modal" data-bs-target="#pickupModal">
            <i class="bi bi-box-arrow-right"></i> Ambil & Berangkat (Upload Foto)
        </button>
    </div>
    <?php elseif ($canComplete): ?>
    <div class="action-btn">
        <button type="button" class="btn btn-success w-100 btn-lg" data-bs-toggle="modal" data-bs-target="#deliveredModal">
            <i class="bi bi-check-circle-fill"></i> Sudah Sampai (Upload Foto)
        </button>
    </div>
    <?php endif; ?>

    <!-- Pickup Modal -->
    <div class="modal fade" id="pickupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Foto Bukti Keberangkatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="pickupForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <?php if (TESTING_MODE): ?>
                            <strong>MODE TESTING:</strong> Upload foto OPTIONAL untuk testing. Bisa langsung submit tanpa foto.
                            <?php else: ?>
                            Ambil foto dengan pesanan yang sudah siap sebelum berangkat.
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto Bukti <?php echo TESTING_MODE ? '' : '<span class="text-danger">*</span>'; ?></label>
                            <input type="file" class="form-control" name="photo" accept="image/*" capture="environment" <?php echo TESTING_MODE ? '' : 'required'; ?>>
                            <div class="form-text">
                                <?php echo TESTING_MODE ? 'Optional - Klik Upload langsung jika tidak ada foto' : 'Foto akan diambil dengan kamera'; ?>
                            </div>
                        </div>
                        <div id="photoPreview"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload"></i> Upload & Berangkat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delivered Modal -->
    <div class="modal fade" id="deliveredModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Foto Bukti Sampai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="deliveredForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i>
                            <?php if (TESTING_MODE): ?>
                            <strong>MODE TESTING:</strong> Upload foto OPTIONAL untuk testing. Bisa langsung submit tanpa foto.
                            <?php else: ?>
                            Ambil foto sebagai bukti pesanan telah sampai ke customer.
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto Bukti <?php echo TESTING_MODE ? '' : '<span class="text-danger">*</span>'; ?></label>
                            <input type="file" class="form-control" name="photo" accept="image/*" capture="environment" <?php echo TESTING_MODE ? '' : 'required'; ?>>
                            <div class="form-text">
                                <?php echo TESTING_MODE ? 'Optional - Klik Konfirmasi langsung jika tidak ada foto' : 'Foto akan diambil dengan kamera'; ?>
                            </div>
                        </div>
                        <div id="photoPreview2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle-fill"></i> Konfirmasi Selesai
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const orderId = <?php echo $orderId; ?>;
        const testingMode = <?php echo TESTING_MODE ? 'true' : 'false'; ?>;
        
        // Arrive at store
        function arriveAtStore() {
            if (!confirm('Konfirmasi bahwa Anda sudah tiba di toko?')) return;
            
            const formData = new FormData();
            formData.append('action', 'arrived_at_store');
            formData.append('order_id', orderId);
            
            // Get GPS or use dummy in testing mode
            if (testingMode) {
                // Testing mode: use dummy coordinates
                formData.append('latitude', <?php echo STORE_LATITUDE; ?>);
                formData.append('longitude', <?php echo STORE_LONGITUDE; ?>);
                submitArrival(formData);
            } else {
                // Production: get real GPS
                navigator.geolocation.getCurrentPosition(function(position) {
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                    submitArrival(formData);
                }, function(error) {
                    alert('Gagal mendapatkan lokasi GPS. Pastikan GPS aktif!');
                });
            }
        }
        
        function submitArrival(formData) {
            fetch('<?php echo SITE_URL; ?>/api/kurir_update_delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }
        
        // Pickup with photo
        document.getElementById('pickupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'pickup_with_photo');
            formData.append('order_id', orderId);
            
            // Get GPS or use dummy in testing mode
            if (testingMode) {
                // Testing mode: use dummy coordinates
                formData.append('latitude', <?php echo STORE_LATITUDE; ?>);
                formData.append('longitude', <?php echo STORE_LONGITUDE; ?>);
                submitPickup(formData);
            } else {
                // Production: get real GPS
                navigator.geolocation.getCurrentPosition(function(position) {
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                    submitPickup(formData);
                }, function(error) {
                    alert('Gagal mendapatkan lokasi GPS. Pastikan GPS aktif!');
                });
            }
        });
        
        function submitPickup(formData) {
            fetch('<?php echo SITE_URL; ?>/api/kurir_update_delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }
        
        // Delivered with photo
        document.getElementById('deliveredForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'delivered_with_photo');
            formData.append('order_id', orderId);
            
            // Get GPS or use dummy in testing mode
            if (testingMode) {
                // Testing mode: use dummy coordinates
                formData.append('latitude', <?php echo STORE_LATITUDE; ?>);
                formData.append('longitude', <?php echo STORE_LONGITUDE; ?>);
                submitDelivery(formData);
            } else {
                // Production: get real GPS
                navigator.geolocation.getCurrentPosition(function(position) {
                    formData.append('latitude', position.coords.latitude);
                    formData.append('longitude', position.coords.longitude);
                    submitDelivery(formData);
                }, function(error) {
                    alert('Gagal mendapatkan lokasi GPS. Pastikan GPS aktif!');
                });
            }
        });
        
        function submitDelivery(formData) {
            fetch('<?php echo SITE_URL; ?>/api/kurir_update_delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    alert('Selamat! Anda mendapat: ' + data.points_earned + ' poin delivery');
                    window.location.href = '<?php echo SITE_URL; ?>/kurir/index.php';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            });
        }
        
        // Photo preview
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const preview = e.target.closest('.modal-body').querySelector('div[id^="photoPreview"]');
                        preview.innerHTML = '<img src="' + event.target.result + '" class="photo-preview" alt="Preview">';
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>
