<?php
/**
 * Invoice Generator API
 * Generates HTML invoices for orders
 */

// CORS handled by .htaccess
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jwt.php';

// Get and validate order_id parameter
$orderId = $_GET['order_id'] ?? '';
$format = $_GET['format'] ?? 'html'; // html or json

if (empty($orderId)) {
    http_response_code(400);
    die('<h1>Error 400</h1><p>Order ID is required</p>');
}

try {
    // Optional: Check authentication (commented out for easier access)
    // $userData = JWT::getUser();
    // if (!$userData) {
    //     http_response_code(401);
    //     die('<h1>Error 401</h1><p>Authentication required</p>');
    // }
    
    global $pdo;
    
    // Get order details - use order_number column
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.order_number,
            o.user_id,
            o.total_amount,
            o.discount_amount,
            o.final_amount,
            o.delivery_method,
            o.delivery_address,
            o.payment_status,
            o.payment_method,
            o.status,
            o.customer_notes,
            o.created_at,
            u.name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        die('<h1>Error 404</h1><p>Order not found: ' . htmlspecialchars($orderId) . '</p>');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT 
            oi.id,
            oi.product_id,
            oi.product_name,
            oi.quantity,
            oi.unit_price as price,
            oi.subtotal,
            oi.size,
            oi.temperature,
            oi.addons,
            oi.notes as item_notes,
            oi.base_price,
            oi.size_price_modifier,
            oi.addons_total
        FROM order_items oi 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate invoice data
    $invoiceData = [
        'invoice_number' => 'INV-' . strtoupper(substr($orderId, 4, 12)),
        'invoice_date' => date('d/m/Y', strtotime($order['created_at'])),
        'due_date' => date('d/m/Y', strtotime($order['created_at'] . ' +7 days')),
        'order_id' => $orderId,
        'order_date' => date('d/m/Y H:i', strtotime($order['created_at'])),
        'status' => $order['status'],
        'payment_status' => $order['payment_status'],
        'payment_method' => $order['payment_method'],
        'customer' => [
            'name' => $order['customer_name'] ?? 'Guest Customer',
            'email' => $order['customer_email'] ?? '-',
            'phone' => $order['customer_phone'] ?? '-',
            'address' => $order['delivery_address'] ?? '-'
        ],
        'items' => array_map(function($item) {
            $itemData = [
                'name' => $item['product_name'] ?? 'Product',
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal']
            ];
            
            // Add customization details
            if (!empty($item['size'])) {
                $itemData['size'] = $item['size'];
            }
            if (!empty($item['temperature'])) {
                $itemData['temperature'] = $item['temperature'];
            }
            if (!empty($item['addons'])) {
                $itemData['addons'] = json_decode($item['addons'], true) ?? [];
            }
            if (!empty($item['item_notes'])) {
                $itemData['notes'] = $item['item_notes'];
            }
            if (isset($item['base_price'])) {
                $itemData['base_price'] = $item['base_price'];
                $itemData['size_modifier'] = $item['size_price_modifier'] ?? 0;
                $itemData['addons_total'] = $item['addons_total'] ?? 0;
            }
            
            return $itemData;
        }, $items),
        'subtotal' => $order['total_amount'] ?? $order['final_amount'],
        'discount' => $order['discount_amount'] ?? 0,
        'delivery_fee' => 0, // Not tracked separately in current schema
        'total' => $order['final_amount'],
        'notes' => $order['customer_notes'] ?? ''
    ];
    
    if ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'invoice' => $invoiceData
        ]);
        exit;
    }
    
    // Generate HTML invoice
    $html = generateInvoiceHTML($invoiceData);
    echo $html;
    
} catch (PDOException $e) {
    error_log("Invoice generation error: " . $e->getMessage());
    http_response_code(500);
    die('<h1>Error 500</h1><p>Database error occurred</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
} catch (Exception $e) {
    error_log("Invoice generation error: " . $e->getMessage());
    http_response_code(500);
    die('<h1>Error 500</h1><p>Failed to generate invoice</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

function generateInvoiceHTML($data) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . htmlspecialchars($data['invoice_number']) . '</title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 40px;
            border-bottom: 3px solid #a97456;
            padding-bottom: 20px;
        }
        .company-info {
            flex: 1;
        }
        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #a97456;
            margin-bottom: 10px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #a97456;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #a97456;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 30px;
            float: right;
            width: 300px;
        }
        .totals table {
            margin-top: 0;
        }
        .totals td {
            padding: 8px 12px;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 18px;
            background-color: #a97456;
            color: white;
        }
        .footer {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-paid { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-cancelled { background-color: #f8d7da; color: #721c24; }
        .print-button {
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #a97456;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-button:hover {
            background-color: #8f6249;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">🖨️ Print Invoice</button>
        <button class="print-button" onclick="window.close()">← Back</button>
    </div>
    
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <div class="company-name">DailyCup</div>
                <div>Premium Coffee Delivery</div>
                <div>Jakarta, Indonesia</div>
                <div>Email: hello@dailycup.id</div>
                <div>Phone: +62 812-3456-7890</div>
            </div>
            <div class="invoice-info">
                <div class="invoice-title">INVOICE</div>
                <div><strong>' . htmlspecialchars($data['invoice_number']) . '</strong></div>
                <div>Date: ' . htmlspecialchars($data['invoice_date']) . '</div>
                <div>Due: ' . htmlspecialchars($data['due_date']) . '</div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Bill To</div>
            <div><strong>' . htmlspecialchars($data['customer']['name']) . '</strong></div>
            <div>' . htmlspecialchars($data['customer']['email']) . '</div>
            <div>' . htmlspecialchars($data['customer']['phone']) . '</div>
            <div>' . nl2br(htmlspecialchars($data['customer']['address'])) . '</div>
        </div>
        
        <div class="section">
            <div class="section-title">Order Details</div>
            <div>Order ID: <strong>' . htmlspecialchars($data['order_id']) . '</strong></div>
            <div>Order Date: ' . htmlspecialchars($data['order_date']) . '</div>
            <div>Payment: ' . strtoupper(htmlspecialchars($data['payment_method'])) . '</div>
            <div>Order Status: <span class="badge badge-' . getStatusBadgeClass($data['status']) . '">' 
                . strtoupper(str_replace('_', ' ', $data['status'])) . '</span></div>
            <div>Payment Status: <span class="badge badge-' . getStatusBadgeClass($data['payment_status']) . '">' 
                . strtoupper(str_replace('_', ' ', $data['payment_status'])) . '</span></div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($data['items'] as $item) {
        $html .= '<tr>
                    <td>
                        <strong>' . htmlspecialchars($item['name']) . '</strong>';
        
        // Display customization details
        $customizations = [];
        if (!empty($item['size'])) {
            $sizeNames = ['S' => 'Small', 'M' => 'Medium', 'L' => 'Large'];
            $customizations[] = '📏 ' . ($sizeNames[$item['size']] ?? $item['size']);
        }
        if (!empty($item['temperature'])) {
            $tempIcon = $item['temperature'] === 'hot' ? '🔥' : '❄️';
            $customizations[] = $tempIcon . ' ' . ucfirst($item['temperature']);
        }
        
        if (!empty($customizations)) {
            $html .= '<br><small style="color: #666; font-weight: 500;">' 
                . implode(' | ', $customizations) . '</small>';
        }
        
        // Display add-ons
        if (!empty($item['addons']) && is_array($item['addons'])) {
            $addonNames = array_map(function($addon) {
                return '+ ' . $addon['name'];
            }, $item['addons']);
            $html .= '<br><small style="color: #28a745; font-weight: 500;">✨ ' 
                . implode(', ', $addonNames) . '</small>';
        }
        
        // Display custom notes
        if (!empty($item['notes'])) {
            $html .= '<br><small style="color: #856404; background: #fff3cd; padding: 2px 6px; border-radius: 3px; display: inline-block; margin-top: 4px;">📝 ' 
                . htmlspecialchars($item['notes']) . '</small>';
        }
        
        // Display price breakdown if available
        if (!empty($item['base_price'])) {
            $breakdown = ['Base: Rp ' . number_format($item['base_price'], 0, ',', '.')];
            if (!empty($item['size_modifier']) && $item['size_modifier'] > 0) {
                $breakdown[] = 'Size: +Rp ' . number_format($item['size_modifier'], 0, ',', '.');
            }
            if (!empty($item['addons_total']) && $item['addons_total'] > 0) {
                $breakdown[] = 'Add-ons: +Rp ' . number_format($item['addons_total'], 0, ',', '.');
            }
            if (count($breakdown) > 1) {
                $html .= '<br><small style="color: #999; font-size: 10px;">' 
                    . implode(' • ', $breakdown) . '</small>';
            }
        }
        
        $html .= '</td>
                    <td class="text-right">' . $item['quantity'] . '</td>
                    <td class="text-right">Rp ' . number_format($item['price'], 0, ',', '.') . '</td>
                    <td class="text-right">Rp ' . number_format($item['subtotal'], 0, ',', '.') . '</td>
                </tr>';
    }
    
    $html .= '</tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">Rp ' . number_format($data['subtotal'], 0, ',', '.') . '</td>
                </tr>';
    
    if ($data['discount'] > 0) {
        $html .= '<tr>
                    <td>Discount</td>
                    <td class="text-right" style="color: #28a745;">- Rp ' . number_format($data['discount'], 0, ',', '.') . '</td>
                </tr>';
    }
    
    $html .= '<tr>
                    <td>Delivery Fee</td>
                    <td class="text-right">Rp ' . number_format($data['delivery_fee'], 0, ',', '.') . '</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="text-right">Rp ' . number_format($data['total'], 0, ',', '.') . '</td>
                </tr>
            </table>
        </div>
        
        <div class="footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>This is a computer-generated invoice and does not require a signature.</p>
            <p>For questions, contact us at hello@dailycup.id</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

function getStatusBadgeClass($status) {
    $classes = [
        'paid' => 'paid',
        'completed' => 'paid',
        'pending' => 'pending',
        'processing' => 'pending',
        'confirmed' => 'pending',
        'cancelled' => 'cancelled',
        'failed' => 'cancelled',
        'refunded' => 'cancelled'
    ];
    return $classes[$status] ?? 'pending';
}
