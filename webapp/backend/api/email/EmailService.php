<?php
require_once __DIR__ . '/../cors.php';
/**
 * Email Service for DailyCup
 * 
 * Handles all email sending functionality
 * Supports both native mail() and SMTP (PHPMailer ready)
 */

class EmailService {
    private static $fromEmail;
    private static $fromName;
    private static $appUrl;
    private static $enabled;
    private static $useQueue = null; // null = not set yet
    private static $manuallySet = false; // Track if setUseQueue was called
    
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
        
        // Only set from env if not manually set via setUseQueue()
        if (!self::$manuallySet) {
            self::$useQueue = getenv('EMAIL_USE_QUEUE') !== 'false';
        }
    }
    
    /**
     * Set whether to use async queue
     */
    public static function setUseQueue($useQueue) {
        self::$useQueue = $useQueue;
        self::$manuallySet = true; // Mark as manually set, don't override from env
    }
    
    /**
     * Send email using native mail() function or queue
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param array $data Additional data for template
     * @return bool Success status
     */
    public static function send($to, $subject, $htmlBody, $data = []) {
        self::init();
        
        // Skip if email disabled
        if (!self::$enabled) {
            error_log("Email sending disabled. Would send to: $to - Subject: $subject");
            return true; // Return true to not break flow
        }
        
        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: $to");
            return false;
        }
        
        // Use queue for async sending (fast response)
        if (self::$useQueue) {
            return self::queueEmail($to, $subject, $htmlBody);
        }
        
        // Send immediately (blocking)
        return self::sendDirect($to, $subject, $htmlBody);
    }
    
    /**
     * Queue email for async sending
     * @return bool Always returns true (queued successfully)
     */
    private static function queueEmail($to, $subject, $htmlBody) {
        require_once __DIR__ . '/EmailQueue.php';
        
        $queued = EmailQueue::add($to, $subject, $htmlBody);
        
        if ($queued) {
            error_log("Email queued for async sending: $to");
        } else {
            error_log("Failed to queue email: $to");
        }
        
        return true; // Always return true to not break order flow
    }
    
    /**
     * Send email directly using PHPMailer (not native mail())
     * @return bool Success status
     */
    private static function sendDirect($to, $subject, $htmlBody) {
        // Load PHPMailer - Adjust path to DailyCup/vendor/autoload.php
        // Path logic: /backend/api/email/ -> up 3 levels to /webapp/ -> up 1 to /DailyCup/ -> /vendor/
        $vendorPath = __DIR__ . '/../../../../vendor/autoload.php';
        
        // Fallback for different structures (e.g. if webapp is root)
        if (!file_exists($vendorPath)) {
            $vendorPath = __DIR__ . '/../../../vendor/autoload.php';
        }
        
        if (!file_exists($vendorPath)) {
            // Try one more common location - laragon root
            $vendorPath = 'C:/laragon/www/DailyCup/vendor/autoload.php';
        }
        
        if (!file_exists($vendorPath)) {
            error_log("PHPMailer not found. Checked: " . __DIR__ . '/../../../../vendor/autoload.php');
            return false;
        }
        require_once $vendorPath;
        
        // Amazon SES or Gmail SMTP

        $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $smtpPort = getenv('SMTP_PORT') ?: 587;
        $smtpUser = getenv('SMTP_USERNAME');
        $smtpPass = getenv('SMTP_PASSWORD');
        
        if (empty($smtpUser) || empty($smtpPass)) {
            error_log("SMTP credentials not configured");
            return false;
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            
            // Use correct encryption based on port
            if ($smtpPort == 465) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $mail->Port = $smtpPort;

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
            
        } catch (\Exception $e) {
            error_log("Failed to send email to $to: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load email template
     * 
     * @param string $templateName Template filename (without .html)
     * @param array $data Data to replace in template
     * @return string Processed HTML
     */
    public static function loadTemplate($templateName, $data = []) {
        // Path from /api/email/ to /api/templates/email/
        $templatePath = __DIR__ . '/../templates/email/' . $templateName . '.html';
        
        if (!file_exists($templatePath)) {
            error_log("Email template not found: $templatePath");
            return '';
        }
        
        $html = file_get_contents($templatePath);
        
        // Replace placeholders
        foreach ($data as $key => $value) {
            if (is_null($value) || $value === '') {
                $replacement = '';
            } else {
                $replacement = $value;
            }
            $html = str_replace('{{' . $key . '}}', (string)$replacement, $html);
        }
        
        // Replace app_url
        $html = str_replace('{{app_url}}', (string)(self::$appUrl ?? ''), $html);
        
        return $html;
    }
    
    /**
     * Send order confirmation email
     * 
     * @param array $order Order data
     * @param array $customer Customer data
     * @return bool
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
        
        $total = $order['total'] ?? $order['total_amount'] ?? 0;
        $subtotal = $order['subtotal'] ?? $order['subtotal_amount'] ?? $total;
        $discount = $order['discount'] ?? $order['discount_amount'] ?? 0;
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'order_date' => date('d F Y, H:i', strtotime($order['created_at'] ?? 'now')),
            'items_html' => $itemsHtml,
            'subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
            'discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
            'total' => 'Rp ' . number_format($total, 0, ',', '.'),
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
     * 
     * @param array $order Order data
     * @param array $customer Customer data
     * @return bool
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
        
        $total = $order['total'] ?? $order['total_amount'] ?? 0;
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'payment_date' => date('d F Y, H:i'),
            'items_html' => $itemsHtml,
            'total_paid' => 'Rp ' . number_format($total, 0, ',', '.'),
            'payment_method' => ucfirst($order['payment_method'] ?? 'online'),
            'order_url' => self::$appUrl . '/orders/' . $order['order_number']
        ];
        
        $html = self::loadTemplate('payment_confirmation', $data);
        
        return self::send($customer['email'], $subject, $html, $data);
    }
    
    /**
     * Send order status update email
     * 
     * @param array $order Order data
     * @param array $customer Customer data
     * @param string $newStatus New status
     * @return bool
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
     * 
     * @param array $user User data
     * @return bool
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
    
    /**
     * Send order shipped/delivering email
     * 
     * @param array $order Order data
     * @param array $customer Customer data
     * @return bool
     */
    public static function sendOrderShipped($order, $customer) {
        $subject = "Your Order is On The Way! 🛵 - " . $order['order_number'];
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'delivery_address' => $customer['address'] ?? '-',
            'estimated_time' => $order['estimated_time'] ?? '15-30 minutes',
            'tracking_url' => self::$appUrl . '/orders/' . $order['order_number']
        ];
        
        $html = self::loadTemplate('order_shipped', $data);
        
        return self::send($customer['email'], $subject, $html, $data);
    }
    
    /**
     * Send order delivered email
     * 
     * @param array $order Order data
     * @param array $customer Customer data
     * @return bool
     */
    public static function sendOrderDelivered($order, $customer) {
        $subject = "Order Delivered! 🎉 - " . $order['order_number'];
        
        // Calculate points earned (1 point per 1000 IDR)
        $pointsEarned = floor(($order['total'] ?? 0) / 1000);
        
        $data = [
            'customer_name' => $customer['name'] ?? 'Customer',
            'order_number' => $order['order_number'],
            'total' => 'Rp ' . number_format($order['total'] ?? 0, 0, ',', '.'),
            'delivery_date' => date('d F Y, H:i'),
            'points_earned' => $pointsEarned,
            'review_url' => self::$appUrl . '/orders/' . $order['order_number'] . '?review=true',
            'reorder_url' => self::$appUrl . '/menu'
        ];
        
        $html = self::loadTemplate('order_delivered', $data);
        
        return self::send($customer['email'], $subject, $html, $data);
    }
}
?>
