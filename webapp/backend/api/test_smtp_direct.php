<?php
require_once __DIR__ . '/cors.php';
/**
 * Direct SMTP Test with PHPMailer
 * Test SMTP connection and email sending with full error reporting
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>SMTP Direct Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
        .success { background: #e8f5e9; padding: 15px; border-radius: 8px; color: #2e7d32; margin: 10px 0; }
        .error { background: #ffebee; padding: 15px; border-radius: 8px; color: #c62828; margin: 10px 0; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; color: #1565c0; margin: 10px 0; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        h2 { color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📧 Direct SMTP Test with PHPMailer</h1>
";

// Step 1: Check PHPMailer
echo "<h2>Step 1: Check PHPMailer Installation</h2>";

$vendorPath = __DIR__ . '/../../../../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    $vendorPath = __DIR__ . '/../../../vendor/autoload.php';
}
if (!file_exists($vendorPath)) {
    $vendorPath = 'C:/laragon/www/DailyCup/vendor/autoload.php';
}

if (!file_exists($vendorPath)) {
    echo "<div class='error'>❌ PHPMailer not found at: $vendorPath</div>";
    echo "<p>Install with: <code>composer require phpmailer/phpmailer</code></p>";
    exit;
} else {
    echo "<div class='success'>✅ PHPMailer found at: <code>$vendorPath</code></div>";
    require_once $vendorPath;
}

// Step 2: Load environment
echo "<h2>Step 2: SMTP Configuration</h2>";

// Load .env manually
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$smtpHost = getenv('SMTP_HOST') ?: $_ENV['SMTP_HOST'] ?? '';
$smtpPort = getenv('SMTP_PORT') ?: $_ENV['SMTP_PORT'] ?? '';
$smtpUser = getenv('SMTP_USERNAME') ?: $_ENV['SMTP_USERNAME'] ?? '';
$smtpPass = getenv('SMTP_PASSWORD') ?: $_ENV['SMTP_PASSWORD'] ?? '';
$smtpEncryption = getenv('SMTP_ENCRYPTION') ?: $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
$fromEmail = getenv('SMTP_FROM_EMAIL') ?: $_ENV['SMTP_FROM_EMAIL'] ?? '';
$fromName = getenv('SMTP_FROM_NAME') ?: $_ENV['SMTP_FROM_NAME'] ?? '';

echo "<div class='info'>";
echo "<strong>SMTP Host:</strong> " . ($smtpHost ?: '<span style="color:red">NOT SET</span>') . "<br>";
echo "<strong>SMTP Port:</strong> " . ($smtpPort ?: '<span style="color:red">NOT SET</span>') . "<br>";
echo "<strong>SMTP User:</strong> " . ($smtpUser ?: '<span style="color:red">NOT SET</span>') . "<br>";
echo "<strong>SMTP Pass:</strong> " . ($smtpPass ? str_repeat('*', strlen($smtpPass)) : '<span style="color:red">NOT SET</span>') . "<br>";
echo "<strong>SMTP Encryption:</strong> " . ($smtpEncryption ?: 'tls') . "<br>";
echo "<strong>From Email:</strong> " . ($fromEmail ?: '<span style="color:red">NOT SET</span>') . "<br>";
echo "<strong>From Name:</strong> " . ($fromName ?: '<span style="color:red">NOT SET</span>') . "<br>";
echo "</div>";

if (empty($smtpHost) || empty($smtpUser) || empty($smtpPass)) {
    echo "<div class='error'>❌ SMTP credentials incomplete!</div>";
    exit;
}

// Step 3: Send test email
echo "<h2>Step 3: Send Test Email</h2>";
echo "<div class='info'>Sending test email to: <strong>$smtpUser</strong></div>";

try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // Enable verbose debug output
    $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client and server
    $mail->Debugoutput = function($str, $level) {
        echo "<pre>DEBUG [$level]: " . htmlspecialchars($str) . "</pre>";
    };
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    
    // Use correct encryption
    if ($smtpPort == 465) {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        echo "<div class='info'>Using SSL encryption (port 465)</div>";
    } else {
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        echo "<div class='info'>Using TLS encryption (port $smtpPort)</div>";
    }
    
    $mail->Port = $smtpPort;
    
    // Recipients
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($smtpUser); // Send to same email for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'DailyCup - Test Email ' . date('H:i:s');
    $mail->Body    = '<h1>Test Email from DailyCup</h1>
                      <p>This is a test email sent at <strong>' . date('Y-m-d H:i:s') . '</strong></p>
                      <p>If you receive this, SMTP is working correctly!</p>';
    $mail->AltBody = 'Test email from DailyCup sent at ' . date('Y-m-d H:i:s');
    
    echo "<p>⏳ Connecting to SMTP server...</p>";
    
    $mail->send();
    
    echo "<div class='success'>";
    echo "<h3>✅ Email Sent Successfully!</h3>";
    echo "<p><strong>Check your inbox:</strong> $smtpUser</p>";
    echo "<p>If you don't see it:</p>";
    echo "<ul>";
    echo "<li>Check SPAM/Junk folder</li>";
    echo "<li>Check Promotions tab (Gmail)</li>";
    echo "<li>Wait a few minutes (Gmail can be slow)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Email Sending Failed</h3>";
    echo "<strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "<br>";
    echo "<strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h4>Common Fixes:</h4>";
    echo "<ul>";
    echo "<li><strong>Gmail 2-Step Verification:</strong> Use App Password, not regular password</li>";
    echo "<li><strong>Less Secure Apps:</strong> Enable in Gmail settings (if not using App Password)</li>";
    echo "<li><strong>Port 587 vs 465:</strong> Try switching ports</li>";
    echo "<li><strong>Firewall:</strong> Check if port is blocked</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
