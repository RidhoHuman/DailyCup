<?php
// Load environment variables and required files
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "<h1>SMTP Debug Test</h1>";
echo "<pre>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;  // Enable verbose debug output
    $mail->isSMTP();                        // Send using SMTP
    $mail->Host       = $_ENV['SMTP_HOST']; // Set the SMTP server to send through
    $mail->SMTPAuth   = true;               // Enable SMTP authentication
    $mail->Username   = $_ENV['SMTP_USER']; // SMTP username
    $mail->Password   = $_ENV['SMTP_PASS']; // SMTP password (space or no space doesn't matter generally)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
    $mail->Port       = $_ENV['SMTP_PORT']; // TCP port to connect to

    // Recipients
    $mail->setFrom($_ENV['SMTP_FROM'], 'DailyCup Debugger');
    
    // Send to the admin/configured email
    $mail->addAddress($_ENV['SMTP_USER']); 

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'DailyCup SMTP Test ' . date('Y-m-d H:i:s');
    $mail->Body    = 'This is a test email to verify your SMTP settings. <b>Success!</b>';

    echo "Attempting to connect to " . $_ENV['SMTP_HOST'] . ":" . $_ENV['SMTP_PORT'] . "...\n";
    echo "User: " . $_ENV['SMTP_USER'] . "\n";
    echo "Pass Length: " . strlen($_ENV['SMTP_PASS']) . " chars\n";
    
    $mail->send();
    echo "\n-------------------------------------------------\n";
    echo "Message has been sent successfully!";
} catch (Exception $e) {
    echo "\n-------------------------------------------------\n";
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

echo "</pre>";
?>
