<?php
require_once __DIR__ . '/../../includes/functions.php';
$pageTitle = 'Live Tracking Monitor';
$isAdminPage = true;
requireAdmin();

$db = getDB();

// Get all active kurir with their locations
$stmt = $db->query("SELECT k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, k.vehicle_number, 
                   k.status, k.rating, k.total_deliveries, k.is_active, k.created_at,
                   kl.latitude, kl.longitude, kl.updated_at as last_location_update,
                   COUNT(DISTINCT o.id) as active_deliveries
                   FROM kurir k
                   LEFT JOIN kurir_location kl ON k.id = kl.kurir_id
                   LEFT JOIN orders o ON k.id = o.kurir_id 
                      AND o.status IN ('ready', 'delivering')
                   WHERE k.is_active = 1
                   GROUP BY k.id, k.name, k.phone, k.email, k.photo, k.vehicle_type, k.vehicle_number,
                            k.status, k.rating, k.total_deliveries, k.is_active, k.created_at,
                            kl.latitude, kl.longitude, kl.updated_at
                   ORDER BY k.status ASC, active_deliveries DESC");
$kurirs = $stmt->fetchAll();

// Get active orders with kurir
$stmt = $db->query("SELECT o.*, 
                   u.name as customer_name, u.phone as customer_phone,
                   k.name as kurir_name, k.phone as kurir_phone
                   FROM orders o
                   JOIN users u ON o.user_id = u.id
                   LEFT JOIN kurir k ON o.kurir_id = k.id
                   WHERE o.status IN ('ready', 'delivering')
                   ORDER BY o.status DESC, o.created_at ASC");
$activeOrders = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
#monitorMap {
    height: 70vh;
    width: 100%;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.kurir-status-panel {
    background: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.kurir-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.kurir-item:last-child {
    border-bottom: none;
}

.kurir-name {
    font-weight: 600;
}

.orders-panel {
    max-height: 400px;
    overflow-y: auto;
}

.order-item {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 4px solid #6F4E37;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #6F4E37;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 5px;
}
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="admin-layout">
    <?php require_once __DIR__ . '/../../includes/sidebar_admin.php'; ?>
    
    <div class="admin-main">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-broadcast-pin"></i> Live Tracking Monitor
                <span class="badge bg-success ms-2">LIVE</span>
            </h1>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-value"><?php echo count(array_filter($kurirs, fn($k) => $k['status'] === 'available')); ?></div>
                <div class="stat-label"><i class="bi bi-check-circle"></i> Available</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo count(array_filter($kurirs, fn($k) => $k['status'] === 'busy')); ?></div>
                <div class="stat-label"><i class="bi bi-hourglass-split"></i> Busy</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo count($activeOrders); ?></div>
                <div class="stat-label"><i class="bi bi-box-seam"></i> Active Deliveries</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo count(array_filter($kurirs, fn($k) => $k['latitude'])); ?></div>
                <div class="stat-label"><i class="bi bi-geo-alt"></i> Tracking Online</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Map -->
                <div id="monitorMap"></div>
            </div>
            
            <div class="col-lg-4">
                <!-- Kurir Status -->
                <div class="kurir-status-panel">
                    <h6 class="mb-3"><i class="bi bi-bicycle"></i> Kurir Status</h6>
                    <?php foreach ($kurirs as $kurir): ?>
                    <div class="kurir-item">
                        <div>
                            <div class="kurir-name"><?php echo htmlspecialchars($kurir['name']); ?></div>
                            <small class="text-muted">
                                <i class="bi bi-circle-fill" style="color: <?php echo $kurir['status'] === 'available' ? '#28a745' : ($kurir['status'] === 'busy' ? '#ffc107' : '#6c757d'); ?>;"></i>
                                <?php echo ucfirst($kurir['status']); ?>
                                <?php if ($kurir['active_deliveries'] > 0): ?>
                                 • <?php echo $kurir['active_deliveries']; ?> order(s)
                                <?php endif; ?>
                            </small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="focusKurir(<?php echo $kurir['id']; ?>, <?php echo $kurir['latitude'] ?? 'null'; ?>, <?php echo $kurir['longitude'] ?? 'null'; ?>)">
                            <i class="bi bi-geo-alt"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Active Orders -->
                <div class="kurir-status-panel orders-panel">
                    <h6 class="mb-3"><i class="bi bi-box-seam"></i> Active Orders</h6>
                    <?php if (count($activeOrders) > 0): ?>
                        <?php foreach ($activeOrders as $order): ?>
                        <div class="order-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>#<?php echo substr($order['order_number'], -8); ?></strong>
                                <span class="badge bg-<?php echo $order['status'] === 'ready' ? 'warning' : 'success'; ?>">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </div>
                            <div class="small">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <i class="bi bi-bicycle"></i> <?php echo $order['kurir_name'] ? htmlspecialchars($order['kurir_name']) : '<em class="text-muted">No kurir</em>'; ?><br>
                                <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No active deliveries</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const kurirs = <?php echo json_encode($kurirs); ?>;

// Store location from constants
const STORE_LAT = <?php echo STORE_LATITUDE; ?>;
const STORE_LNG = <?php echo STORE_LONGITUDE; ?>;
const STORE_NAME = <?php echo json_encode(STORE_NAME); ?>;
const STORE_ADDRESS = <?php echo json_encode(str_replace("\n", "<br>", STORE_ADDRESS)); ?>;

// DEBUG: Log coordinates to console
console.log('=== STORE COORDINATES DEBUG ===');
console.log('STORE_LAT:', STORE_LAT);
console.log('STORE_LNG:', STORE_LNG);
console.log('STORE_NAME:', STORE_NAME);
console.log('STORE_ADDRESS:', STORE_ADDRESS);
console.log('================================');

// Validate coordinates
if (!STORE_LAT || !STORE_LNG || isNaN(STORE_LAT) || isNaN(STORE_LNG)) {
    console.error('❌ INVALID COORDINATES!');
    alert('Error: Koordinat toko tidak valid!\nLAT: ' + STORE_LAT + '\nLNG: ' + STORE_LNG);
}

// Initialize map centered on store location
console.log('Initializing map at:', [STORE_LAT, STORE_LNG]);
const map = L.map('monitorMap').setView([STORE_LAT, STORE_LNG], 15);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19
}).addTo(map);

console.log('✅ Map initialized successfully');

const kurirMarkers = {};

// Add store marker with bigger icon
const cafeIcon = L.divIcon({
    className: 'custom-icon',
    html: '<div style="background: #28a745; color: white; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.5); border: 4px solid white; font-size: 24px;">🏪</div>',
    iconSize: [50, 50],
    iconAnchor: [25, 50]
});

console.log('Adding store marker at:', [STORE_LAT, STORE_LNG]);
const storeMarker = L.marker([STORE_LAT, STORE_LNG], {icon: cafeIcon})
    .addTo(map)
    .bindPopup('<strong>' + STORE_NAME + '</strong><br>' + STORE_ADDRESS + '<br><small>Lat: ' + STORE_LAT + '<br>Lng: ' + STORE_LNG + '</small>')
    .openPopup();

console.log('✅ Store marker added:', storeMarker);

// Add circle around store for visibility
L.circle([STORE_LAT, STORE_LNG], {
    color: '#28a745',
    fillColor: '#28a745',
    fillOpacity: 0.2,
    radius: 300 // 300 meters
}).addTo(map);

console.log('✅ Store circle added');

// Add kurir markers
kurirs.forEach(kurir => {
    if (kurir.latitude && kurir.longitude) {
        const color = kurir.status === 'available' ? '#28a745' : 
                     kurir.status === 'busy' ? '#ffc107' : '#6c757d';
        
        const icon = L.divIcon({
            className: 'custom-icon',
            html: `<div style="background: ${color}; color: white; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3); border: 3px solid white;"><i class="bi bi-bicycle" style="font-size: 1.2rem;"></i></div>`,
            iconSize: [45, 45]
        });
        
        const marker = L.marker([kurir.latitude, kurir.longitude], {icon: icon})
            .addTo(map)
            .bindPopup(`<strong>${kurir.name}</strong><br>Status: ${kurir.status}<br>Active: ${kurir.active_deliveries} order(s)<br><small>Updated: ${kurir.last_location_update || 'Never'}</small>`);
        
        kurirMarkers[kurir.id] = marker;
    }
});

// Auto-refresh locations every 10 seconds
setInterval(function() {
    fetch('<?php echo SITE_URL; ?>/api/get_all_kurir_locations.php', {
        credentials: 'same-origin',  // Include session cookies
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => {
            console.log('API Response Status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Fetched kurir locations:', data.length, 'kurirs');
            data.forEach(kurir => {
                if (kurir.latitude && kurir.longitude) {
                    if (kurirMarkers[kurir.id]) {
                        kurirMarkers[kurir.id].setLatLng([kurir.latitude, kurir.longitude]);
                        kurirMarkers[kurir.id].setPopupContent(
                            `<strong>${kurir.name}</strong><br>Status: ${kurir.status}<br>Active: ${kurir.active_deliveries} order(s)<br><small>Updated: ${new Date().toLocaleTimeString()}</small>`
                        );
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching locations:', error));
}, 10000);

function focusKurir(kurirId, lat, lng) {
    if (lat && lng && kurirMarkers[kurirId]) {
        map.setView([lat, lng], 15);
        kurirMarkers[kurirId].openPopup();
    } else {
        alert('Kurir location not available');
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
