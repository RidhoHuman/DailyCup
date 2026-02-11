<?php
// Customer Service Widget Component
// Include this in footer for customer pages

require_once __DIR__ . '/../config/database.php';

// Get WhatsApp settings
$db = getDB();
$stmt = $db->query("SELECT setting_key, setting_value FROM cs_settings WHERE setting_key IN ('whatsapp_number', 'whatsapp_message', 'chat_enabled')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$waNumber = $settings['whatsapp_number'] ?? '6281234567890';
$waMessage = urlencode($settings['whatsapp_message'] ?? 'Halo DailyCup, saya butuh bantuan...');
$chatEnabled = ($settings['chat_enabled'] ?? '1') == '1';
$waLink = "https://wa.me/{$waNumber}?text={$waMessage}";

// Check if user logged in
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer';
?>

<!-- Customer Service CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/customer-service.css">

<!-- WhatsApp Floating Button -->
<a href="<?php echo $waLink; ?>" target="_blank" class="wa-float" title="Chat via WhatsApp">
    <i class="bi bi-whatsapp"></i>
    <div class="wa-tooltip">
        Chat via WhatsApp
    </div>
</a>

<?php if ($chatEnabled && $isLoggedIn): ?>
<!-- Live Chat Widget Button -->
<div class="chat-widget-btn" id="chatWidgetBtn">
    <i class="bi bi-chat-dots"></i>
    <span class="badge" id="unreadChatCount" style="display: none;">0</span>
</div>

<!-- Chat Widget Window -->
<div class="chat-widget" id="chatWidget">
    <div class="chat-widget-header">
        <div>
            <h5>💬 Live Chat</h5>
            <small>Customer Service</small>
        </div>
        <button class="chat-widget-close" id="chatWidgetClose">×</button>
    </div>
    
    <div class="chat-widget-messages" id="chatMessages">
        <!-- Messages will be loaded here -->
    </div>
    
    <div class="chat-widget-input">
        <form id="chatForm" class="chat-input-group">
            <input type="text" id="chatInput" placeholder="Ketik pesan..." required autocomplete="off">
            <button type="submit">
                <i class="bi bi-send"></i>
            </button>
        </form>
    </div>
</div>

<script>
// Chat Widget JavaScript
const chatWidget = document.getElementById('chatWidget');
const chatWidgetBtn = document.getElementById('chatWidgetBtn');
const chatWidgetClose = document.getElementById('chatWidgetClose');
const chatMessages = document.getElementById('chatMessages');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const unreadCount = document.getElementById('unreadChatCount');

let lastMessageId = 0;
let chatInterval = null;

// Toggle chat widget
chatWidgetBtn.addEventListener('click', function() {
    chatWidget.classList.toggle('active');
    if (chatWidget.classList.contains('active')) {
        loadMessages();
        startPolling();
        markAsRead();
    } else {
        stopPolling();
    }
});

chatWidgetClose.addEventListener('click', function() {
    chatWidget.classList.remove('active');
    stopPolling();
});

// Send message
chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const message = chatInput.value.trim();
    if (!message) return;
    
    fetch('<?php echo SITE_URL; ?>/api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'send',
            message: message
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            chatInput.value = '';
            loadMessages();
        }
    });
});

// Load messages
function loadMessages() {
    fetch('<?php echo SITE_URL; ?>/api/chat.php?action=get&last_id=' + lastMessageId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    addMessageToChat(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
                scrollToBottom();
            }
        });
}

// Add message to chat
function addMessageToChat(msg) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message ' + msg.sender_type;
    messageDiv.innerHTML = `
        <div class="chat-message-bubble">
            <div>${escapeHtml(msg.message)}</div>
            <div class="chat-message-time">${formatTime(msg.created_at)}</div>
        </div>
    `;
    chatMessages.appendChild(messageDiv);
}

// Scroll to bottom
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Start polling for new messages
function startPolling() {
    chatInterval = setInterval(loadMessages, 3000); // Check every 3 seconds
}

// Stop polling
function stopPolling() {
    if (chatInterval) {
        clearInterval(chatInterval);
        chatInterval = null;
    }
}

// Mark messages as read
function markAsRead() {
    fetch('<?php echo SITE_URL; ?>/api/chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ action: 'mark_read' })
    });
    unreadCount.style.display = 'none';
}

// Check for unread messages periodically
setInterval(function() {
    // Only check if user is logged in and widget is not active
    if (typeof window.IS_LOGGED_IN !== 'undefined' && window.IS_LOGGED_IN && !chatWidget.classList.contains('active')) {
        fetch('<?php echo SITE_URL; ?>/api/chat.php?action=unread_count')
            .then(res => {
                if (!res.ok) throw new Error('Not authorized');
                return res.json();
            })
            .then(data => {
                if (data.count > 0) {
                    unreadCount.textContent = data.count;
                    unreadCount.style.display = 'flex';
                } else {
                    unreadCount.style.display = 'none';
                }
            })
            .catch(err => {
                // Silently fail if not authorized
                console.debug('Chat check skipped:', err.message);
            });
    }
}, 5000);

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}
</script>
<?php endif; ?>
