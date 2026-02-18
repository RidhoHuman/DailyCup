<?php
require_once __DIR__ . '/../cors.php';
/**
 * Email Service BARU dengan PHPMailer
 * Menggantikan mail() function dengan PHPMailer yang BENAR-BENAR BISA kirim email
 */

// Load PHPMailer from C:\laragon\www\DailyCup\vendor\
// Path: webapp/backend/api/email/ → ../../../../vendor/autoload.php = DailyCup/vendor/
$vendorPath = __DIR__ . '/../../../../vendor/autoload.php';

if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    // Fallback ke absolute path
    $absolutePath = 'C:/laragon/www/DailyCup/vendor/autoload.php';
    if (file_exists($absolutePath)) {
        require_once $absolutePath;
    } else {
        error_log("PHPMailer not found at: $vendorPath or $absolutePath");
        die("ERROR: PHPMailer not installed.\nRun: cd C:\\laragon\\www\\DailyCup && composer require phpmailer/phpmailer\n");
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailServiceV2 {
    private static $fromEmail;
    private static $fromName;
    private static $appUrl;
    private static $enabled;
    
    /**
     * Initialize email service
     */
    public static function init() {
        self::$fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@dailycup.com';
        self::$fromName = getenv('SMTP_FROM_NAME') ?: 'DailyCup Coffee Shop';
        self::$appUrl = getenv('APP_URL') ?: '';
        if (empty(self::$appUrl)) {
            error_log('ERROR: APP_URL environment variable not set');
        }
        self::$enabled = getenv('SMTP_ENABLED') === 'true' || getenv('SMTP_ENABLED') === '1';
    }
    
    /**
     * Send email using PHPMailer
     */
    public static function send($to, $subject, $htmlBody, $data = []) {
        self::init();
        
        // Skip if email disabled
        if (!self::$enabled) {
            error_log("Email sending disabled");
            return true;
        }
        
        // Validate
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email: $to");
            return false;
        }
        
        // Get SMTP config
        $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtpPort = getenv('SMTP_PORT') ?: 587;
        $smtpUser = getenv('SMTP_USERNAME');
        $smtpPass = getenv('SMTP_PASSWORD');
        
        if (empty($smtpUser) || empty($smtpPass)) {
            error_log("SMTP credentials not configured");
            return false;
        }
        
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            
            // Use correct encryption based on port
            if ($smtpPort == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // TLS
            }
            
            $mail->Port       = $smtpPort;

            // Recipients
            $mail->setFrom(self::$fromEmail, self::$fromName);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            error_log("Email sent successfully to: $to");
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to send email to $to: {$mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Load email template
     */
    public static function loadTemplate($templateName, $data = []) {
        $templatePath = __DIR__ . '/../../templates/email/' . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            error_log("Email template not found: $templatePath");
            return '';
        }
        
        $html = file_get_contents($templatePath);
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value ?? '', $html);
        }
        
        // Replace app_url
        $html = str_replace('{{app_url}}', self::$appUrl, $html);
        
        return $html;
    }
    
    /**
     * Send order confirmation email
     */
    public static function sendOrderConfirmation($order, $customer) {
        $subject = "Order Confirmation - " . $order['order_number'];
        
        // Format items for email
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>Rp " . number_format($item['price'], 0, ',', '.') . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>Rp " . number_format($itemTotal, 0, ',', '.') . "</td>
                </tr>
            ";
        }
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'order_date' => date('d F Y, H:i', strtotime($order['created_at'] ?? 'now')),
            'items_html' => $itemsHtml,
            'subtotal' => 'Rp ' . number_format($order['subtotal'] ?? $order['total'], 0, ',', '.'),
            'discount' => 'Rp ' . number_format($order['discount'] ?? 0, 0, ',', '.'),
            'total' => 'Rp ' . number_format($order['total'], 0, ',', '.'),
            'delivery_method' => ucfirst($order['delivery_method'] ?? 'takeaway'),
            'delivery_address' => $customer['address'] ?? '-',
            'payment_method' => ucfirst($order['payment_method'] ?? 'cash'),
            'order_url' => self::$appUrl . '/orders/' . $order['order_number']
        ];
        
        $html = self::loadTemplate('order_confirmation', $data);
        
        return self::send($customer['email'], $subject, $html, $data);
    }
    
    /**
     * Send payment confirmation email
     */
    public static function sendPaymentConfirmation($order, $customer) {
        $subject = "Payment Received - " . $order['order_number'];
        
        // Format items
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>Rp " . number_format($itemTotal, 0, ',', '.') . "</td>
                </tr>
            ";
        }
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'payment_date' => date('d F Y, H:i'),
            'items_html' => $itemsHtml,
            'total_paid' => 'Rp ' . number_format($order['total'], 0, ',', '.'),
            'payment_method' => ucfirst($order['payment_method'] ?? 'online'),
            'order_url' => self::$appUrl . '/orders/' . $order['order_number']
        ];
        
        $html = self::loadTemplate('payment_confirmation', $data);
        
        return self::send($customer['email'], $subject, $html, $data);
    }
    
    /**
     * Send order status update email
     */
    public static function sendStatusUpdate($order, $customer, $newStatus) {
        $statusMessages = [
            'processing' => 'Your order is being prepared',
            'ready' => 'Your order is ready for pickup/delivery',
            'delivering' => 'Your order is on the way',
            'completed' => 'Your order has been delivered',
            'cancelled' => 'Your order has been cancelled'
        ];
        
        $statusMessage = $statusMessages[$newStatus] ?? 'Your order status has been updated';
        $subject = "Order Update - " . $order['order_number'];
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'status' => ucfirst($newStatus),
            'status_message' => $statusMessage,
            'order_url' => self::$appUrl . '/orders/' . $order['order_number']
        ];
        
        $html = self::loadTemplate('status_update', $data);
        
        return self::send($customer['email'], $subject, $html, $data);
    }
    
    /**
     * Send welcome email to new user
     */
    public static function sendWelcomeEmail($user) {
        $subject = "Welcome to DailyCup! ☕";
        
        $data = [
            'user_name' => $user['name'] ?? 'Coffee Lover',
            'user_email' => $user['email'],
            'menu_url' => self::$appUrl . '/menu',
            'profile_url' => self::$appUrl . '/profile'
        ];
        
        $html = self::loadTemplate('welcome', $data);
        
        return self::send($user['email'], $subject, $html, $data);
    }
}
?>
