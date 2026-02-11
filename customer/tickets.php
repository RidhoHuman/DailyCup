<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Support Tickets';
requireLogin();

if ($_SESSION['role'] !== 'customer') {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Get user's tickets
$stmt = $db->prepare("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$tickets = $stmt->fetchAll();
?>

<!-- Breadcrumb & Quick Navigation -->
<div class="bg-light py-3 border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/index.php" class="text-decoration-none">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?php echo SITE_URL; ?>/customer/contact.php" class="text-decoration-none">
                            Customer Service
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">My Tickets</li>
                </ol>
            </nav>
            <div>
                <a href="<?php echo SITE_URL; ?>/customer/contact.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Customer Service
                </a>
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-sm btn-outline-coffee">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.ticket-card {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    transition: box-shadow 0.3s;
}

.ticket-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.ticket-status {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-open {
    background: #fff3cd;
    color: #856404;
}

.status-in_progress {
    background: #d1ecf1;
    color: #0c5460;
}

.status-resolved {
    background: #d4edda;
    color: #155724;
}

.status-closed {
    background: #f8d7da;
    color: #721c24;
}

.priority-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 15px;
    font-size: 11px;
    margin-left: 10px;
}

.priority-low {
    background: #e7f5fe;
    color: #0366d6;
}

.priority-medium {
    background: #fff3cd;
    color: #856404;
}

.priority-high {
    background: #ffe3e3;
    color: #c92a2a;
}
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-ticket-detailed"></i> Support Tickets</h2>
                <a href="<?php echo SITE_URL; ?>/customer/create_ticket.php" class="btn btn-coffee">
                    <i class="bi bi-plus-circle"></i> Buat Ticket Baru
                </a>
            </div>

            <?php if (empty($tickets)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 80px; color: #ccc;"></i>
                <p class="text-muted mt-3">Belum ada support ticket</p>
                <a href="<?php echo SITE_URL; ?>/customer/create_ticket.php" class="btn btn-coffee">Buat Ticket Pertama</a>
            </div>
            <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
            <div class="ticket-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h5 class="mb-1">
                            #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
                            <span class="priority-badge priority-<?php echo $ticket['priority']; ?>">
                                <?php echo strtoupper($ticket['priority']); ?>
                            </span>
                        </h5>
                        <h6><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                    </div>
                    <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                        <?php 
                        $statusLabels = [
                            'open' => 'Open',
                            'in_progress' => 'In Progress',
                            'resolved' => 'Resolved',
                            'closed' => 'Closed'
                        ];
                        echo $statusLabels[$ticket['status']];
                        ?>
                    </span>
                </div>
                
                <div class="text-muted small mb-3">
                    <i class="bi bi-calendar"></i> <?php echo formatDate($ticket['created_at']); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-tag"></i> <?php echo ucfirst($ticket['category']); ?>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="<?php echo SITE_URL; ?>/customer/ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </a>
                    <?php if ($ticket['status'] != 'closed'): ?>
                    <a href="<?php echo SITE_URL; ?>/customer/ticket_detail.php?id=<?php echo $ticket['id']; ?>#reply" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-reply"></i> Reply
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Floating Back to Home Button -->
<style>
.container {
    padding-bottom: 100px !important;
}

.floating-home-btn {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #6F4E37 0%, #8B6F47 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(111, 78, 55, 0.3);
    text-decoration: none;
    transition: all 0.3s ease;
    z-index: 999;
    border: 3px solid white;
}

.floating-home-btn:hover {
    background: linear-gradient(135deg, #8B6F47 0%, #6F4E37 100%);
    color: white;
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 6px 20px rgba(111, 78, 55, 0.5);
}

@media (max-width: 768px) {
    .container {
        padding-bottom: 120px !important;
    }
    
    .floating-home-btn {
        width: 50px;
        height: 50px;
        font-size: 20px;
        bottom: 20px;
        right: 20px;
    }
}
</style>

<a href="<?php echo SITE_URL; ?>/index.php" class="floating-home-btn" title="Kembali ke Home">
    <i class="bi bi-house-fill"></i>
</a>

<script>
// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'h') {
        e.preventDefault();
        window.location.href = '<?php echo SITE_URL; ?>/index.php';
    }
    if (e.altKey && e.key === 'c') {
        e.preventDefault();
        window.location.href = '<?php echo SITE_URL; ?>/customer/create_ticket.php';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
