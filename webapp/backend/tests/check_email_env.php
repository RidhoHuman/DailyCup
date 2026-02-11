<?php
/**
 * Check Email Environment Variables
 * Helper script untuk email_checklist.php
 */

require_once __DIR__ . '/../api/.env.php';

header('Content-Type: application/json');

$response = [
    'smtp_enabled' => getenv('SMTP_ENABLED') === 'true' || getenv('SMTP_ENABLED') === '1',
    'smtp_host' => getenv('SMTP_HOST'),
    'smtp_port' => getenv('SMTP_PORT'),
    'smtp_username' => !empty(getenv('SMTP_USERNAME')),
    'smtp_password' => !empty(getenv('SMTP_PASSWORD')),
    'smtp_from_email' => getenv('SMTP_FROM_EMAIL'),
    'smtp_from_name' => getenv('SMTP_FROM_NAME'),
    'smtp_encryption' => getenv('SMTP_ENCRYPTION'),
    'app_url' => getenv('APP_URL'),
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response);
?>
