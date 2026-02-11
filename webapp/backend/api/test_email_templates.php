<?php
/**
 * Test Email Templates
 * 
 * Preview email templates without actually sending
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';

$template = $_GET['template'] ?? 'order_confirmation';
$send = isset($_GET['send']);
$email = $_GET['email'] ?? null;

// Sample data for templates
$sampleOrder = [
    'order_number' => 'ORD-' . time() . '-1234',
    'total' => 85000,
    'subtotal' => 75000,
    'discount' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'delivery_method' => 'delivery',
    'payment_method' => 'Xendit (Online Payment)',
    'estimated_time' => '20-30 minutes',
    'items' => [
        ['name' => 'Cappuccino (Large, Hot)', 'price' => 35000, 'quantity' => 1],
        ['name' => 'Latte (Regular, Ice)', 'price' => 28000, 'quantity' => 1],
        ['name' => 'Croissant', 'price' => 22000, 'quantity' => 1]
    ]
];

$sampleCustomer = [
    'name' => 'John Doe',
    'email' => $email ?? 'john@example.com',
    'phone' => '+62 812-3456-7890',
    'address' => 'Jl. Bunga Mawar No. 56, Kebayoran Lama, Jakarta Selatan, DKI Jakarta'
];

$sampleUser = [
    'name' => 'John Doe',
    'email' => $email ?? 'john@example.com'
];

// Generate HTML based on template
$html = '';
$subject = '';

switch ($template) {
    case 'order_confirmation':
        $itemsHtml = '';
        foreach ($sampleOrder['items'] as $item) {
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
            'customer_name' => $sampleCustomer['name'],
            'order_number' => $sampleOrder['order_number'],
            'order_date' => date('d F Y, H:i'),
            'items_html' => $itemsHtml,
            'subtotal' => 'Rp ' . number_format($sampleOrder['subtotal'], 0, ',', '.'),
            'discount' => 'Rp 0',
            'total' => 'Rp ' . number_format($sampleOrder['total'], 0, ',', '.'),
            'delivery_method' => 'Delivery',
            'delivery_address' => $sampleCustomer['address'],
            'payment_method' => $sampleOrder['payment_method'],
            'order_url' => 'http://localhost:3000/orders/' . $sampleOrder['order_number']
        ];
        $subject = "Order Confirmation - " . $sampleOrder['order_number'];
        break;
        
    case 'payment_confirmation':
        $itemsHtml = '';
        foreach ($sampleOrder['items'] as $item) {
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
            'customer_name' => $sampleCustomer['name'],
            'order_number' => $sampleOrder['order_number'],
            'payment_date' => date('d F Y, H:i'),
            'items_html' => $itemsHtml,
            'total_paid' => 'Rp ' . number_format($sampleOrder['total'], 0, ',', '.'),
            'payment_method' => 'Xendit (Online Payment)',
            'order_url' => 'http://localhost:3000/orders/' . $sampleOrder['order_number']
        ];
        $subject = "Payment Received - " . $sampleOrder['order_number'];
        break;
        
    case 'status_update':
        $data = [
            'customer_name' => $sampleCustomer['name'],
            'order_number' => $sampleOrder['order_number'],
            'status' => 'Processing',
            'status_message' => 'Your order is being prepared by our barista!',
            'order_url' => 'http://localhost:3000/orders/' . $sampleOrder['order_number']
        ];
        $subject = "Order Update - " . $sampleOrder['order_number'];
        break;
        
    case 'order_shipped':
        $data = [
            'customer_name' => $sampleCustomer['name'],
            'order_number' => $sampleOrder['order_number'],
            'delivery_address' => $sampleCustomer['address'],
            'estimated_time' => '20-30 minutes',
            'tracking_url' => 'http://localhost:3000/orders/' . $sampleOrder['order_number']
        ];
        $subject = "Your Order is On The Way! 🛵 - " . $sampleOrder['order_number'];
        break;
        
    case 'order_delivered':
        $data = [
            'customer_name' => $sampleCustomer['name'],
            'order_number' => $sampleOrder['order_number'],
            'total' => 'Rp ' . number_format($sampleOrder['total'], 0, ',', '.'),
            'delivery_date' => date('d F Y, H:i'),
            'points_earned' => floor($sampleOrder['total'] / 1000),
            'review_url' => 'http://localhost:3000/orders/' . $sampleOrder['order_number'] . '?review=true',
            'reorder_url' => 'http://localhost:3000/menu'
        ];
        $subject = "Order Delivered! 🎉 - " . $sampleOrder['order_number'];
        break;
        
    case 'welcome':
        $data = [
            'user_name' => $sampleUser['name'],
            'user_email' => $sampleUser['email'],
            'menu_url' => 'http://localhost:3000/menu',
            'profile_url' => 'http://localhost:3000/profile'
        ];
        $subject = "Welcome to DailyCup! ☕";
        break;
        
    default:
        die("Unknown template: $template");
}

// Load template
$html = EmailService::loadTemplate($template, $data);

// Send if requested
if ($send && $email) {
    EmailService::setUseQueue(false); // Send immediately for testing
    $result = EmailService::send($email, $subject, $html);
    echo "<div style='background: " . ($result ? '#e8f5e9' : '#ffebee') . "; padding: 15px; margin: 10px; border-radius: 8px;'>";
    echo $result ? "✅ Email sent to $email!" : "❌ Failed to send email";
    echo "</div>";
}

// Show template selector
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Template Preview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .toolbar { background: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .toolbar select, .toolbar input, .toolbar button { padding: 8px 15px; border-radius: 6px; border: 1px solid #ddd; font-size: 14px; }
        .toolbar button { background: #a97456; color: white; border: none; cursor: pointer; }
        .toolbar button:hover { background: #8a5a3a; }
        .toolbar .send-btn { background: #4caf50; }
        .toolbar .send-btn:hover { background: #388e3c; }
        .preview { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .preview-header { background: #333; color: #fff; padding: 10px 20px; font-size: 12px; }
        .preview-body { padding: 0; }
        iframe { width: 100%; height: 800px; border: none; }
    </style>
</head>
<body>
    <div class="toolbar">
        <form method="GET" style="display: contents;">
            <label>Template:</label>
            <select name="template" onchange="this.form.submit()">
                <option value="order_confirmation" <?= $template === 'order_confirmation' ? 'selected' : '' ?>>Order Confirmation</option>
                <option value="payment_confirmation" <?= $template === 'payment_confirmation' ? 'selected' : '' ?>>Payment Confirmation</option>
                <option value="status_update" <?= $template === 'status_update' ? 'selected' : '' ?>>Status Update</option>
                <option value="order_shipped" <?= $template === 'order_shipped' ? 'selected' : '' ?>>Order Shipped</option>
                <option value="order_delivered" <?= $template === 'order_delivered' ? 'selected' : '' ?>>Order Delivered</option>
                <option value="welcome" <?= $template === 'welcome' ? 'selected' : '' ?>>Welcome Email</option>
            </select>
            
            <label>Send to:</label>
            <input type="email" name="email" placeholder="test@example.com" value="<?= htmlspecialchars($email ?? '') ?>">
            <button type="submit" name="send" value="1" class="send-btn">📧 Send Test Email</button>
        </form>
    </div>
    
    <div class="preview">
        <div class="preview-header">
            <strong>Subject:</strong> <?= htmlspecialchars($subject) ?>
        </div>
        <div class="preview-body">
            <iframe srcdoc="<?= htmlspecialchars($html) ?>"></iframe>
        </div>
    </div>
</body>
</html>
