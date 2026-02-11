<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #6F4E37;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #6F4E37;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .order-details {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>☕ DailyCup Coffee Shop</h1>
            <p>Thank You for Your Order!</p>
        </div>
        
        <div class="content">
            <h2>Hi <?php echo htmlspecialchars($customerName ?? 'Customer'); ?>,</h2>
            
            <p>Your order has been confirmed! We're preparing your delicious coffee right now.</p>
            
            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($orderNumber ?? 'N/A'); ?></p>
                <p><strong>Order Date:</strong> <?php echo date('d M Y H:i'); ?></p>
                <p><strong>Total Amount:</strong> <?php echo formatCurrency($totalAmount ?? 0); ?></p>
                <p><strong>Delivery Method:</strong> <?php echo ucfirst($deliveryMethod ?? 'N/A'); ?></p>
            </div>
            
            <p>You can track your order status anytime by logging into your account.</p>
            
            <div style="text-align: center;">
                <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="button">Track My Order</a>
            </div>
            
            <p><strong>Need Help?</strong><br>
            Contact us at <a href="mailto:info@dailycup.com">info@dailycup.com</a> or call us at +62 812-3456-7890</p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> DailyCup Coffee Shop. All rights reserved.</p>
            <p>Jakarta, Indonesia</p>
        </div>
    </div>
</body>
</html>
