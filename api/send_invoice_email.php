<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/generate_invoice.php';

function sendInvoiceEmail($orderId) {
    $db = getDB();
    
    // Get order details
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email 
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order || !$order['customer_email']) {
        return false;
    }
    
    // Generate invoice PDF
    $invoiceFilename = generateInvoicePDF($orderId);
    if (!$invoiceFilename) {
        return false;
    }
    
    $invoiceFilepath = __DIR__ . '/../assets/invoices/' . $invoiceFilename;
    
    // Email content
    $to = $order['customer_email'];
    $subject = 'Invoice Order #' . $order['order_number'] . ' - DailyCup Coffee';
    
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #6F4E37 0%, #8B4513 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .invoice-info { background: white; padding: 20px; border-left: 4px solid #6F4E37; margin: 20px 0; }
            .invoice-info h3 { color: #6F4E37; margin-top: 0; }
            .btn { display: inline-block; padding: 12px 30px; background: #6F4E37; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; border-top: 1px solid #ddd; }
            .highlight { color: #6F4E37; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>☕ DailyCup Coffee</h1>
                <p>Thank You for Your Order!</p>
            </div>
            <div class="content">
                <p>Hi <strong>' . htmlspecialchars($order['customer_name']) . '</strong>,</p>
                
                <p>Terima kasih telah berbelanja di DailyCup Coffee! Pesanan Anda telah selesai dan kami harap Anda menikmati produk kami.</p>
                
                <div class="invoice-info">
                    <h3>📋 Detail Pesanan</h3>
                    <table style="width: 100%;">
                        <tr>
                            <td><strong>Order Number:</strong></td>
                            <td>' . htmlspecialchars($order['order_number']) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Order Date:</strong></td>
                            <td>' . date('d F Y', strtotime($order['created_at'])) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td class="highlight">' . formatCurrency($order['final_amount']) . '</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td><span style="background: #28a745; color: white; padding: 5px 10px; border-radius: 5px;">COMPLETED</span></td>
                        </tr>
                    </table>
                </div>
                
                <p><strong>Invoice terlampir</strong> pada email ini dalam format PDF. Anda juga dapat mengunduh invoice kapan saja dari halaman detail pesanan di website kami.</p>
                
                <p>Jangan lupa untuk memberikan <strong>review</strong> untuk produk yang Anda beli dan dapatkan <strong class="highlight">10 poin loyalty bonus</strong>! 🌟</p>
                
                <div style="text-align: center;">
                    <a href="' . SITE_URL . '/customer/order_detail.php?id=' . $order['id'] . '" class="btn">
                        Lihat Detail Pesanan
                    </a>
                </div>
                
                <p style="margin-top: 30px;">Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi kami:</p>
                <ul>
                    <li>📧 Email: info@dailycup.com</li>
                    <li>📱 Phone: (021) 1234-5678</li>
                    <li>⏰ Senin - Minggu: 08:00 - 22:00 WIB</li>
                </ul>
            </div>
            <div class="footer">
                <p><strong>DailyCup Coffee Shop</strong></p>
                <p>Brewing happiness since 2020 ☕</p>
                <p style="font-size: 10px; margin-top: 10px;">
                    Email ini dikirim secara otomatis. Mohon untuk tidak membalas email ini.
                </p>
            </div>
        </div>
    </body>
    </html>';
    
    // Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    
    // For attachment, we need to use boundary
    $boundary = md5(time());
    
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n";
    
    // Attach PDF
    if (file_exists($invoiceFilepath)) {
        $fileContent = chunk_split(base64_encode(file_get_contents($invoiceFilepath)));
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: application/pdf; name=\"{$invoiceFilename}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$invoiceFilename}\"\r\n\r\n";
        $body .= $fileContent . "\r\n";
    }
    
    $body .= "--{$boundary}--";
    
    // Send email
    return mail($to, $subject, $body, $headers);
}

// Test function
if (isset($_GET['test_order_id'])) {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        die('Access denied');
    }
    
    $orderId = intval($_GET['test_order_id']);
    $result = sendInvoiceEmail($orderId);
    
    if ($result) {
        echo "✅ Email sent successfully!";
    } else {
        echo "❌ Failed to send email. Check your PHP mail configuration.";
    }
}
