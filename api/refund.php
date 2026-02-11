<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

$db = getDB();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Define refund auto-approve threshold
define('AUTO_APPROVE_THRESHOLD', 50000); // Rp 50.000
define('MAX_REFUNDS_PER_MONTH', 3); // Max 3 refunds per 30 days

if ($action === 'request_refund') {
    try {
        $orderId = intval($_POST['order_id'] ?? 0);
        $reason = sanitizeInput($_POST['reason'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $refundMethod = sanitizeInput($_POST['refund_method'] ?? 'loyalty_points');
        
        // Validate required fields
        if (!$orderId || !$reason || !$description) {
            echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
            exit;
        }
        
        // Verify order ownership and status
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
            exit;
        }
        
        if ($order['status'] !== 'completed') {
            echo json_encode(['success' => false, 'message' => 'Refund hanya bisa dilakukan untuk order yang sudah completed']);
            exit;
        }
        
        // Check 3-day window
        $completedTime = strtotime($order['updated_at']);
        $daysSinceCompletion = (time() - $completedTime) / (60 * 60 * 24);
        
        if ($daysSinceCompletion > 3) {
            echo json_encode(['success' => false, 'message' => 'Periode refund telah berakhir. Refund hanya dapat dilakukan dalam 3 hari setelah order completed']);
            exit;
        }
        
        // Check if refund already requested
        $stmt = $db->prepare("SELECT id FROM returns WHERE order_id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Refund sudah pernah diajukan untuk order ini']);
            exit;
        }
        
        // Check refund limit (max 3 per 30 days)
        $stmt = $db->prepare("SELECT COUNT(*) FROM returns WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$userId]);
        $refundCount = $stmt->fetchColumn();
        
        if ($refundCount >= MAX_REFUNDS_PER_MONTH) {
            echo json_encode(['success' => false, 'message' => 'Anda telah mencapai limit refund (max ' . MAX_REFUNDS_PER_MONTH . ' kali dalam 30 hari)']);
            exit;
        }
        
        // Handle image uploads
        $uploadedImages = [];
        if (isset($_FILES['proof_images']) && $_FILES['proof_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $uploadDir = __DIR__ . '/../assets/images/returns/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $totalFiles = count($_FILES['proof_images']['name']);
            if ($totalFiles > 3) {
                echo json_encode(['success' => false, 'message' => 'Maksimal 3 foto']);
                exit;
            }
            
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['proof_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['proof_images']['tmp_name'][$i];
                    $fileSize = $_FILES['proof_images']['size'][$i];
                    $fileType = $_FILES['proof_images']['type'][$i];
                    
                    // Validate image
                    if ($fileSize > 2 * 1024 * 1024) { // 2MB max
                        echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB']);
                        exit;
                    }
                    
                    if (!in_array($fileType, ['image/jpeg', 'image/jpg', 'image/png'])) {
                        echo json_encode(['success' => false, 'message' => 'Format file harus JPG atau PNG']);
                        exit;
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($_FILES['proof_images']['name'][$i], PATHINFO_EXTENSION);
                    $newFilename = 'refund_' . $orderId . '_' . time() . '_' . $i . '.' . $extension;
                    
                    if (move_uploaded_file($tmpName, $uploadDir . $newFilename)) {
                        $uploadedImages[] = $newFilename;
                    }
                }
            }
            
            if (empty($uploadedImages)) {
                echo json_encode(['success' => false, 'message' => 'Foto bukti produk wajib dilampirkan']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Foto bukti produk wajib dilampirkan']);
            exit;
        }
        
        // Handle bank account info if bank transfer
        $bankName = null;
        $bankAccountNumber = null;
        $bankAccountName = null;
        
        if ($refundMethod === 'bank_transfer') {
            $bankName = sanitizeInput($_POST['bank_name'] ?? '');
            $bankAccountNumber = sanitizeInput($_POST['bank_account_number'] ?? '');
            $bankAccountName = sanitizeInput($_POST['bank_account_name'] ?? '');
            
            if (!$bankName || !$bankAccountNumber || !$bankAccountName) {
                echo json_encode(['success' => false, 'message' => 'Informasi rekening bank wajib diisi untuk refund via bank transfer']);
                exit;
            }
        }
        
        // Determine auto-approve
        $refundAmount = $order['final_amount'];
        $autoApproved = ($refundAmount < AUTO_APPROVE_THRESHOLD) ? 1 : 0;
        $status = $autoApproved ? 'approved' : 'pending';
        $refundProcessed = 0;
        
        $db->beginTransaction();
        
        // Insert refund request
        $stmt = $db->prepare("INSERT INTO returns (order_id, user_id, reason, description, proof_images, status, refund_amount, refund_method, bank_name, bank_account_number, bank_account_name, auto_approved, refund_processed, processed_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $orderId,
            $userId,
            $reason,
            $description,
            json_encode($uploadedImages),
            $status,
            $refundAmount,
            $refundMethod,
            $bankName,
            $bankAccountNumber,
            $bankAccountName,
            $autoApproved,
            $refundProcessed,
            $autoApproved ? date('Y-m-d H:i:s') : null
        ]);
        
        $refundId = $db->lastInsertId();
        
        // If auto-approved and loyalty points method, process immediately
        if ($autoApproved && $refundMethod === 'loyalty_points') {
            // Calculate points to return (amount / rupiah_per_point, or just use amount as points)
            $stmt = $db->prepare("SELECT rupiah_per_point FROM loyalty_settings WHERE is_active = 1 LIMIT 1");
            $stmt->execute();
            $setting = $stmt->fetch();
            $rupiahPerPoint = $setting ? $setting['rupiah_per_point'] : 100;
            
            $pointsToReturn = intval($refundAmount / $rupiahPerPoint);
            
            // Update user loyalty points
            $stmt = $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt->execute([$pointsToReturn, $userId]);
            
            // Log loyalty transaction
            $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, transaction_type, points, description, order_id) 
                                 VALUES (?, 'earned', ?, ?, ?)");
            $stmt->execute([
                $userId,
                $pointsToReturn,
                "Refund order #{$order['order_number']} (Auto-approved)",
                $orderId
            ]);
            
            // Mark refund as processed
            $stmt = $db->prepare("UPDATE returns SET refund_processed = 1 WHERE id = ?");
            $stmt->execute([$refundId]);
            
            // Create notification
            createNotification(
                $userId,
                "Refund Approved & Processed!",
                "Refund Anda sebesar " . formatCurrency($refundAmount) . " telah disetujui dan {$pointsToReturn} loyalty points telah ditambahkan ke akun Anda.",
                'refund_approved',
                $orderId
            );
            
            $message = "Refund berhasil diajukan dan otomatis disetujui! {$pointsToReturn} loyalty points telah ditambahkan ke akun Anda.";
        } else {
            // Create notification for pending review
            createNotification(
                $userId,
                "Refund Request Submitted",
                "Permintaan refund Anda untuk order #{$order['order_number']} sedang ditinjau oleh admin.",
                'refund_pending',
                $orderId
            );
            
            $message = "Refund berhasil diajukan. Tim kami akan meninjau permintaan Anda dalam 1-24 jam.";
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'auto_approved' => $autoApproved
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
