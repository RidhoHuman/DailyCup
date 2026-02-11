<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateInvoicePDF($orderId) {
    $db = getDB();
    
    // Get order details with items
    $stmt = $db->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, u.address
                         FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        return false;
    }
    
    // Get order items
    $stmt = $db->prepare("SELECT oi.*, p.name as product_name 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    // Get loyalty points used
    $loyaltyDiscount = 0;
    if ($order['loyalty_points_used'] > 0) {
        $stmt = $db->prepare("SELECT rupiah_per_point FROM loyalty_settings WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $setting = $stmt->fetch();
        $rupiahPerPoint = $setting ? $setting['rupiah_per_point'] : 100;
        $loyaltyDiscount = $order['loyalty_points_used'] * $rupiahPerPoint;
    }
    
    // Generate HTML
    $html = getInvoiceHTML($order, $items, $loyaltyDiscount);
    
    // Configure Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    // Save to file
    $filename = 'invoice_' . $order['order_number'] . '.pdf';
    $filepath = __DIR__ . '/../assets/invoices/' . $filename;
    
    // Create directory if not exists
    if (!file_exists(__DIR__ . '/../assets/invoices')) {
        mkdir(__DIR__ . '/../assets/invoices', 0777, true);
    }
    
    file_put_contents($filepath, $dompdf->output());
    
    return $filename;
}

function getInvoiceHTML($order, $items, $loyaltyDiscount) {
    $orderDate = date('d F Y', strtotime($order['created_at']));
    $orderTime = date('H:i', strtotime($order['created_at']));
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
            .container { width: 100%; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #8B4513; padding-bottom: 15px; }
            .header h1 { color: #8B4513; font-size: 28px; margin-bottom: 5px; }
            .header .tagline { color: #666; font-size: 11px; }
            .invoice-info { margin-bottom: 20px; }
            .invoice-info table { width: 100%; }
            .invoice-info td { padding: 5px; }
            .invoice-info .label { font-weight: bold; width: 120px; }
            .section { margin-bottom: 20px; }
            .section-title { background: #8B4513; color: white; padding: 8px; font-weight: bold; margin-bottom: 10px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .items-table th { background: #f5f5f5; padding: 8px; text-align: left; border-bottom: 2px solid #8B4513; }
            .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .items-table .right { text-align: right; }
            .items-table .center { text-align: center; }
            .totals { float: right; width: 300px; margin-top: 20px; }
            .totals table { width: 100%; }
            .totals td { padding: 8px; border-bottom: 1px solid #ddd; }
            .totals .label { text-align: right; }
            .totals .amount { text-align: right; font-weight: bold; }
            .totals .grand-total { background: #8B4513; color: white; font-size: 14px; }
            .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
            .notes { background: #fffbf0; border-left: 4px solid #ffc107; padding: 15px; margin-top: 20px; }
            .notes strong { color: #8B4513; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <h1>☕ DAILY CUP COFFEE</h1>
                <div class="tagline">Your Daily Coffee Companion</div>
                <div style="margin-top: 10px; font-size: 10px;">
                    Jl. Coffee Street No. 123, Jakarta | Phone: (021) 1234-5678 | Email: info@dailycup.com
                </div>
            </div>
            
            <!-- Invoice Info -->
            <div class="invoice-info">
                <table>
                    <tr>
                        <td class="label">Invoice Number:</td>
                        <td><strong>' . htmlspecialchars($order['order_number']) . '</strong></td>
                        <td class="label" style="text-align: right;">Date:</td>
                        <td style="text-align: right;">' . $orderDate . ' ' . $orderTime . '</td>
                    </tr>
                    <tr>
                        <td class="label">Customer:</td>
                        <td><strong>' . htmlspecialchars($order['customer_name']) . '</strong></td>
                        <td class="label" style="text-align: right;">Payment Status:</td>
                        <td style="text-align: right;">
                            <span style="background: ' . ($order['payment_status'] == 'paid' ? '#28a745' : '#ffc107') . '; color: white; padding: 3px 8px; border-radius: 3px;">
                                ' . strtoupper($order['payment_status']) . '
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Email:</td>
                        <td>' . htmlspecialchars($order['customer_email']) . '</td>
                        <td class="label" style="text-align: right;">Order Status:</td>
                        <td style="text-align: right;">
                            <span style="background: ' . ($order['status'] == 'completed' ? '#28a745' : '#17a2b8') . '; color: white; padding: 3px 8px; border-radius: 3px;">
                                ' . strtoupper($order['status']) . '
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Phone:</td>
                        <td>' . htmlspecialchars($order['customer_phone'] ?: '-') . '</td>
                        <td class="label" style="text-align: right;">Delivery Type:</td>
                        <td style="text-align: right;">' . ($order['delivery_type'] == 'delivery' ? 'Delivery' : 'Dine In / Pickup') . '</td>
                    </tr>';
    
    if ($order['delivery_type'] == 'delivery') {
        $html .= '
                    <tr>
                        <td class="label">Delivery Address:</td>
                        <td colspan="3">' . nl2br(htmlspecialchars($order['delivery_address'])) . '</td>
                    </tr>';
    }
    
    $html .= '
                </table>
            </div>
            
            <!-- Order Items -->
            <div class="section">
                <div class="section-title">ORDER ITEMS</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 10%;">No</th>
                            <th>Product Name</th>
                            <th class="center" style="width: 15%;">Qty</th>
                            <th class="right" style="width: 20%;">Price</th>
                            <th class="right" style="width: 20%;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    $no = 1;
    $subtotal = 0;
    foreach ($items as $item) {
        $itemSubtotal = $item['price'] * $item['quantity'];
        $subtotal += $itemSubtotal;
        
        $html .= '
                        <tr>
                            <td class="center">' . $no++ . '</td>
                            <td>
                                <strong>' . htmlspecialchars($item['product_name']) . '</strong>';
        
        if ($item['notes']) {
            $html .= '<br><small style="color: #666;">Notes: ' . htmlspecialchars($item['notes']) . '</small>';
        }
        
        $html .= '
                            </td>
                            <td class="center">' . $item['quantity'] . '</td>
                            <td class="right">' . formatCurrency($item['price']) . '</td>
                            <td class="right">' . formatCurrency($itemSubtotal) . '</td>
                        </tr>';
    }
    
    $html .= '
                    </tbody>
                </table>
            </div>
            
            <!-- Totals -->
            <div class="totals">
                <table>
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="amount">' . formatCurrency($subtotal) . '</td>
                    </tr>';
    
    if ($order['delivery_fee'] > 0) {
        $html .= '
                    <tr>
                        <td class="label">Delivery Fee:</td>
                        <td class="amount">' . formatCurrency($order['delivery_fee']) . '</td>
                    </tr>';
    }
    
    if ($order['discount_amount'] > 0) {
        $html .= '
                    <tr style="color: #28a745;">
                        <td class="label">Discount:</td>
                        <td class="amount">- ' . formatCurrency($order['discount_amount']) . '</td>
                    </tr>';
    }
    
    if ($loyaltyDiscount > 0) {
        $html .= '
                    <tr style="color: #17a2b8;">
                        <td class="label">Loyalty Points Used (' . $order['loyalty_points_used'] . ' pts):</td>
                        <td class="amount">- ' . formatCurrency($loyaltyDiscount) . '</td>
                    </tr>';
    }
    
    $html .= '
                    <tr class="grand-total">
                        <td class="label">TOTAL:</td>
                        <td class="amount">' . formatCurrency($order['final_amount']) . '</td>
                    </tr>
                </table>
            </div>
            
            <div style="clear: both;"></div>
            
            <!-- Notes -->
            <div class="notes">
                <strong>Thank you for your order!</strong><br>
                Jika ada pertanyaan tentang invoice ini, silakan hubungi kami di (021) 1234-5678 atau email ke info@dailycup.com
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <div style="margin-bottom: 10px;">
                    <strong>Daily Cup Coffee Shop</strong> - Brewing happiness since 2020
                </div>
                <div>
                    This is a computer-generated invoice and does not require a signature.
                </div>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

// If called directly (for download)
if (isset($_GET['order_id'])) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
    
    $orderId = intval($_GET['order_id']);
    $db = getDB();
    
    // Verify order ownership or admin
    $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order || ($order['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin')) {
        die('Access denied');
    }
    
    $filename = generateInvoicePDF($orderId);
    
    if ($filename) {
        $filepath = __DIR__ . '/../assets/invoices/' . $filename;
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        die('Error generating invoice');
    }
}
