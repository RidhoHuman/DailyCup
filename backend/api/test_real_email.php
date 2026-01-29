<?php
/**
 * Test Real Payment Confirmation Email
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email/EmailService.php';

// Force direct sending
EmailService::setUseQueue(false);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Real Email</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 8px; color: #2e7d32; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 8px; color: #c62828; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; color: #1565c0; margin: 10px 0; }
        input { padding: 10px; width: 100%; font-size: 16px; border: 2px solid #ddd; border-radius: 5px; margin: 10px 0; }
        button { background: #1976d2; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
        button:hover { background: #1565c0; }
        .preview { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Test Payment Confirmation Email</h1>
        <p>Kirim email konfirmasi pembayaran yang sesungguhnya (bukan test sederhana)</p>
";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $recipientEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $recipientName = htmlspecialchars($_POST['name'] ?? 'Customer');
    
    if (!$recipientEmail) {
        echo "<div class='error'>❌ Email tidak valid!</div>";
    } else {
        // Create realistic order data
        $orderNumber = 'ORD-' . time() . '-' . rand(1000, 9999);
        $testOrder = [
            'order_number' => $orderNumber,
            'total_amount' => 85000,
            'total' => 85000,
            'payment_method' => 'Xendit QRIS',
            'created_at' => date('Y-m-d H:i:s'),
            'items' => [
                [
                    'name' => 'Cappuccino',
                    'quantity' => 2,
                    'price' => 25000
                ],
                [
                    'name' => 'Espresso',
                    'quantity' => 1,
                    'price' => 20000
                ],
                [
                    'name' => 'Croissant',
                    'quantity' => 1,
                    'price' => 15000
                ]
            ]
        ];
        
        $testCustomer = [
            'name' => $recipientName,
            'email' => $recipientEmail
        ];
        
        echo "<div class='info'>";
        echo "<strong>Mengirim ke:</strong> $recipientEmail<br>";
        echo "<strong>Nama:</strong> $recipientName<br>";
        echo "<strong>Order:</strong> $orderNumber<br>";
        echo "<strong>Total:</strong> Rp " . number_format($testOrder['total'], 0, ',', '.') . "<br>";
        echo "</div>";
        
        echo "<p>⏳ Mengirim email...</p>";
        
        try {
            $sent = EmailService::sendPaymentConfirmation($testOrder, $testCustomer);
            
            if ($sent) {
                echo "<div class='success'>";
                echo "<h3>✅ Email Payment Confirmation Berhasil Dikirim!</h3>";
                echo "<p><strong>Dikirim ke:</strong> $recipientEmail</p>";
                echo "<p><strong>Subject:</strong> Payment Received - $orderNumber</p>";
                echo "<p><strong>Isi email:</strong> Konfirmasi pembayaran dengan detail order lengkap</p>";
                echo "<ul>";
                echo "<li>Cappuccino x2 = Rp 50.000</li>";
                echo "<li>Espresso x1 = Rp 20.000</li>";
                echo "<li>Croissant x1 = Rp 15.000</li>";
                echo "<li><strong>Total: Rp 85.000</strong></li>";
                echo "</ul>";
                echo "<p style='margin-top:20px;'>Cek email sekarang! Jika dari email berbeda, pasti masuk inbox.</p>";
                echo "</div>";
            } else {
                echo "<div class='error'>❌ Email gagal dikirim. Cek error log.</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
    }
    
    echo "<hr style='margin: 30px 0;'>";
}

// Form
echo "<h2>Masukkan Email Tujuan</h2>";
echo "<form method='post'>";
echo "<label><strong>Email Penerima:</strong></label>";
echo "<input type='email' name='email' placeholder='contoh@gmail.com' required value='" . ($_POST['email'] ?? '') . "'>";
echo "<label><strong>Nama Penerima:</strong></label>";
echo "<input type='text' name='name' placeholder='Nama Customer' value='" . ($_POST['name'] ?? 'Test Customer') . "'>";
echo "<button type='submit'>📤 Kirim Payment Confirmation</button>";
echo "</form>";

echo "<div class='preview'>";
echo "<h3>Preview: Isi Email Yang Akan Dikirim</h3>";
echo "<ul>";
echo "<li><strong>Template:</strong> payment_confirmation.html</li>";
echo "<li><strong>Subject:</strong> Payment Received - [ORDER_NUMBER]</li>";
echo "<li><strong>Berisi:</strong> Ucapan terima kasih, detail order, items, total pembayaran, metode payment</li>";
echo "<li><strong>Design:</strong> HTML profesional dengan logo DailyCup</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h4>💡 Tips:</h4>";
echo "<ul>";
echo "<li><strong>Test ke email BERBEDA</strong> (bukan ridhohuman11@gmail.com) untuk hasil akurat</li>";
echo "<li>Gmail ke Gmail sendiri tidak muncul di inbox, cuma di Sent</li>";
echo "<li>Coba kirim ke: Yahoo Mail, Outlook, Gmail berbeda</li>";
echo "<li>Cek SPAM jika tidak masuk inbox dalam 1 menit</li>";
echo "</ul>";
echo "</div>";

echo "</div>
</body>
</html>";
?>
