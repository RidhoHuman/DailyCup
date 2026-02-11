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
            background-color: #28a745;
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
        .success-icon {
            font-size: 60px;
            color: #28a745;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>☕ DailyCup Coffee Shop</h1>
            <p>Your Order is Complete!</p>
        </div>
        
        <div class="content">
            <div class="success-icon">✓</div>
            
            <h2 style="text-align: center;">Order Completed!</h2>
            
            <p>Hi <?php echo htmlspecialchars($customerName ?? 'Customer'); ?>,</p>
            
            <p>Great news! Your order <strong><?php echo htmlspecialchars($orderNumber ?? 'N/A'); ?></strong> has been completed successfully.</p>
            
            <p>We hope you enjoyed your DailyCup experience! 🎉</p>
            
            <div style="text-align: center;">
                <p><strong>Earned Loyalty Points: <?php echo $earnedPoints ?? 0; ?> points</strong></p>
            </div>
            
            <p>We'd love to hear your feedback! Please take a moment to rate and review your order.</p>
            
            <div style="text-align: center;">
                <a href="<?php echo SITE_URL; ?>/customer/orders.php" class="button">Rate This Order</a>
            </div>
            
            <p>Thank you for choosing DailyCup. We look forward to serving you again!</p>
        </div>
        
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> DailyCup Coffee Shop. All rights reserved.</p>
            <p>Jakarta, Indonesia</p>
        </div>
    </div>
</body>
</html>
