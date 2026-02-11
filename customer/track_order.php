<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$orderId = intval($_GET['id'] ?? 0);
if (!$orderId) {
    header('Location: ' . SITE_URL . '/customer/orders.php');
    exit;
}

$db = getDB();

// Get order details with kurir info
$stmt = $db->prepare("SELECT o.*, 
                     u.name as customer_name, u.address as customer_address,
                     k.name as kurir_name, k.phone as kurir_phone, k.vehicle_type, k.vehicle_number
                     FROM orders o 
                     JOIN users u ON o.user_id = u.id
                     LEFT JOIN kurir k ON o.kurir_id = k.id
                     WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/customer/orders.php');
    exit;
}

// Check if order is trackable (has kurir assigned and in delivery)
$isTrackable = $order['kurir_id'] && in_array($order['status'], ['ready', 'delivering']);

// Get latest kurir location
$kurirLocation = null;
if ($order['kurir_id']) {
    $stmt = $db->prepare("SELECT latitude, longitude, updated_at 
                         FROM kurir_location 
                         WHERE kurir_id = ? 
                         ORDER BY updated_at DESC 
                         LIMIT 1");
    $stmt->execute([$order['kurir_id']]);
    $kurirLocation = $stmt->fetch();
}

// Cafe location (hardcoded - you can move this to settings)
$cafeLocation = [
    'lat' => -6.2088,  // Jakarta example - ganti dengan lokasi cafe Anda
    'lng' => 106.8456,
    'name' => 'DailyCup Coffee - Main Store'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo substr($order['order_number'], -8); ?> - DailyCup</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        #map {
            height: 60vh;
            width: 100%;
            z-index: 1;
        }
        
        .tracking-header {
            background: linear-gradient(135deg, #6F4E37 0%, #8B4513 100%);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .tracking-info {
            background: white;
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 2;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #28a745;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.1); }
        }
        
        .eta-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
        }
        
        .eta-time {
            font-size: 2rem;
            font-weight: bold;
            color: #6F4E37;
        }
        
        .kurir-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .kurir-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #6F4E37;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .btn-call {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-back {
            background: white;
            color: #6F4E37;
            border: 2px solid #6F4E37;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6F4E37;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #6F4E37;
        }
        
        .timeline-item.active::before {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a745;
            animation: pulse 2s infinite;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -17px;
            top: 20px;
            width: 2px;
            height: calc(100% - 10px);
            background: #ddd;
        }
        
        .timeline-item:last-child::after {
            display: none;
        }
        
        .offline-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .last-update {
            font-size: 0.85rem;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="tracking-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Track Order</h5>
                <small>#<?php echo substr($order['order_number'], -8); ?></small>
            </div>
            <a href="<?php echo SITE_URL; ?>/customer/order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-back">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <?php if (!$isTrackable): ?>
    <!-- Not Trackable Notice -->
    <div class="tracking-info">
        <div class="text-center py-4">
            <i class="bi bi-info-circle" style="font-size: 3rem; color: #6F4E37;"></i>
            <h5 class="mt-3">Tracking Not Available</h5>
            <p class="text-muted">
                <?php if (!$order['kurir_id']): ?>
                    Kurir belum ditugaskan untuk pesanan ini.
                <?php else: ?>
                    Pesanan Anda belum dalam pengiriman. Status: <strong><?php echo ucfirst($order['status']); ?></strong>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Map -->
    <div id="map"></div>
    
    <!-- Tracking Info -->
    <div class="tracking-info">
        <!-- Status -->
        <div class="status-indicator">
            <div class="status-dot"></div>
            <div>
                <strong><?php echo $order['status'] === 'ready' ? 'Pesanan Siap - Menunggu Pengambilan' : 'Kurir Sedang Dalam Perjalanan'; ?></strong>
                <div class="small text-muted">Live tracking aktif</div>
            </div>
        </div>
        
        <!-- ETA -->
        <div class="eta-box">
            <div class="small text-muted mb-1">Estimasi Waktu Tiba</div>
            <div class="eta-time" id="eta-display">
                <i class="bi bi-clock"></i> Menghitung...
            </div>
            <div class="small text-muted mt-1">
                <i class="bi bi-geo-alt"></i> <span id="distance-display">Mengukur jarak...</span>
            </div>
        </div>
        
        <!-- Kurir Info -->
        <?php if ($order['kurir_name']): ?>
        <div class="kurir-card">
            <div class="kurir-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="flex-grow-1">
                <strong><?php echo htmlspecialchars($order['kurir_name']); ?></strong>
                <div class="small text-muted">
                    <i class="bi bi-bicycle"></i> <?php echo ucfirst($order['vehicle_type']); ?> • <?php echo htmlspecialchars($order['vehicle_number']); ?>
                </div>
            </div>
            <a href="tel:<?php echo $order['kurir_phone']; ?>" class="btn btn-sm btn-success">
                <i class="bi bi-telephone-fill"></i>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Timeline -->
        <h6 class="mt-4 mb-3"><i class="bi bi-list-check"></i> Status Pengiriman</h6>
        <div class="timeline">
            <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'processing', 'ready', 'delivering', 'completed']) ? 'active' : ''; ?>">
                <strong>Pesanan Dikonfirmasi</strong>
                <div class="small text-muted">Pesanan Anda telah dikonfirmasi</div>
            </div>
            <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'ready', 'delivering', 'completed']) ? 'active' : ''; ?>">
                <strong>Sedang Diproses</strong>
                <div class="small text-muted">Pesanan sedang disiapkan</div>
            </div>
            <div class="timeline-item <?php echo in_array($order['status'], ['ready', 'delivering', 'completed']) ? 'active' : ''; ?>">
                <strong>Siap Diantar</strong>
                <div class="small text-muted">Menunggu kurir untuk pickup</div>
            </div>
            <div class="timeline-item <?php echo in_array($order['status'], ['delivering', 'completed']) ? 'active' : ''; ?>">
                <strong>Dalam Pengiriman</strong>
                <div class="small text-muted">Kurir sedang dalam perjalanan</div>
            </div>
            <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'active' : ''; ?>">
                <strong>Pesanan Sampai</strong>
                <div class="small text-muted">Selamat menikmati!</div>
            </div>
        </div>
        
        <?php if ($kurirLocation): ?>
        <div class="last-update">
            <i class="bi bi-clock-history"></i> 
            Last updated: <?php echo date('H:i:s', strtotime($kurirLocation['updated_at'])); ?>
        </div>
        <?php else: ?>
        <div class="offline-notice">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Waiting for kurir location...</strong><br>
            <small>Lokasi akan muncul saat kurir mulai pengiriman</small>
        </div>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Configuration
        const orderId = <?php echo $orderId; ?>;
        const isTrackable = <?php echo $isTrackable ? 'true' : 'false'; ?>;
        
        // Locations
        const cafeLocation = <?php echo json_encode($cafeLocation); ?>;
        const customerLocation = {
            lat: -6.2088 + (Math.random() * 0.02 - 0.01), // Random nearby location for demo
            lng: 106.8456 + (Math.random() * 0.02 - 0.01),
            address: <?php echo json_encode($order['delivery_address']); ?>
        };
        
        let kurirLocation = <?php echo $kurirLocation ? json_encode(['lat' => (float)$kurirLocation['latitude'], 'lng' => (float)$kurirLocation['longitude']]) : 'null'; ?>;
        
        let map, kurirMarker, customerMarker, cafeMarker, routeLine;
        
        if (isTrackable) {
            // Initialize map
            map = L.map('map').setView([cafeLocation.lat, cafeLocation.lng], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // Cafe marker (green house icon)
            const cafeIcon = L.divIcon({
                className: 'custom-icon',
                html: '<div style="background: #28a745; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-shop" style="font-size: 1.2rem;"></i></div>',
                iconSize: [40, 40]
            });
            cafeMarker = L.marker([cafeLocation.lat, cafeLocation.lng], {icon: cafeIcon})
                .addTo(map)
                .bindPopup('<strong>' + cafeLocation.name + '</strong>');
            
            // Customer marker (blue home icon)
            const customerIcon = L.divIcon({
                className: 'custom-icon',
                html: '<div style="background: #007bff; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="bi bi-house-fill" style="font-size: 1.2rem;"></i></div>',
                iconSize: [40, 40]
            });
            customerMarker = L.marker([customerLocation.lat, customerLocation.lng], {icon: customerIcon})
                .addTo(map)
                .bindPopup('<strong>Your Location</strong><br>' + customerLocation.address);
            
            // Kurir marker (animated)
            if (kurirLocation) {
                const kurirIcon = L.divIcon({
                    className: 'custom-icon',
                    html: '<div style="background: #6F4E37; color: white; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.4); animation: bounce 1s infinite;"><i class="bi bi-bicycle" style="font-size: 1.5rem;"></i></div><style>@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); }}</style>',
                    iconSize: [50, 50]
                });
                kurirMarker = L.marker([kurirLocation.lat, kurirLocation.lng], {icon: kurirIcon})
                    .addTo(map)
                    .bindPopup('<strong>Kurir Location</strong><br>On the way!');
                
                // Draw route line
                updateRoute();
            }
            
            // Fit bounds to show all markers
            const bounds = L.latLngBounds([
                [cafeLocation.lat, cafeLocation.lng],
                [customerLocation.lat, customerLocation.lng]
            ]);
            if (kurirLocation) {
                bounds.extend([kurirLocation.lat, kurirLocation.lng]);
            }
            map.fitBounds(bounds, {padding: [50, 50]});
            
            // Start real-time tracking
            startTracking();
        }
        
        function updateRoute() {
            if (routeLine) {
                map.removeLayer(routeLine);
            }
            
            if (kurirLocation) {
                routeLine = L.polyline([
                    [kurirLocation.lat, kurirLocation.lng],
                    [customerLocation.lat, customerLocation.lng]
                ], {
                    color: '#6F4E37',
                    weight: 4,
                    opacity: 0.7,
                    dashArray: '10, 10'
                }).addTo(map);
                
                // Calculate distance and ETA
                const distance = calculateDistance(
                    kurirLocation.lat, kurirLocation.lng,
                    customerLocation.lat, customerLocation.lng
                );
                
                const distanceKm = (distance / 1000).toFixed(1);
                const avgSpeed = 20; // km/h average speed
                const etaMinutes = Math.ceil((distance / 1000) / avgSpeed * 60);
                
                document.getElementById('distance-display').textContent = distanceKm + ' km';
                document.getElementById('eta-display').innerHTML = 
                    '<i class="bi bi-clock"></i> ' + etaMinutes + ' menit';
            }
        }
        
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth radius in meters
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;
            
            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            
            return R * c; // Distance in meters
        }
        
        function startTracking() {
            // Server-Sent Events for real-time updates
            const eventSource = new EventSource('<?php echo SITE_URL; ?>/api/track_location.php?order_id=' + orderId);
            
            eventSource.onmessage = function(event) {
                const data = JSON.parse(event.data);
                
                if (data.lat && data.lng) {
                    kurirLocation = {lat: data.lat, lng: data.lng};
                    
                    if (kurirMarker) {
                        kurirMarker.setLatLng([data.lat, data.lng]);
                    } else {
                        const kurirIcon = L.divIcon({
                            className: 'custom-icon',
                            html: '<div style="background: #6F4E37; color: white; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.4);"><i class="bi bi-bicycle" style="font-size: 1.5rem;"></i></div>',
                            iconSize: [50, 50]
                        });
                        kurirMarker = L.marker([data.lat, data.lng], {icon: kurirIcon})
                            .addTo(map)
                            .bindPopup('<strong>Kurir Location</strong><br>On the way!');
                    }
                    
                    updateRoute();
                    
                    // Update last update time
                    const now = new Date();
                    document.querySelector('.last-update').innerHTML = 
                        '<i class="bi bi-clock-history"></i> Last updated: ' + 
                        now.toLocaleTimeString('id-ID');
                }
            };
            
            eventSource.onerror = function() {
                console.log('Connection lost. Retrying...');
            };
        }
    </script>
</body>
</html>
