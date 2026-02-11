/**
 * Notification System JavaScript
 */

// Notification settings
const NOTIFICATION_CHECK_INTERVAL = 30000; // 30 seconds
let notificationCheckTimer = null;
let isLoadingNotifications = false; // Prevent duplicate requests
// Reference global SITE_URL_JS from main.js (no redeclaration)

// Initialize notifications
document.addEventListener('DOMContentLoaded', function() {
    // Only log if on notifications page
    const isNotificationPage = window.location.pathname.includes('notification');
    if (isNotificationPage) {
        console.log('Notification system initializing...');
    }
    
    loadNotifications();
    startNotificationPolling();
    initNotificationEvents();
});

/**
 * Load notifications from server
 */
function loadNotifications() {
    const SITE_URL_JS = window.SITE_URL_JS;
    const isNotificationPage = window.location.pathname.includes('notification');
    
    // Check if user is logged in
    if (typeof window.IS_LOGGED_IN !== 'undefined' && !window.IS_LOGGED_IN) {
        if (isNotificationPage) {
            console.log('User not logged in, skipping notification load');
        }
        return;
    }

    // Prevent duplicate requests
    if (isLoadingNotifications) {
        return;
    }
    
    isLoadingNotifications = true;
    if (isNotificationPage) {
        console.log('Loading notifications from API...');
    }
    
    // Show loading state only if container exists
    const container = document.getElementById('notificationsList');
    if (container && !container.querySelector('.notification-item')) {
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-coffee mx-auto"></div>
                <p class="mt-3 text-muted">Memuat notifikasi...</p>
            </div>
        `;
    }
    
    fetch(`${SITE_URL_JS}/api/notifications.php?action=get`)
        .then(response => {
            if (isNotificationPage) {
                console.log('Notification API response status:', response.status);
            }
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (isNotificationPage) {
                console.log('Notification API data:', data);
            }
            isLoadingNotifications = false;
            
            if (data.success) {
                // Validate notifications array
                const notifications = Array.isArray(data.notifications) ? data.notifications : [];
                if (isNotificationPage) {
                    console.log('Loaded', notifications.length, 'notifications');
                }
                
                // Only update display if container exists (on notifications page)
                const container = document.getElementById('notificationsList');
                if (container) {
                    updateNotificationDisplay(notifications);
                }
                
                // Always update badge count (shown on all pages)
                updateNotificationCount(data.unread_count || 0);
            } else {
                console.error('API returned error:', data.message);
                showNotificationError(data.message || 'Gagal memuat notifikasi');
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            isLoadingNotifications = false;
            showNotificationError('Gagal menghubungi server. Silakan refresh halaman.');
        });
}

/**
 * Update notification count badge
 */
function updateNotificationCount(count) {
    // ONLY target notification badges, NOT cart badges!
    const badges = document.querySelectorAll('.notification-count');
    badges.forEach(badge => {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    });
}

/**
 * Update notification display
 */
function updateNotificationDisplay(notifications) {
    const container = document.getElementById('notificationsList');
    if (!container) {
        // Only warn if we're on the notifications page
        const currentPage = window.location.pathname;
        if (currentPage.includes('notification')) {
            console.warn('⚠️ Notification container not found on notifications page');
        }
        // Silently skip on other pages - this is normal
        return;
    }
    
    console.log('Updating notification display with', notifications.length, 'items');
    
    // Ensure notifications is an array
    if (!Array.isArray(notifications)) {
        console.error('Notifications is not an array:', notifications);
        notifications = [];
    }
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
                <p class="mt-3 text-muted">Tidak ada notifikasi</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        const isRead = notif.is_read == 1;
        const bgClass = isRead ? '' : 'bg-light';
        
        html += `
            <div class="notification-item ${bgClass} border-bottom p-3" 
                 data-notif-id="${notif.id}" 
                 style="cursor: pointer;"
                 onclick="markAsRead(${notif.id})">
                <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1 ${isRead ? 'text-muted' : 'fw-bold'}">
                            ${escapeHtml(notif.title || 'Notifikasi')}
                        </h6>
                        <p class="mb-1 small ${isRead ? 'text-muted' : ''}">
                            ${escapeHtml(notif.message || '')}
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> ${formatNotificationTime(notif.created_at)}
                        </small>
                    </div>
                    ${!isRead ? '<span class="badge bg-primary ms-2">Baru</span>' : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    console.log('Notification display updated successfully');
}

/**
 * Show notification error message
 */
function showNotificationError(message) {
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
            <p class="mt-3 text-danger">${escapeHtml(message)}</p>
            <button class="btn btn-coffee btn-sm" onclick="loadNotifications()">
                <i class="bi bi-arrow-clockwise"></i> Coba Lagi
            </button>
        </div>
    `;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Mark notification as read
 */
function markAsRead(notificationId) {
    const SITE_URL_JS = window.SITE_URL_JS;
    fetch(`${SITE_URL_JS}/api/notifications.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_read',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

/**
 * Mark all notifications as read
 */
function markAllAsRead() {
    const SITE_URL_JS = window.SITE_URL_JS;
    fetch(`${SITE_URL_JS}/api/notifications.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'mark_all_read'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            if (window.DailyCup && window.DailyCup.showAlert) {
                window.DailyCup.showAlert('Semua notifikasi ditandai sebagai dibaca', 'success');
            }
        }
    })
    .catch(error => console.error('Error marking all read:', error));
}

/**
 * Delete notification
 */
function deleteNotification(notificationId) {
    const SITE_URL_JS = window.SITE_URL_JS;
    if (!confirm('Hapus notifikasi ini?')) {
        return;
    }
    
    fetch(`${SITE_URL_JS}/api/notifications.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete',
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.error('Error deleting notification:', error));
}

/**
 * Start polling for new notifications
 */
function startNotificationPolling() {
    // Only poll if user is logged in
    if (typeof window.IS_LOGGED_IN === 'undefined' || !window.IS_LOGGED_IN) {
        console.debug('Notification polling skipped: user not logged in');
        return;
    }
    
    checkNewNotifications();
    notificationCheckTimer = setInterval(() => {
        checkNewNotifications();
    }, NOTIFICATION_CHECK_INTERVAL);
}

/**
 * Stop polling
 */
function stopNotificationPolling() {
    if (notificationCheckTimer) {
        clearInterval(notificationCheckTimer);
        notificationCheckTimer = null;
    }
}

/**
 * Check for new notifications
 */
function checkNewNotifications() {
    const SITE_URL_JS = window.SITE_URL_JS;
    
    // Skip if user is not logged in
    if (typeof window.IS_LOGGED_IN === 'undefined' || !window.IS_LOGGED_IN) {
        return;
    }
    
    fetch(`${SITE_URL_JS}/api/notifications.php?action=check_new`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.has_new) {
                updateNotificationCount(data.unread_count || 0);
                
                if (data.latest_notification) {
                    showToastNotification(data.latest_notification);
                }
            } else if (data.success) {
                updateNotificationCount(data.unread_count || 0);
            }
        })
        .catch(error => {
            // Silent fail for background polling - don't spam console
            // Only log if debugging is needed
            // console.error('Error checking notifications:', error);
        });
}

/**
 * Show toast notification
 */
function showToastNotification(notification) {
    const toastHTML = `
        <div class="toast align-items-center text-white bg-coffee border-0 position-fixed top-0 end-0 m-3" 
             role="alert" style="z-index: 9999; background-color: #6F4E37 !important;">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${notification.title}</strong><br>
                    ${notification.message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    const div = document.createElement('div');
    div.innerHTML = toastHTML;
    document.body.appendChild(div.firstElementChild);
    
    const toastElement = document.querySelector('.toast');
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

/**
 * Format notification time
 */
function formatNotificationTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit yang lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam yang lalu`;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

/**
 * Initialize notification events
 */
function initNotificationEvents() {
    const markAllBtn = document.getElementById('markAllReadBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', markAllAsRead);
    }
    
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopNotificationPolling();
        else startNotificationPolling();
    });
}

// Make functions available globally
window.markAsRead = markAsRead;
window.markAllAsRead = markAllAsRead;
window.deleteNotification = deleteNotification;
window.loadNotifications = loadNotifications;
