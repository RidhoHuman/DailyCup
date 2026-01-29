<?php
/**
 * HMAC Webhook Signature Verification
 * 
 * Secure verification for Xendit and other payment provider webhooks.
 */

class WebhookSignature {
    
    /**
     * Verify Xendit webhook signature
     * 
     * Xendit sends signature in X-Callback-Signature header
     * using HMAC-SHA256 with your webhook secret key
     */
    public static function verifyXendit(): bool {
        $webhookSecret = getenv('XENDIT_WEBHOOK_SECRET');
        
        // If no webhook secret configured, fall back to callback token
        if (empty($webhookSecret)) {
            return self::verifyXenditCallbackToken();
        }
        
        $signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        
        if (empty($signature) || empty($payload)) {
            error_log('WEBHOOK: Missing signature or payload');
            return false;
        }
        
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        // Timing-safe comparison
        $isValid = hash_equals($expectedSignature, $signature);
        
        if (!$isValid) {
            error_log('WEBHOOK: Signature mismatch');
            error_log('WEBHOOK: Expected: ' . $expectedSignature);
            error_log('WEBHOOK: Received: ' . $signature);
        }
        
        return $isValid;
    }
    
    /**
     * Fallback: Verify using X-Callback-Token (less secure but simpler)
     */
    public static function verifyXenditCallbackToken(): bool {
        $expectedToken = getenv('XENDIT_CALLBACK_TOKEN');
        
        if (empty($expectedToken)) {
            error_log('WEBHOOK: No XENDIT_CALLBACK_TOKEN configured');
            return false;
        }
        
        // Token can be in header or query string
        $token = $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? $_GET['token'] ?? '';
        
        return hash_equals($expectedToken, $token);
    }
    
    /**
     * Generic HMAC verification for other providers
     */
    public static function verifyHMAC(string $secret, string $signatureHeader = 'X-Signature', string $algorithm = 'sha256'): bool {
        $signature = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($signatureHeader))] ?? '';
        $payload = file_get_contents('php://input');
        
        if (empty($signature) || empty($payload)) {
            return false;
        }
        
        $expectedSignature = hash_hmac($algorithm, $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Verify Stripe webhook signature
     */
    public static function verifyStripe(): bool {
        $endpointSecret = getenv('STRIPE_WEBHOOK_SECRET');
        
        if (empty($endpointSecret)) {
            error_log('WEBHOOK: No STRIPE_WEBHOOK_SECRET configured');
            return false;
        }
        
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        
        if (empty($sigHeader)) {
            return false;
        }
        
        // Parse Stripe signature header
        $sigParts = [];
        foreach (explode(',', $sigHeader) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $sigParts[$kv[0]] = $kv[1];
            }
        }
        
        $timestamp = $sigParts['t'] ?? '';
        $signature = $sigParts['v1'] ?? '';
        
        if (empty($timestamp) || empty($signature)) {
            return false;
        }
        
        // Verify timestamp is recent (within 5 minutes)
        if (abs(time() - (int)$timestamp) > 300) {
            error_log('WEBHOOK: Stripe timestamp too old');
            return false;
        }
        
        // Calculate expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $endpointSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Verify Midtrans webhook signature
     */
    public static function verifyMidtrans(): bool {
        $serverKey = getenv('MIDTRANS_SERVER_KEY');
        
        if (empty($serverKey)) {
            error_log('WEBHOOK: No MIDTRANS_SERVER_KEY configured');
            return false;
        }
        
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);
        
        if (!$data) {
            return false;
        }
        
        // Midtrans signature format: SHA512(order_id + status_code + gross_amount + server_key)
        $orderId = $data['order_id'] ?? '';
        $statusCode = $data['status_code'] ?? '';
        $grossAmount = $data['gross_amount'] ?? '';
        $signatureKey = $data['signature_key'] ?? '';
        
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        return hash_equals($expectedSignature, $signatureKey);
    }
    
    /**
     * Enforce webhook verification (verify and exit if invalid)
     */
    public static function enforce(string $provider = 'xendit'): void {
        $isValid = false;
        
        switch (strtolower($provider)) {
            case 'xendit':
                $isValid = self::verifyXendit();
                break;
            case 'stripe':
                $isValid = self::verifyStripe();
                break;
            case 'midtrans':
                $isValid = self::verifyMidtrans();
                break;
            default:
                error_log('WEBHOOK: Unknown provider: ' . $provider);
        }
        
        if (!$isValid) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'Invalid webhook signature'
            ]);
            exit;
        }
    }
    
    /**
     * Log webhook for audit trail
     */
    public static function log(string $provider, bool $verified, ?array $data = null): void {
        $logDir = __DIR__ . '/../data/webhook_logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'provider' => $provider,
            'verified' => $verified,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];
        
        $logFile = $logDir . date('Y-m-d') . '.json';
        $logs = [];
        
        if (file_exists($logFile)) {
            $logs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        
        $logs[] = $logEntry;
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
}
