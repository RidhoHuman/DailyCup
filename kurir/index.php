<?php
require_once __DIR__ . '/../includes/functions.php';

// Kurir login check
if (!isset($_SESSION['kurir_id'])) {
    header('Location: ' . SITE_URL . '/kurir/login.php');
    exit;
}

$db = getDB();
$kurirId = $_SESSION['kurir_id'];

// Get kurir info
$stmt = $db->prepare("SELECT * FROM kurir WHERE id = ?");
$stmt->execute([$kurirId]);
$kurir = $stmt->fetch();

if (!$kurir || !$kurir['is_active']) {
    session_destroy();
    header('Location: ' . SITE_URL . '/kurir/login.php?error=inactive');
    exit;
}

// Get active deliveries
$stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.phone as customer_phone
                     FROM orders o
                     JOIN users u ON o.user_id = u.id
                     WHERE o.kurir_id = ? AND o.status IN ('confirmed', 'processing', 'ready', 'delivering')
                     ORDER BY o.created_at ASC");
$stmt->execute([$kurirId]);
$activeOrders = $stmt->fetchAll();

// Get completed deliveries today
$stmt = $db->prepare("SELECT COUNT(*) FROM orders 
                     WHERE kurir_id = ? 
                     AND status = 'completed' 
                     AND DATE(updated_at) = CURDATE()");
$stmt->execute([$kurirId]);
$todayDeliveries = $stmt->fetchColumn();

// Get total earnings today (can be enhanced with commission system)
$stmt = $db->prepare("SELECT SUM(final_amount) FROM orders 
                     WHERE kurir_id = ? 
                     AND status = 'completed' 
                     AND DATE(updated_at) = CURDATE()");
$stmt->execute([$kurirId]);
$todayEarnings = $stmt->fetchColumn() ?: 0;

// Get unread notifications count
$stmt = $db->prepare("SELECT COUNT(*) FROM kurir_notifications 
                     WHERE kurir_id = ? AND is_read = 0");
$stmt->execute([$kurirId]);
$unreadNotifications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurir Dashboard - DailyCup</title>
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
            padding-bottom: 80px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .kurir-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #8B4513 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-available { background: var(--success-color); color: white; }
        .status-busy { background: var(--warning-color); color: #000; }
        .status-offline { background: var(--danger-color); color: white; }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-align: center;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #666;
            font-size: 0.85rem;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
        }
        
        .order-card.urgent {
            border-left-color: var(--danger-color);
            background: #fff5f5;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-number {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .order-time {
            font-size: 0.85rem;
            color: #666;
        }
        
        .customer-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .btn-kurir {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-kurir:hover {
            background: #5a3d2a;
            color: white;
        }
        
        .btn-pickup {
            background: var(--warning-color);
            color: #000;
        }
        
        .btn-deliver {
            background: var(--success-color);
            color: white;
        }
        
        .btn-complete {
            background: #17a2b8;
            color: white;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 2px solid #e0e0e0;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .bottom-nav .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #666;
            text-decoration: none;
            padding: 5px;
        }
        
        .bottom-nav .nav-link.active {
            color: var(--primary-color);
        }
        
        .bottom-nav .nav-link i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="kurir-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Hi, <?php echo htmlspecialchars($kurir['name']); ?>! 👋</h5>
                <small>ID: <?php echo $kurir['id']; ?> | <?php echo ucfirst($kurir['vehicle_type']); ?>: <?php echo htmlspecialchars($kurir['vehicle_number']); ?></small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Notification Bell -->
                <a href="#" class="btn btn-light btn-sm position-relative" id="notificationBtn" data-bs-toggle="modal" data-bs-target="#notificationModal">
                    <i class="bi bi-bell-fill"></i>
                    <?php if ($unreadNotifications > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $unreadNotifications; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <span class="status-badge status-<?php echo $kurir['status']; ?>">
                    <i class="bi bi-circle-fill"></i>
                    <?php echo ucfirst($kurir['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="row text-center">
            <div class="col-6">
                <div class="fw-bold fs-4"><?php echo count($activeOrders); ?></div>
                <small>Active Orders</small>
            </div>
            <div class="col-6">
                <div class="fw-bold fs-4"><?php echo $todayDeliveries; ?></div>
                <small>Delivered Today</small>
            </div>
        </div>
    </div>
    
    <!-- Today Stats -->
    <div class="container mt-3">
        <div class="row">
            <div class="col-6">
                <div class="stat-card">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                    <div class="stat-value"><?php echo $kurir['total_deliveries']; ?></div>
                    <div class="stat-label">Total Deliveries</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <i class="bi bi-star-fill text-warning" style="font-size: 2rem;"></i>
                    <div class="stat-value"><?php echo number_format($kurir['rating'], 1); ?></div>
                    <div class="stat-label">Rating</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Orders -->
    <div class="container">
        <h6 class="mt-3 mb-3 fw-bold">
            <i class="bi bi-box-seam"></i> Active Deliveries
        </h6>
        
        <?php if (count($activeOrders) > 0): ?>
            <?php foreach ($activeOrders as $order): ?>
            <div class="order-card <?php echo $order['status'] === 'ready' ? 'urgent' : ''; ?>">
                <div class="order-header">
                    <div class="order-number">#<?php echo substr($order['order_number'], -8); ?></div>
                    <span class="badge bg-<?php echo $order['status'] === 'confirmed' ? 'primary' : ($order['status'] === 'processing' ? 'info' : ($order['status'] === 'ready' ? 'warning' : 'success')); ?>">
                        <?php echo strtoupper($order['status']); ?>
                    </span>
                </div>
                
                <div class="customer-info">
                    <div class="mb-2">
                        <i class="bi bi-person-fill"></i>
                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-telephone-fill"></i>
                        <a href="tel:<?php echo $order['customer_phone']; ?>"><?php echo $order['customer_phone']; ?></a>
                    </div>
                    <div>
                        <i class="bi bi-geo-alt-fill"></i>
                        <small><?php echo htmlspecialchars($order['delivery_address']); ?></small>
                    </div>
                </div>
                
                <div class="order-time">
                    <i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?> WIB
                </div>
                
                <!-- View Detail Button -->
                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="bi bi-eye"></i> Lihat Detail & Upload Foto
                </a>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No active deliveries</h5>
                <p>Enjoy your break! New orders will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">
                        <i class="bi bi-bell-fill"></i> Notifikasi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="notificationList">
                    <div class="text-center p-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                        <i class="bi bi-check-all"></i> Tandai Semua Sudah Dibaca
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #fff3cd;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        #notificationBtn .badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }
    </style>
    
    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <div class="row text-center g-0">
            <div class="col">
                <a href="index.php" class="nav-link active">
                    <i class="bi bi-house-fill"></i>
                    <small>Home</small>
                </a>
            </div>
            <div class="col">
                <a href="history.php" class="nav-link">
                    <i class="bi bi-clock-history"></i>
                    <small>History</small>
                </a>
            </div>
            <div class="col">
                <a href="profile.php" class="nav-link">
                    <i class="bi bi-person-fill"></i>
                    <small>Profile</small>
                </a>
            </div>
            <div class="col">
                <a href="logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <small>Logout</small>
                </a>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);

// GPS Location Tracking (auto-update every 10 seconds)
if (navigator.geolocation) {
    function updateLocation() {
        navigator.geolocation.getCurrentPosition(function(position) {
            const data = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            };
            
            fetch('<?php echo SITE_URL; ?>/api/update_kurir_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('Location updated:', result.timestamp);
                } else {
                    console.error('Failed to update location:', result.message);
                }
            })
            .catch(error => {
                console.error('Error updating location:', error);
            });
        }, function(error) {
            console.error('Geolocation error:', error.message);
        }, {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        });
    }
    
    // Update location immediately
    updateLocation();
    
    // Then update every 10 seconds
    setInterval(updateLocation, 10000);
} else {
    console.error('Geolocation is not supported by this browser.');
}

// Notification System
function loadNotifications() {
    fetch('<?php echo SITE_URL; ?>/api/kurir_notifications.php?action=get&limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationUI(data.notifications, data.unread_count);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function updateNotificationUI(notifications, unreadCount) {
    const badge = document.querySelector('#notificationBtn .badge');
    const list = document.getElementById('notificationList');
    
    // Update badge
    if (unreadCount > 0) {
        if (badge) {
            badge.textContent = unreadCount;
            badge.style.display = '';
        }
    } else {
        if (badge) badge.style.display = 'none';
    }
    
    // Update list
    if (notifications.length === 0) {
        list.innerHTML = '<div class="text-center text-muted p-3">Tidak ada notifikasi</div>';
        return;
    }
    
    list.innerHTML = notifications.map(notif => {
        const isUnread = notif.is_read == 0;
        const time = new Date(notif.created_at).toLocaleString('id-ID');
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notif.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1" onclick="markAsRead(${notif.id})">
                        <h6 class="mb-1">${notif.title}</h6>
                        <p class="mb-1 small">${notif.message}</p>
                        <small class="text-muted">${time}</small>
                    </div>
                    ${isUnread ? '<span class="badge bg-danger">New</span>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

function markAsRead(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    
    fetch('<?php echo SITE_URL; ?>/api/kurir_notifications.php?action=mark_read', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

function markAllAsRead() {
    fetch('<?php echo SITE_URL; ?>/api/kurir_notifications.php?action=mark_all_read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

// Load notifications on page load
loadNotifications();

// Refresh notifications every 30 seconds
setInterval(loadNotifications, 30000);
</script>
</body>
</html>
