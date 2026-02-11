<?php
/**
 * COD OTP Generation & Verification
 * Simulated OTP system for Cash on Delivery orders
 * 
 * POST /api/orders/cod_generate_otp.php - Generate OTP
 * POST /api/orders/cod_verify_otp.php - Verify OTP
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../jwt.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Determine action based on request URI
    $requestUri = $_SERVER['REQUEST_URI'];
    $isGenerate = strpos($requestUri, 'cod_generate_otp') !== false;
    $isVerify = strpos($requestUri, 'cod_verify_otp') !== false;

    if (!$isGenerate && !$isVerify) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid endpoint']);
        exit;
    }

    // Verify JWT token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Missing or invalid Authorization header']);
        exit;
    }

    $token = $matches[1];
    $decoded = validateJWT($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    $userId = $decoded->user_id;

    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? '';

    if (empty($orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id is required']);
        exit;
    }

    // Get order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Verify user owns order or is admin
    if ($order['user_id'] != $userId && $decoded->role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }

    if ($order['payment_method'] !== 'cod') {
        http_response_code(400);
        echo json_encode(['error' => 'This order is not COD payment']);
        exit;
    }

    // ============ GENERATE OTP ============
    if ($isGenerate) {
        // Check if user is trusted (has 5+ completed orders)
        $trustStmt = $pdo->prepare("
            SELECT COUNT(*) as completed_count 
            FROM orders 
            WHERE user_id = ? AND status = 'completed'
        ");
        $trustStmt->execute([$userId]);
        $trustData = $trustStmt->fetch();
        $isTrustedUser = $trustData['completed_count'] >= 5;

        // Generate 6-digit OTP
        $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Delete any existing OTP for this order
        $deleteStmt = $pdo->prepare("DELETE FROM cod_verifications WHERE order_id = ?");
        $deleteStmt->execute([$orderId]);

        // Insert new OTP
        $insertStmt = $pdo->prepare("
            INSERT INTO cod_verifications 
            (order_id, user_id, otp_code, is_trusted_user, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$orderId, $userId, $otpCode, $isTrustedUser, $expiresAt]);

        // If trusted user, auto-approve and update order status
        if ($isTrustedUser) {
            $verifyStmt = $pdo->prepare("
                UPDATE cod_verifications 
                SET is_verified = TRUE, verified_at = NOW() 
                WHERE order_id = ?
            ");
            $verifyStmt->execute([$orderId]);

            $orderUpdateStmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'queueing' 
                WHERE order_id = ?
            ");
            $orderUpdateStmt->execute([$orderId]);

            // Log status change
            $logStmt = $pdo->prepare("
                INSERT INTO order_status_log 
                (order_id, status, message, changed_by, changed_by_type) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $orderId,
                'queueing',
                'COD auto-approved for trusted user',
                $userId,
                'system'
            ]);

            echo json_encode([
                'success' => true,
                'auto_approved' => true,
                'message' => 'Trusted user - Order automatically approved!',
                'order_status' => 'queueing'
            ]);
        } else {
            // Send simulated OTP (display in console/alert)
            echo json_encode([
                'success' => true,
                'auto_approved' => false,
                'message' => 'OTP generated (SIMULATED - No real SMS sent)',
                'simulated_otp' => $otpCode,  // In production, this would be sent via WhatsApp/SMS
                'expires_at' => $expiresAt,
                'note' => 'This is a SIMULATED OTP. In production, it would be sent via WhatsApp API.'
            ]);
        }
    }

    // ============ VERIFY OTP ============
    if ($isVerify) {
        $otpInput = $input['otp_code'] ?? '';

        if (empty($otpInput)) {
            http_response_code(400);
            echo json_encode(['error' => 'otp_code is required']);
            exit;
        }

        // Get verification record
        $verifyStmt = $pdo->prepare("
            SELECT * FROM cod_verifications 
            WHERE order_id = ?
        ");
        $verifyStmt->execute([$orderId]);
        $verification = $verifyStmt->fetch();

        if (!$verification) {
            http_response_code(404);
            echo json_encode(['error' => 'No OTP found for this order. Please generate OTP first.']);
            exit;
        }

        if ($verification['is_verified']) {
            http_response_code(400);
            echo json_encode(['error' => 'OTP already verified']);
            exit;
        }

        // Check expiration
        if (strtotime($verification['expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(['error' => 'OTP has expired. Please request a new one.']);
            exit;
        }

        // Check attempts (max 5)
        if ($verification['attempts'] >= 5) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum attempts exceeded. Please request a new OTP.']);
            exit;
        }

        // Verify OTP
        if ($otpInput !== $verification['otp_code']) {
            // Increment attempts
            $attemptStmt = $pdo->prepare("
                UPDATE cod_verifications 
                SET attempts = attempts + 1 
                WHERE order_id = ?
            ");
            $attemptStmt->execute([$orderId]);

            $remainingAttempts = 5 - ($verification['attempts'] + 1);
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid OTP',
                'remaining_attempts' => $remainingAttempts
            ]);
            exit;
        }

        // OTP is correct - mark as verified
        $verifyUpdateStmt = $pdo->prepare("
            UPDATE cod_verifications 
            SET is_verified = TRUE, verified_at = NOW() 
            WHERE order_id = ?
        ");
        $verifyUpdateStmt->execute([$orderId]);

        // Update order status to queueing
        $orderUpdateStmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'queueing' 
            WHERE order_id = ?
        ");
        $orderUpdateStmt->execute([$orderId]);

        // Log status change
        $logStmt = $pdo->prepare("
            INSERT INTO order_status_log 
            (order_id, status, message, changed_by, changed_by_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $logStmt->execute([
            $orderId,
            'queueing',
            'COD verified with OTP',
            $userId,
            'customer'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully!',
            'order_status' => 'queueing'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'COD verification failed',
        'message' => $e->getMessage()
    ]);
}
?>
