<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}

$code = strtoupper(sanitizeInput($_POST['code'] ?? ''));
$userId = $_SESSION['user_id'];

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Kode tidak boleh kosong']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Check if code exists and not used
    $stmt = $db->prepare("SELECT * FROM redeem_codes WHERE code = ? AND is_used = 0");
    $stmt->execute([$code]);
    $redeemCode = $stmt->fetch();

    if (!$redeemCode) {
        echo json_encode(['success' => false, 'message' => 'Kode tidak valid atau sudah digunakan']);
        $db->rollBack();
        exit;
    }

    $points = $redeemCode['points'];

    // Update redeem_code status
    $stmt = $db->prepare("UPDATE redeem_codes SET is_used = 1, used_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['name'], $redeemCode['id']]);

    // Add points to user
    $stmt = $db->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
    $stmt->execute([$points, $userId]);

    // Record transaction
    $stmt = $db->prepare("INSERT INTO loyalty_transactions (user_id, points, transaction_type, description, created_at) VALUES (?, ?, 'earned', ?, NOW())");
    $stmt->execute([$userId, $points, "Redeem Code: $code"]);
    
    // CREATE NOTIFICATION for redeem code success
    createNotification(
        $userId,
        "Kode Redeem Berhasil!",
        "Selamat! Kode $code berhasil ditukarkan. Anda mendapatkan $points poin loyalty.",
        'redeem_success',
        null
    );

    $db->commit();

    echo json_encode([
        'success' => true, 
        'message' => "Selamat! Anda mendapatkan $points poin."
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal memproses kode: ' . $e->getMessage()]);
}
